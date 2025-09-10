<?php
/**
 * Brute Force Protection functionality.
 *
 * @package    Kura_AI
 * @subpackage Kura_AI/includes
 * @author     Daniel Abughdyer <daniel@danovatesolutions.org>
 */

namespace Kura_AI;

// Make sure we have access to WordPress functions
if (!function_exists('add_action')) {
    exit;
}

// Make sure WordPress core functions are available
if (!function_exists('get_option')) {
    require_once ABSPATH . 'wp-includes/option.php';
}

// Make sure WordPress database functions are available
if (!isset($GLOBALS['wpdb'])) {
    require_once ABSPATH . 'wp-includes/wp-db.php';
}

// Make sure WordPress user functions are available
if (!class_exists('WP_User')) {
    require_once ABSPATH . 'wp-includes/class-wp-user.php';
}

class Kura_AI_Brute_Force {
    /**
     * The maximum number of failed login attempts allowed before lockout.
     *
     * @var int
     */
    private $max_attempts;

    /**
     * The lockout time in minutes.
     *
     * @var int
     */
    private $lockout_time;

    /**
     * Initialize the class and set its properties.
     */
    public function __construct() {
        $settings = get_option('kura_ai_settings', array());
        $brute_force_settings = isset($settings['brute_force_protection']) ? $settings['brute_force_protection'] : array();
        
        $this->max_attempts = isset($brute_force_settings['max_attempts']) ? intval($brute_force_settings['max_attempts']) : 5;
        $this->lockout_time = isset($brute_force_settings['lockout_time']) ? intval($brute_force_settings['lockout_time']) : 30;
    }

    /**
     * Initialize the brute force protection.
     */
    public function init() {
        // Hook into WordPress login failures
        add_action('wp_login_failed', array($this, 'handle_failed_login'));
        
        // Hook into WordPress authentication to check for locked IPs
        add_filter('authenticate', array($this, 'check_ip_lockout'), 30, 3);
        
        // Schedule cleanup of old lockouts
        if (!wp_next_scheduled('kura_ai_cleanup_lockouts')) {
            wp_schedule_event(time(), 'daily', 'kura_ai_cleanup_lockouts');
        }
        add_action('kura_ai_cleanup_lockouts', array($this, 'cleanup_old_lockouts'));
    }

    /**
     * Get the current user's IP address.
     *
     * @return string The IP address.
     */
    private function get_ip_address() {
        $ip_address = '';
        
        // Check for CloudFlare IP
        if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
            $ip_address = $_SERVER['HTTP_CF_CONNECTING_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            // Check for proxy IP
            $ip_address = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } elseif (!empty($_SERVER['REMOTE_ADDR'])) {
            // Default to REMOTE_ADDR
            $ip_address = $_SERVER['REMOTE_ADDR'];
        }
        
        // Validate IP address
        if (filter_var($ip_address, FILTER_VALIDATE_IP)) {
            return $ip_address;
        }
        
        return '0.0.0.0'; // Fallback
    }

    /**
     * Handle failed login attempts.
     *
     * @param string $username The username that was used in the failed login attempt.
     */
    public function handle_failed_login($username) {
        $wpdb = new \Kura_AI\wpdb();
        
        $ip_address = $this->get_ip_address();
        $table_name = $wpdb->prefix . 'kura_ai_brute_force';
        
        // Check if this IP+username combination exists
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE ip_address = %s AND username = %s",
            $ip_address,
            $username
        ));
        
        if ($existing) {
            // Update existing record
            $wpdb->update(
                $table_name,
                array(
                    'attempt_count' => $existing->attempt_count + 1,
                    'last_attempt' => current_time('mysql'),
                    'blocked_until' => ($existing->attempt_count + 1 >= $this->max_attempts) ? 
                        date('Y-m-d H:i:s', strtotime('+' . $this->lockout_time . ' minutes')) : null
                ),
                array('id' => $existing->id),
                array('%d', '%s', '%s'),
                array('%d')
            );
            
            // Log the failed attempt
            if ($existing->attempt_count + 1 >= $this->max_attempts) {
                $this->log_lockout($ip_address, $username, $this->lockout_time);
            }
        } else {
            // Insert new record
            $wpdb->insert(
                $table_name,
                array(
                    'ip_address' => $ip_address,
                    'username' => $username,
                    'attempt_count' => 1,
                    'last_attempt' => current_time('mysql'),
                    'blocked_until' => null
                ),
                array('%s', '%s', '%d', '%s', '%s')
            );
        }
    }

    /**
     * Check if the current IP is locked out.
     *
     * @param mixed $user     WP_User object if authenticated, WP_Error or null otherwise.
     * @param string $username Username.
     * @param string $password Password.
     * @return mixed           WP_User object if authenticated, WP_Error or null otherwise.
     */
    public function check_ip_lockout($user, $username, $password) {
        // Skip if already authenticated or empty username
        if ($user instanceof \WordPress\WP_User || empty($username)) {
            return $user;
        }
        
        $wpdb = new \Kura_AI\wpdb();
        $ip_address = $this->get_ip_address();
        $table_name = $wpdb->prefix . 'kura_ai_brute_force';
        
        // Check if this IP is locked out
        $lockout = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE ip_address = %s AND blocked_until > %s AND attempt_count >= %d",
            $ip_address,
            current_time('mysql'),
            $this->max_attempts
        ));
        
        if ($lockout) {
            // Calculate remaining lockout time
            $blocked_until = strtotime($lockout->blocked_until);
            $current_time = strtotime(current_time('mysql'));
            $minutes_remaining = ceil(($blocked_until - $current_time) / 60);
            
            // Return error with remaining lockout time
            return new \WP_Error(
                'ip_locked',
                sprintf(
                    __('Too many failed login attempts. Please try again in %d minutes.', 'kura-ai'),
                    $minutes_remaining
                )
            );
        }
        
        return $user;
    }

    /**
     * Log a lockout event.
     *
     * @param string $ip_address   The IP address that was locked out.
     * @param string $username     The username that was used in the failed login attempt.
     * @param int    $lockout_time The lockout time in minutes.
     */
    private function log_lockout($ip_address, $username, $lockout_time) {
        $wpdb = new \Kura_AI\wpdb();
        $logs_table = $wpdb->prefix . 'kura_ai_logs';
        
        $log_data = array(
            'ip' => $ip_address,
            'username' => $username,
            'lockout_time' => $lockout_time
        );
        
        $wpdb->insert(
            $logs_table,
            array(
                'log_type' => 'brute_force',
                'log_message' => sprintf(
                    __('IP %s locked out for %d minutes after failed login attempts for username: %s', 'kura-ai'),
                    $ip_address,
                    $lockout_time,
                    $username
                ),
                'log_data' => json_encode($log_data),
                'severity' => 'warning',
                'created_at' => current_time('mysql')
            ),
            array('%s', '%s', '%s', '%s', '%s')
        );
    }

    /**
     * Clean up old lockouts.
     */
    public function cleanup_old_lockouts() {
        $wpdb = new \Kura_AI\wpdb();
        $table_name = $wpdb->prefix . 'kura_ai_brute_force';
        
        // Delete records where blocked_until is in the past
        $wpdb->query($wpdb->prepare(
            "DELETE FROM $table_name WHERE blocked_until < %s AND blocked_until IS NOT NULL",
            current_time('mysql')
        ));
        
        // Reset attempt count for records older than 24 hours
        $wpdb->query($wpdb->prepare(
            "UPDATE $table_name SET attempt_count = 0 WHERE last_attempt < %s",
            date('Y-m-d H:i:s', strtotime('-24 hours'))
        ));
    }
    
    /**
     * Get current brute force protection settings.
     *
     * @return array Settings array.
     */
    public function get_settings() {
        $settings = get_option('kura_ai_settings', array());
        $brute_force_settings = isset($settings['brute_force_protection']) ? $settings['brute_force_protection'] : array();
        
        return array(
            'allowed_attempts' => isset($brute_force_settings['max_attempts']) ? intval($brute_force_settings['max_attempts']) : $this->max_attempts,
            'lockout_duration' => isset($brute_force_settings['lockout_time']) ? intval($brute_force_settings['lockout_time']) : $this->lockout_time,
            'retry_time' => 24 * 60, // Default to 24 hours in minutes
        );
    }
    
    /**
     * Get current lockouts.
     *
     * @return array Array of IP addresses and their lockout expiration times.
     */
    public function get_lockouts() {
        $wpdb = new \Kura_AI\wpdb();
        $table_name = $wpdb->prefix . 'kura_ai_brute_force';
        $lockouts = array();
        
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT ip_address, blocked_until FROM $table_name WHERE blocked_until > %s AND attempt_count >= %d",
            current_time('mysql'),
            $this->max_attempts
        ));
        
        if ($results) {
            foreach ($results as $row) {
                $lockouts[$row->ip_address] = strtotime($row->blocked_until);
            }
        }
        
        return $lockouts;
    }
    
    /**
     * Get failed login attempts.
     *
     * @return array Array of IP addresses and their attempt counts.
     */
    public function get_failed_attempts() {
        $wpdb = new \Kura_AI\wpdb();
        $table_name = $wpdb->prefix . 'kura_ai_brute_force';
        $attempts = array();
        
        $results = $wpdb->get_results(
            "SELECT ip_address, attempt_count, last_attempt FROM $table_name WHERE attempt_count > 0 ORDER BY last_attempt DESC LIMIT 50"
        );
        
        if ($results) {
            foreach ($results as $row) {
                $attempts[$row->ip_address] = array(
                    'attempts' => $row->attempt_count,
                    'last_attempt' => strtotime($row->last_attempt)
                );
            }
        }
        
        return $attempts;
    }
    
    /**
     * Update brute force protection settings.
     *
     * @param array $new_settings New settings array.
     * @return bool True on success, false on failure.
     */
    public function update_settings($new_settings) {
        $settings = get_option('kura_ai_settings', array());
        
        if (!isset($settings['brute_force_protection'])) {
            $settings['brute_force_protection'] = array();
        }
        
        $settings['brute_force_protection']['max_attempts'] = isset($new_settings['allowed_attempts']) ? intval($new_settings['allowed_attempts']) : 5;
        $settings['brute_force_protection']['lockout_time'] = isset($new_settings['lockout_duration']) ? intval($new_settings['lockout_duration']) : 30;
        
        $this->max_attempts = $settings['brute_force_protection']['max_attempts'];
        $this->lockout_time = $settings['brute_force_protection']['lockout_time'];
        
        return update_option('kura_ai_settings', $settings);
    }
    
    /**
     * Reset all lockouts and failed attempts.
     *
     * @return bool True on success, false on failure.
     */
    public function reset_lockouts() {
        $wpdb = new \Kura_AI\wpdb();
        $table_name = $wpdb->prefix . 'kura_ai_brute_force';
        
        $result = $wpdb->query("TRUNCATE TABLE $table_name");
        
        return $result !== false;
    }
}