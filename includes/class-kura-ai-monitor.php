<?php
/**
 * Real-time Security Monitoring functionality.
 *
 * @package    Kura_AI
 * @subpackage Kura_AI/includes
 * @author     Daniel Abughdyer <daniel@danovatesolutions.org>
 */

class Kura_AI_Monitor {

    /**
     * The database interface instance.
     *
     * @since    1.0.0
     * @access   private
     * @var      wpdb    $db    The database interface instance.
     */
    private $db;

    /**
     * The logger instance.
     *
     * @since    1.0.0
     * @access   private
     * @var      Kura_AI_Logger    $logger    The logger instance.
     */
    private $logger;

    /**
     * The notifier instance.
     *
     * @since    1.0.0
     * @access   private
     * @var      Kura_AI_Notifier    $notifier    The notifier instance.
     */
    private $notifier;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     */
    public function __construct() {
        global $wpdb;
        $this->db = $wpdb;
        $this->logger = new Kura_AI_Logger();
        $this->notifier = new Kura_AI_Notifier();

        // Initialize monitoring hooks
        $this->init_hooks();
    }

    /**
     * Initialize WordPress hooks for monitoring.
     *
     * @since    1.0.0
     */
    private function init_hooks() {
        // Core monitoring
        add_action('wp_login', array($this, 'monitor_login'), 10, 2);
        add_action('wp_login_failed', array($this, 'monitor_failed_login'));
        add_action('admin_init', array($this, 'monitor_admin_access'));
        
        // File system monitoring
        add_action('add_attachment', array($this, 'monitor_file_upload'));
        add_action('delete_attachment', array($this, 'monitor_file_deletion'));
        
        // Database monitoring
        add_action('pre_insert_user', array($this, 'monitor_user_creation'));
        add_action('delete_user', array($this, 'monitor_user_deletion'));
        add_action('activated_plugin', array($this, 'monitor_plugin_activation'));
        add_action('deactivated_plugin', array($this, 'monitor_plugin_deactivation'));
    }

    /**
     * Get real-time security metrics.
     *
     * @since    1.0.0
     * @return   array    Security metrics data.
     */
    public function get_security_metrics() {
        return array(
            'failed_logins' => $this->get_failed_login_count(),
            'file_changes' => $this->get_file_change_count(),
            'malware_detections' => $this->get_malware_detection_count(),
            'user_activities' => $this->get_user_activity_count(),
            'system_alerts' => $this->get_system_alert_count()
        );
    }

    /**
     * Get recent security events for the dashboard.
     *
     * @since    1.0.0
     * @param    int    $limit    Number of events to return.
     * @return   array    Recent security events.
     */
    public function get_recent_events($limit = 10) {
        $table_name = $this->db->prefix . 'kura_ai_logs';
        
        return $this->db->get_results(
            $this->db->prepare(
                "SELECT * FROM {$table_name} ORDER BY created_at DESC LIMIT %d",
                $limit
            )
        );
    }

    /**
     * Monitor successful login attempts.
     *
     * @since    1.0.0
     * @param    string    $user_login    The user's login name.
     * @param    WP_User   $user         The user object.
     */
    public function monitor_login($user_login, $user) {
        $this->logger->log_event(
            'login',
            sprintf('Successful login: %s', $user_login),
            array(
                'user_id' => $user->ID,
                'ip_address' => $this->get_client_ip()
            )
        );
    }

    /**
     * Monitor failed login attempts.
     *
     * @since    1.0.0
     * @param    string    $username    The attempted username.
     */
    public function monitor_failed_login($username) {
        $ip_address = $this->get_client_ip();
        $failed_attempts = $this->get_failed_attempts($ip_address);

        $this->logger->log_event(
            'failed_login',
            sprintf('Failed login attempt: %s', $username),
            array(
                'ip_address' => $ip_address,
                'attempt_count' => $failed_attempts
            )
        );

        // Check for brute force attempts
        if ($failed_attempts >= 5) {
            $this->notifier->send_alert(
                'brute_force_attempt',
                sprintf('Multiple failed login attempts from IP: %s', $ip_address)
            );
        }
    }

    /**
     * Monitor admin area access.
     *
     * @since    1.0.0
     */
    public function monitor_admin_access() {
        if (!current_user_can('manage_options')) {
            return;
        }

        $this->logger->log_event(
            'admin_access',
            'Admin area accessed',
            array(
                'user_id' => get_current_user_id(),
                'ip_address' => $this->get_client_ip()
            )
        );
    }

    /**
     * Monitor file uploads.
     *
     * @since    1.0.0
     * @param    int    $attachment_id    The attachment ID.
     */
    public function monitor_file_upload($attachment_id) {
        $file = get_attached_file($attachment_id);
        $file_type = wp_check_filetype($file);

        $this->logger->log_event(
            'file_upload',
            sprintf('File uploaded: %s', basename($file)),
            array(
                'file_type' => $file_type['type'],
                'file_size' => filesize($file),
                'user_id' => get_current_user_id()
            )
        );
    }

    /**
     * Monitor file deletions.
     *
     * @since    1.0.0
     * @param    int    $attachment_id    The attachment ID.
     */
    public function monitor_file_deletion($attachment_id) {
        $file = get_attached_file($attachment_id);

        $this->logger->log_event(
            'file_deletion',
            sprintf('File deleted: %s', basename($file)),
            array(
                'user_id' => get_current_user_id()
            )
        );
    }

    /**
     * Monitor user creation.
     *
     * @since    1.0.0
     * @param    array    $userdata    Array of user data.
     */
    public function monitor_user_creation($userdata) {
        $this->logger->log_event(
            'user_creation',
            sprintf('New user created: %s', $userdata['user_login']),
            array(
                'user_email' => $userdata['user_email'],
                'user_role' => $userdata['role'],
                'created_by' => get_current_user_id()
            )
        );
    }

    /**
     * Monitor user deletion.
     *
     * @since    1.0.0
     * @param    int    $user_id    The user ID.
     */
    public function monitor_user_deletion($user_id) {
        $user = get_userdata($user_id);

        $this->logger->log_event(
            'user_deletion',
            sprintf('User deleted: %s', $user->user_login),
            array(
                'deleted_by' => get_current_user_id()
            )
        );
    }

    /**
     * Monitor plugin activation.
     *
     * @since    1.0.0
     * @param    string    $plugin    Plugin path.
     */
    public function monitor_plugin_activation($plugin) {
        $this->logger->log_event(
            'plugin_activation',
            sprintf('Plugin activated: %s', $plugin),
            array(
                'user_id' => get_current_user_id()
            )
        );
    }

    /**
     * Monitor plugin deactivation.
     *
     * @since    1.0.0
     * @param    string    $plugin    Plugin path.
     */
    public function monitor_plugin_deactivation($plugin) {
        $this->logger->log_event(
            'plugin_deactivation',
            sprintf('Plugin deactivated: %s', $plugin),
            array(
                'user_id' => get_current_user_id()
            )
        );
    }

    /**
     * Get client IP address.
     *
     * @since    1.0.0
     * @return   string    Client IP address.
     */
    private function get_client_ip() {
        $ip_address = '';
        
        if (isset($_SERVER['HTTP_CLIENT_IP'])) {
            $ip_address = sanitize_text_field($_SERVER['HTTP_CLIENT_IP']);
        } elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip_address = sanitize_text_field($_SERVER['HTTP_X_FORWARDED_FOR']);
        } elseif (isset($_SERVER['REMOTE_ADDR'])) {
            $ip_address = sanitize_text_field($_SERVER['REMOTE_ADDR']);
        }
        
        return $ip_address;
    }

    /**
     * Get number of failed login attempts for an IP.
     *
     * @since    1.0.0
     * @param    string    $ip_address    The IP address to check.
     * @return   int       Number of failed attempts.
     */
    private function get_failed_attempts($ip_address) {
        $table_name = $this->db->prefix . 'kura_ai_logs';
        
        return (int) $this->db->get_var(
            $this->db->prepare(
                "SELECT COUNT(*) FROM {$table_name} 
                WHERE event_type = 'failed_login' 
                AND ip_address = %s 
                AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)",
                $ip_address
            )
        );
    }

    /**
     * Get count of failed logins in last 24 hours.
     *
     * @since    1.0.0
     * @return   int    Count of failed logins.
     */
    private function get_failed_login_count() {
        $table_name = $this->db->prefix . 'kura_ai_logs';
        
        return (int) $this->db->get_var(
            "SELECT COUNT(*) FROM {$table_name} 
            WHERE event_type = 'failed_login' 
            AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)"
        );
    }

    /**
     * Get count of file changes in last 24 hours.
     *
     * @since    1.0.0
     * @return   int    Count of file changes.
     */
    private function get_file_change_count() {
        $table_name = $this->db->prefix . 'kura_ai_logs';
        
        return (int) $this->db->get_var(
            "SELECT COUNT(*) FROM {$table_name} 
            WHERE event_type IN ('file_upload', 'file_deletion') 
            AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)"
        );
    }

    /**
     * Get count of malware detections in last 24 hours.
     *
     * @since    1.0.0
     * @return   int    Count of malware detections.
     */
    private function get_malware_detection_count() {
        $table_name = $this->db->prefix . 'kura_ai_logs';
        
        return (int) $this->db->get_var(
            "SELECT COUNT(*) FROM {$table_name} 
            WHERE event_type = 'malware_detection' 
            AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)"
        );
    }

    /**
     * Get count of user activities in last 24 hours.
     *
     * @since    1.0.0
     * @return   int    Count of user activities.
     */
    private function get_user_activity_count() {
        $table_name = $this->db->prefix . 'kura_ai_logs';
        
        return (int) $this->db->get_var(
            "SELECT COUNT(*) FROM {$table_name} 
            WHERE event_type IN ('login', 'admin_access', 'user_creation', 'user_deletion') 
            AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)"
        );
    }

    /**
     * Get count of system alerts in last 24 hours.
     *
     * @since    1.0.0
     * @return   int    Count of system alerts.
     */
    private function get_system_alert_count() {
        $table_name = $this->db->prefix . 'kura_ai_logs';
        
        return (int) $this->db->get_var(
            "SELECT COUNT(*) FROM {$table_name} 
            WHERE event_type = 'system_alert' 
            AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)"
        );
    }
}