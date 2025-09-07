<?php

namespace Kura_AI;

/**
 * The file monitoring functionality of the plugin.
 *
 * @link       https://kura.ai
 * @since      1.0.0
 *
 * @package    Kura_Ai
 * @subpackage Kura_Ai/includes
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// WordPress function stubs for static analysis
if (!function_exists('get_template_directory')) {
    function get_template_directory() { return '/path/to/theme'; }
}
if (!function_exists('is_child_theme')) {
    function is_child_theme() { return false; }
}
if (!function_exists('get_stylesheet_directory')) {
    function get_stylesheet_directory() { return '/path/to/stylesheet'; }
}
if (!function_exists('wp_next_scheduled')) {
    function wp_next_scheduled($hook, $args = array()) { return false; }
}
if (!function_exists('wp_schedule_event')) {
    function wp_schedule_event($timestamp, $recurrence, $hook, $args = array()) { return true; }
}
if (!function_exists('dbDelta')) {
    function dbDelta($queries = '', $execute = true) { return array(); }
}
if (!function_exists('current_time')) {
    function current_time($type, $gmt = 0) { return date('Y-m-d H:i:s'); }
}
if (!function_exists('esc_html__')) {
    function esc_html__($text, $domain = 'default') { return htmlspecialchars($text, ENT_QUOTES, 'UTF-8'); }
}
if (!function_exists('is_wp_error')) {
    function is_wp_error($thing) { return is_a($thing, 'WP_Error'); }
}
if (!function_exists('check_ajax_referer')) {
    function check_ajax_referer($action = -1, $query_arg = false, $die = true) { return true; }
}
if (!function_exists('current_user_can')) {
    function current_user_can($capability, $object_id = null) { return true; }
}
if (!function_exists('wp_die')) {
    function wp_die($message = '', $title = '', $args = array()) { die($message); }
}
if (!function_exists('sanitize_text_field')) {
    function sanitize_text_field($str) { return trim(strip_tags($str)); }
}
if (!function_exists('wp_send_json_error')) {
    function wp_send_json_error($data = null, $status_code = null) { wp_die(json_encode(array('success' => false, 'data' => $data))); }
}
if (!function_exists('wp_send_json_success')) {
    function wp_send_json_success($data = null, $status_code = null) { wp_die(json_encode(array('success' => true, 'data' => $data))); }
}
if (!function_exists('update_option')) {
    function update_option($option, $value, $autoload = null) { return true; }
}
if (!function_exists('get_option')) {
    function get_option($option, $default = false) { return $default; }
}
if (!class_exists('WP_Error')) {
    class WP_Error {
        public $errors = array();
        public $error_data = array();
        public function __construct($code = '', $message = '', $data = '') {
            if (empty($code)) return;
            $this->errors[$code][] = $message;
            if (!empty($data)) $this->error_data[$code] = $data;
        }
        public function get_error_message($code = '') { return isset($this->errors[$code]) ? $this->errors[$code][0] : ''; }
    }
}
if (!defined('ARRAY_A')) {
    define('ARRAY_A', 'ARRAY_A');
}

// Import WordPress core files and functions
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-includes/pluggable.php';
require_once ABSPATH . 'wp-admin/includes/upgrade.php';

// WordPress functions are accessed globally in namespaced context

class Kura_AI_File_Monitor {
    private $monitored_files;
    private $version_table;
    private $wpdb;
    private $critical_files;
    private $scan_results_table;

    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->version_table = $wpdb->prefix . 'kura_ai_file_versions';
        $this->scan_results_table = $wpdb->prefix . 'kura_ai_file_scan_results';
        $this->monitored_files = get_option('kura_ai_monitored_files', array());
        $this->critical_files = $this->get_critical_wordpress_files();
        $this->init_critical_monitoring();
        
        // Perform initial scan if table is empty
        $this->maybe_perform_initial_scan();
    }

    /**
     * Get critical WordPress files that should be monitored
     */
    public function get_critical_wordpress_files() {
        $files = array();
        
        // WordPress core files
        $wp_config = ABSPATH . 'wp-config.php';
        if (file_exists($wp_config)) {
            $files['wp-config.php'] = $wp_config;
        }
        
        // .htaccess file
        $htaccess = ABSPATH . '.htaccess';
        if (file_exists($htaccess)) {
            $files['.htaccess'] = $htaccess;
        }
        
        // Active theme's functions.php
        $theme_dir = \get_template_directory();
        if ($theme_dir !== false) {
            $functions_php = $theme_dir . '/functions.php';
            if (file_exists($functions_php)) {
                $files['functions.php'] = $functions_php;
            }
        }
        
        // Child theme functions.php if exists
        if (\is_child_theme()) {
            $child_theme_dir = \get_stylesheet_directory();
            if ($child_theme_dir !== false) {
                $child_functions = $child_theme_dir . '/functions.php';
                if (file_exists($child_functions)) {
                    $files['child-functions.php'] = $child_functions;
                }
            }
        }
        
        // WordPress index.php
        $index_php = ABSPATH . 'index.php';
        if (file_exists($index_php)) {
            $files['index.php'] = $index_php;
        }
        
        return $files;
    }

    /**
     * Initialize critical file monitoring
     */
    private function init_critical_monitoring() {
        // Create scan results table if it doesn't exist
        $this->create_scan_results_table();
        
        // Schedule only essential daily scan to prevent cron overload
        if (function_exists('wp_next_scheduled') && !\wp_next_scheduled('kura_ai_file_monitor_scan')) {
            // Add delay to prevent conflicts with other Kura AI cron events
            $schedule_time = time() + 600; // 10 minute delay
            if (function_exists('wp_schedule_event')) {
                $result = \wp_schedule_event($schedule_time, 'daily', 'kura_ai_file_monitor_scan');
                
                if ($result === false) {
                    error_log('Kura AI: Failed to schedule file monitor scan');
                }
            }
        }
        
        // Removed hourly auto_scan to reduce cron load and prevent "could_not_set" errors
    }
    
    /**
     * Perform initial scan if table is empty
     */
    private function maybe_perform_initial_scan() {
        // Check if we have any scan results
        $count = $this->wpdb->get_var(
            "SELECT COUNT(*) FROM {$this->scan_results_table}"
        );
        
        // If no results exist, perform initial scan
        if ($count == 0) {
            $this->perform_automatic_scan();
        }
    }

    /**
     * Create scan results table
     */
    private function create_scan_results_table() {
        $charset_collate = $this->wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS {$this->scan_results_table} (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            file_name varchar(255) NOT NULL,
            file_path text NOT NULL,
            file_size bigint(20) NOT NULL,
            file_hash varchar(64) NOT NULL,
            last_modified datetime NOT NULL,
            scan_date datetime DEFAULT CURRENT_TIMESTAMP,
            status varchar(20) DEFAULT 'normal',
            risk_level varchar(10) DEFAULT 'low',
            changes_detected text,
            PRIMARY KEY (id),
            KEY file_name (file_name),
            KEY scan_date (scan_date)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        if (function_exists('dbDelta')) {
            \dbDelta($sql);
        }
    }

    /**
     * Run a comprehensive file scan
     */
    public function run_file_scan() {
        $critical_files = $this->get_critical_wordpress_files();
        $scan_results = array();
        
        foreach ($critical_files as $file_path) {
            if (file_exists($file_path)) {
                $file_info = $this->scan_single_file($file_path);
                if ($file_info) {
                    $scan_results[] = $file_info;
                    $this->save_scan_result($file_info);
                }
            }
        }
        
        return $scan_results;
    }
    
    /**
     * Scan individual file and return analysis
     */
    private function scan_single_file($file_path) {
        if (!file_exists($file_path)) {
            return false;
        }
        
        $file_size = filesize($file_path);
        $file_hash = hash_file('sha256', $file_path);
        $last_modified = date('Y-m-d H:i:s', filemtime($file_path));
        
        // Determine status based on file analysis
        $status = $this->analyze_file_status($file_path);
        $risk_level = $this->determine_risk_level($file_path, $status);
        
        return array(
            'file_name' => basename($file_path),
            'file_path' => $file_path,
            'file_size' => $file_size,
            'file_hash' => $file_hash,
            'last_modified' => $last_modified,
            'status' => $status,
            'risk_level' => $risk_level,
            'changes_detected' => ''
        );
    }
    
    /**
     * Analyze file status
     */
    private function analyze_file_status($file_path) {
        // Basic file analysis
        $content = file_get_contents($file_path);
        
        // Check for suspicious patterns
        $suspicious_patterns = array(
            'eval\s*\(',
            'base64_decode\s*\(',
            'exec\s*\(',
            'system\s*\(',
            'shell_exec\s*\(',
            'passthru\s*\(',
            'file_get_contents\s*\(\s*["\']https?://'
        );
        
        foreach ($suspicious_patterns as $pattern) {
            if (preg_match('~' . $pattern . '~i', $content)) {
                return 'suspicious';
            }
        }
        
        // Check if file was recently modified
        $file_time = filemtime($file_path);
        $week_ago = time() - (7 * 24 * 60 * 60);
        
        if ($file_time > $week_ago) {
            return 'modified';
        }
        
        return 'clean';
    }
    
    /**
     * Determine risk level based on file analysis
     */
    private function determine_risk_level($file_path, $status) {
        if ($status === 'suspicious') {
            return 'high';
        }
        
        if ($status === 'modified') {
            // Check if it's a core WordPress file
            if (strpos($file_path, ABSPATH . 'wp-admin') === 0 || 
                strpos($file_path, ABSPATH . 'wp-includes') === 0) {
                return 'high';
            }
            return 'medium';
        }
        
        return 'low';
    }
    
    /**
     * Save scan result to database
     */
    private function save_scan_result($file_info) {
        $this->wpdb->insert(
            $this->scan_results_table,
            array(
                'file_name' => $file_info['file_name'],
                'file_path' => $file_info['file_path'],
                'file_size' => $file_info['file_size'],
                'file_hash' => $file_info['file_hash'],
                'last_modified' => $file_info['last_modified'],
                'scan_date' => function_exists('current_time') ? \current_time('mysql') : date('Y-m-d H:i:s'),
                'status' => $file_info['status'],
                'risk_level' => $file_info['risk_level'],
                'changes_detected' => $file_info['changes_detected']
            ),
            array('%s', '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%s')
        );
    }

    public function add_monitored_file($file_path) {
        if (!file_exists($file_path)) {
            return new \WP_Error('invalid_file', \esc_html__('File does not exist', 'kura-ai'));
        }

        if (!in_array($file_path, $this->monitored_files)) {
            $this->monitored_files[] = $file_path;
            \update_option('kura_ai_monitored_files', $this->monitored_files);
            $this->create_version($file_path, 'Initial version');
        }

        return true;
    }

    public function remove_monitored_file($file_path) {
        $key = array_search($file_path, $this->monitored_files);
        if ($key !== false) {
            unset($this->monitored_files[$key]);
            $this->monitored_files = array_values($this->monitored_files);
            \update_option('kura_ai_monitored_files', $this->monitored_files);
            return true;
        }
        return false;
    }

    public function create_version($file_path, $description = '') {
        if (!file_exists($file_path)) {
            return new \WP_Error('invalid_file', \esc_html__('File does not exist', 'kura-ai'));
        }

        $content = file_get_contents($file_path);
        $hash = md5($content);

        $this->wpdb->insert(
            $this->version_table,
            array(
                'file_path' => $file_path,
                'content' => $content,
                'hash' => $hash,
                'description' => \sanitize_text_field($description),
                'created_at' => function_exists('current_time') ? \current_time('mysql') : date('Y-m-d H:i:s')
            ),
            array('%s', '%s', '%s', '%s', '%s')
        );

        return $this->wpdb->insert_id;
    }

    public function rollback_version($version_id) {
        $version = $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->version_table} WHERE id = %d",
                $version_id
            )
        );

        if (!$version) {
            return new \WP_Error('invalid_version', \esc_html__('Version not found', 'kura-ai'));
        }

        if (!file_exists($version->file_path)) {
            return new \WP_Error('invalid_file', \esc_html__('Original file no longer exists', 'kura-ai'));
        }

        $result = file_put_contents($version->file_path, $version->content);
        if ($result === false) {
            return new \WP_Error('write_error', \esc_html__('Failed to write file content', 'kura-ai'));
        }

        return true;
    }

    public function get_file_versions($file_path) {
        return $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->version_table} WHERE file_path = %s ORDER BY created_at DESC",
                $file_path
            )
        );
    }

    public function remove_file_versions($file_path) {
        return $this->wpdb->delete(
            $this->version_table,
            array('file_path' => $file_path),
            array('%s')
        );
    }

    public function compare_versions($version_id_1, $version_id_2) {
        $version1 = $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->version_table} WHERE id = %d",
                $version_id_1
            )
        );

        $version2 = $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->version_table} WHERE id = %d",
                $version_id_2
            )
        );

        if (!$version1 || !$version2) {
            return new \WP_Error('invalid_version', \esc_html__('One or both versions not found', 'kura-ai'));
        }

        // Generate diff HTML
        $diff_html = $this->generate_diff_html($version1->content, $version2->content);
        
        return array(
            'diff_html' => $diff_html,
            'version1' => array(
                'content' => $version1->content,
                'created_at' => $version1->created_at,
                'description' => $version1->description
            ),
            'version2' => array(
                'content' => $version2->content,
                'created_at' => $version2->created_at,
                'description' => $version2->description
            )
        );
    }

    public function get_monitored_files() {
        return $this->monitored_files;
    }
    
    /**
     * Generate HTML diff between two content strings
     */
    private function generate_diff_html($content1, $content2) {
        $lines1 = explode("\n", $content1);
        $lines2 = explode("\n", $content2);
        
        $diff_html = '<div class="diff-container">';
        $diff_html .= '<div class="diff-side">';
        $diff_html .= '<h4>Version 1</h4>';
        $diff_html .= '<pre class="diff-content">' . esc_html($content1) . '</pre>';
        $diff_html .= '</div>';
        $diff_html .= '<div class="diff-side">';
        $diff_html .= '<h4>Version 2</h4>';
        $diff_html .= '<pre class="diff-content">' . esc_html($content2) . '</pre>';
        $diff_html .= '</div>';
        $diff_html .= '</div>';
        
        return $diff_html;
    }

    /**
     * Perform automatic scan of critical WordPress files
     */
    public function perform_automatic_scan() {
        $scan_results = array();
        
        foreach ($this->critical_files as $file_name => $file_path) {
            if (file_exists($file_path)) {
                $result = $this->scan_file($file_path, $file_name);
                $scan_results[] = $result;
                $this->store_scan_result($result);
            }
        }
        
        return $scan_results;
    }

    /**
     * Scan individual file for changes and security issues
     */
    private function scan_file($file_path, $file_name) {
        $file_size = filesize($file_path);
        $last_modified = date('Y-m-d H:i:s', filemtime($file_path));
        
        // Basic security checks
        $content = file_get_contents($file_path);
        $status = 'clean';
        $risk_level = 'low';
        $changes_detected = '';
        
        // Check for suspicious patterns
        $suspicious_patterns = array(
            'eval\s*\(',
            'base64_decode\s*\(',
            'shell_exec\s*\(',
            'system\s*\(',
            'exec\s*\(',
            'passthru\s*\(',
            'file_get_contents\s*\(\s*["\']https?://'
        );
        
        foreach ($suspicious_patterns as $pattern) {
            if (preg_match('~' . $pattern . '~i', $content)) {
                $status = 'suspicious';
                $risk_level = 'high';
                $changes_detected .= 'Suspicious pattern detected: ' . $pattern . "\n";
            }
        }
        
        return array(
            'file_name' => $file_name,
            'file_path' => $file_path,
            'file_size' => $file_size,
            'last_modified' => $last_modified,
            'status' => $status,
            'risk_level' => $risk_level,
            'changes_detected' => $changes_detected
        );
    }

    /**
     * Store scan result in database
     */
    private function store_scan_result($result) {
        $this->wpdb->insert(
            $this->scan_results_table,
            array(
                'file_name' => $result['file_name'],
                'file_path' => $result['file_path'],
                'file_size' => $result['file_size'],
                'last_modified' => $result['last_modified'],
                'status' => $result['status'],
                'risk_level' => $result['risk_level'],
                'changes_detected' => $result['changes_detected'],
                'scan_date' => function_exists('current_time') ? \current_time('mysql') : date('Y-m-d H:i:s')
            ),
            array('%s', '%s', '%d', '%s', '%s', '%s', '%s', '%s')
        );
    }

    /**
     * Get chart data for dashboard
     */
    public function get_chart_data() {
        // Check if table exists
        $table_exists = $this->wpdb->get_var($this->wpdb->prepare(
            "SHOW TABLES LIKE %s",
            $this->scan_results_table
        ));
        
        if (!$table_exists) {
            return array(
                'file_status_distribution' => array('labels' => array(), 'data' => array()),
                'risk_level_distribution' => array('labels' => array(), 'data' => array()),
                'file_changes_timeline' => array('labels' => array(), 'data' => array()),
                'file_size_trends' => array('labels' => array(), 'data' => array())
            );
        }
        
        $results = $this->wpdb->get_results(
            "SELECT * FROM {$this->scan_results_table} 
             WHERE scan_date >= DATE_SUB(NOW(), INTERVAL 30 DAY) 
             ORDER BY scan_date DESC",
            ARRAY_A
        );
        
        if ($this->wpdb->last_error || !is_array($results)) {
            return array(
                'file_status_distribution' => array('labels' => array(), 'data' => array()),
                'risk_level_distribution' => array('labels' => array(), 'data' => array()),
                'file_changes_timeline' => array('labels' => array(), 'data' => array()),
                'file_size_trends' => array('labels' => array(), 'data' => array())
            );
        }
        
        return array(
            'file_status_distribution' => $this->get_file_status_distribution($results),
            'risk_level_distribution' => $this->get_risk_level_distribution($results),
            'file_changes_timeline' => $this->get_file_changes_timeline($results),
            'file_size_trends' => $this->get_file_size_trends($results)
        );
    }

    /**
     * Get recent scan results
     */
    public function get_recent_scan_results($limit = 10) {
        // Check if table exists
        $table_exists = $this->wpdb->get_var($this->wpdb->prepare(
            "SHOW TABLES LIKE %s",
            $this->scan_results_table
        ));
        
        if (!$table_exists) {
            return array();
        }
        
        $results = $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->scan_results_table} 
                 ORDER BY scan_date DESC 
                 LIMIT %d",
                $limit
            ),
            ARRAY_A
        );
        
        if ($this->wpdb->last_error || !is_array($results)) {
            return array();
        }
        
        return $results;
    }

    /**
     * Get critical files list
     */
    public function get_critical_files_list() {
        $critical_files = array(
            'wp-config.php' => ABSPATH . 'wp-config.php',
            'index.php' => ABSPATH . 'index.php',
            '.htaccess' => ABSPATH . '.htaccess',
            'wp-admin/index.php' => ABSPATH . 'wp-admin/index.php',
            'wp-includes/wp-config-sample.php' => ABSPATH . 'wp-includes/wp-config-sample.php',
            'active_theme_functions' => \get_template_directory() . '/functions.php',
            'active_theme_index' => \get_template_directory() . '/index.php',
            'active_theme_style' => \get_template_directory() . '/style.css'
        );

        // Add child theme files if active
        if (\is_child_theme()) {
            $critical_files['child_theme_functions'] = \get_stylesheet_directory() . '/functions.php';
            $critical_files['child_theme_style'] = \get_stylesheet_directory() . '/style.css';
        }
        
        return $critical_files;
    }

    // AJAX handlers
    public function ajax_monitor_file() {
        if (function_exists('check_ajax_referer')) {
            \check_ajax_referer('kura_ai_nonce', 'nonce');
        }
        
        if (!\current_user_can('manage_options')) {
            \wp_die(\esc_html__('Insufficient permissions', 'kura-ai'));
        }

        $file_path = \sanitize_text_field($_POST['file_path']);
        $result = $this->add_monitored_file($file_path);

        if (\is_wp_error($result)) {
            \wp_send_json_error($result->get_error_message());
        } else {
            \wp_send_json_success(\esc_html__('File added to monitoring', 'kura-ai'));
        }
    }

    public function ajax_stop_monitoring() {
        \check_ajax_referer('kura_ai_nonce', 'nonce');
        
        if (!\current_user_can('manage_options')) {
            \wp_die(\esc_html__('Insufficient permissions', 'kura-ai'));
        }

        $file_path = \sanitize_text_field($_POST['file_path']);
        $result = $this->remove_monitored_file($file_path);

        if ($result) {
            \wp_send_json_success(\esc_html__('File removed from monitoring', 'kura-ai'));
        } else {
            \wp_send_json_error(\esc_html__('File not found in monitoring list', 'kura-ai'));
        }
    }

    public function ajax_create_version() {
        \check_ajax_referer('kura_ai_nonce', 'nonce');
        
        if (!\current_user_can('manage_options')) {
            \wp_die(\esc_html__('Insufficient permissions', 'kura-ai'));
        }

        $file_path = \sanitize_text_field($_POST['file_path']);
        $description = \sanitize_text_field($_POST['description']);
        
        $result = $this->create_version($file_path, $description);

        if (\is_wp_error($result)) {
            \wp_send_json_error($result->get_error_message());
        } else {
            \wp_send_json_success(array(
                'message' => \esc_html__('Version created successfully', 'kura-ai'),
                'version_id' => $result
            ));
        }
    }

    public function ajax_rollback_version() {
        \check_ajax_referer('kura_ai_nonce', 'nonce');
        
        if (!\current_user_can('manage_options')) {
            \wp_die(\esc_html__('Insufficient permissions', 'kura-ai'));
        }

        $version_id = intval($_POST['version_id']);
        $result = $this->rollback_version($version_id);

        if (\is_wp_error($result)) {
            \wp_send_json_error($result->get_error_message());
        } else {
            \wp_send_json_success(\esc_html__('File rolled back successfully', 'kura-ai'));
        }
    }

    public function ajax_compare_versions() {
        \check_ajax_referer('kura_ai_nonce', 'nonce');
        
        if (!\current_user_can('manage_options')) {
            \wp_die(\esc_html__('Insufficient permissions', 'kura-ai'));
        }

        $version_id_1 = intval($_POST['version_id_1']);
        $version_id_2 = intval($_POST['version_id_2']);
        
        $result = $this->compare_versions($version_id_1, $version_id_2);

        if (\is_wp_error($result)) {
            \wp_send_json_error($result->get_error_message());
        } else {
            \wp_send_json_success($result);
        }
    }

    public function ajax_get_chart_data() {
        \check_ajax_referer('kura_ai_nonce', 'nonce');
        
        if (!\current_user_can('manage_options')) {
            \wp_die(\esc_html__('Insufficient permissions', 'kura-ai'));
        }

        $chart_data = $this->get_chart_data();
        \wp_send_json_success($chart_data);
    }

    // Helper methods for chart data
    private function get_file_status_distribution($results) {
        $distribution = array('clean' => 0, 'suspicious' => 0, 'modified' => 0);
        
        foreach ($results as $result) {
            if (isset($distribution[$result['status']])) {
                $distribution[$result['status']]++;
            }
        }
        
        return array(
            'labels' => array_keys($distribution),
            'data' => array_values($distribution)
        );
    }

    private function get_risk_level_distribution($results) {
        $distribution = array('low' => 0, 'medium' => 0, 'high' => 0);
        
        foreach ($results as $result) {
            if (isset($distribution[$result['risk_level']])) {
                $distribution[$result['risk_level']]++;
            }
        }
        
        return array(
            'labels' => array_keys($distribution),
            'data' => array_values($distribution)
        );
    }

    private function get_file_changes_timeline($results) {
        $timeline = array();
        
        foreach ($results as $result) {
            $date = date('Y-m-d', strtotime($result['scan_date']));
            if (!isset($timeline[$date])) {
                $timeline[$date] = 0;
            }
            $timeline[$date]++;
        }
        
        return array(
            'labels' => array_keys($timeline),
            'data' => array_values($timeline)
        );
    }

    private function get_file_size_trends($results) {
        $sizes = array();
        
        foreach ($results as $result) {
            $date = date('Y-m-d', strtotime($result['scan_date']));
            if (!isset($sizes[$date])) {
                $sizes[$date] = 0;
            }
            $sizes[$date] += intval($result['file_size']);
        }
        
        return array(
            'labels' => array_keys($sizes),
            'data' => array_values($sizes)
        );
    }
}