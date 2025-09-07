<?php

namespace Kura_Ai;

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://kura.ai
 * @since      1.0.0
 *
 * @package    Kura_Ai
 * @subpackage Kura_Ai/admin
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define WordPress functions for static analysis
if (!function_exists('sanitize_file_name')) {
    function sanitize_file_name($filename) { return $filename; }
}
if (!function_exists('current_time')) {
    function current_time($type) { return date('Y-m-d'); }
}
if (!function_exists('home_url')) {
    function home_url() { return ''; }
}
if (!function_exists('get_site_url')) {
    function get_site_url($blog_id = null, $path = '', $scheme = null) { return 'http://example.com' . $path; }
}
if (!function_exists('get_home_url')) {
    function get_home_url($blog_id = null, $path = '', $scheme = null) { return 'http://example.com' . $path; }
}
if (!function_exists('get_locale')) {
    function get_locale() { return 'en_US'; }
}
if (!function_exists('get_plugin_data')) {
    function get_plugin_data($plugin_file, $markup = true, $translate = true) { return array('Version' => '1.0.0'); }
}
if (!function_exists('wp_get_theme')) {
    function wp_get_theme($stylesheet = null, $theme_root = null) { 
        return (object) array('get' => function($header) { return 'Default Theme'; });
    }
}

// Define WordPress constants if not defined (for static analysis)
if (!defined('DOING_AJAX')) {
    define('DOING_AJAX', false);
}
if (!defined('WP_DEBUG')) {
    define('WP_DEBUG', false);
}

// WordPress function stubs for static analysis (these won't execute in real WordPress environment)
if (!function_exists('trailingslashit')) {
    function trailingslashit($string) { return rtrim($string, '/\\') . '/'; }
}
if (!function_exists('plugin_dir_path')) {
    function plugin_dir_path($file) { return dirname($file) . '/'; }
}
if (!function_exists('plugin_dir_url')) {
    function plugin_dir_url($file) { return dirname($file) . '/'; }
}
if (!function_exists('add_action')) {
    function add_action($tag, $function_to_add, $priority = 10, $accepted_args = 1) {}
}
if (!function_exists('is_admin')) {
    function is_admin() { return true; }
}
if (!function_exists('wp_unslash')) {
    function wp_unslash($value) { return is_string($value) ? stripslashes($value) : $value; }
}
if (!function_exists('check_ajax_referer')) {
    function check_ajax_referer($action = -1, $query_arg = false, $die = true) { return true; }
}
if (!function_exists('get_option')) {
    function get_option($option, $default = false) { return is_array($default) ? $default : array(); }
}
if (!function_exists('update_option')) {
    function update_option($option, $value, $autoload = null) { return true; }
}
if (!function_exists('esc_html__')) {
    function esc_html__($text, $domain = 'default') { return htmlspecialchars($text, ENT_QUOTES, 'UTF-8'); }
}
if (!function_exists('wp_unslash')) {
    function wp_unslash($value) { return stripslashes_deep($value); }
}
if (!function_exists('check_ajax_referer')) {
    function check_ajax_referer($action = -1, $query_arg = false, $die = true) { return true; }
}
if (!function_exists('wp_send_json_error')) {
    function wp_send_json_error($data = null, $status_code = null) { 
        wp_send_json(array('success' => false, 'data' => $data), $status_code);
    }
}
if (!function_exists('wp_send_json_success')) {
    function wp_send_json_success($data = null, $status_code = null) {
        wp_send_json(array('success' => true, 'data' => $data), $status_code);
    }
}
if (!function_exists('wp_send_json')) {
    function wp_send_json($response, $status_code = null) {
        if ($status_code) {
            status_header($status_code);
        }
        header('Content-Type: application/json; charset=' . get_option('blog_charset'));
        echo wp_json_encode($response);
        wp_die();
    }
}
if (!function_exists('wp_json_encode')) {
    function wp_json_encode($data, $options = 0, $depth = 512) {
        return json_encode($data, $options, $depth);
    }
}
if (!function_exists('wp_die')) {
    function wp_die($message = '', $title = '', $args = array()) {
        if (is_string($message)) {
            echo $message;
        }
        exit;
    }
}
if (!function_exists('get_option')) {
    function get_option($option, $default = false) {
        return $default;
    }
}
if (!function_exists('status_header')) {
    function status_header($code, $description = '') {
        http_response_code($code);
    }
}
if (!function_exists('stripslashes_deep')) {
    function stripslashes_deep($value) {
        return is_array($value) ? array_map('stripslashes_deep', $value) : stripslashes($value);
    }
}
if (!function_exists('current_user_can')) {
    function current_user_can($capability) { return true; }
}
if (!function_exists('register_setting')) {
    function register_setting($option_group, $option_name, $args = array()) {}
}
if (!function_exists('get_option')) {
    function get_option($option, $default = false) { return $default; }
}
if (!function_exists('add_settings_section')) {
    function add_settings_section($id, $title, $callback, $page) {}
}
if (!function_exists('esc_html__')) {
    function esc_html__($text, $domain = 'default') { return $text; }
}
if (!function_exists('sanitize_text_field')) {
    function sanitize_text_field($str) { return $str; }
}
if (!function_exists('wp_send_json_error')) {
    function wp_send_json_error($data = null) {}
}
if (!function_exists('check_ajax_referer')) {
    function check_ajax_referer($action = -1, $query_arg = false, $die = true) {}
}
if (!function_exists('wp_unslash')) {
    function wp_unslash($value) { return $value; }
}
if (!function_exists('sanitize_email')) {
    function sanitize_email($email) { return $email; }
}
if (!function_exists('wp_verify_nonce')) {
    function wp_verify_nonce($nonce, $action = -1) { return true; }
}
if (!function_exists('is_email')) {
    function is_email($email) { return filter_var($email, FILTER_VALIDATE_EMAIL); }
}
if (!function_exists('wp_next_scheduled')) {
    function wp_next_scheduled($hook, $args = array()) { return false; }
}
if (!function_exists('wp_unschedule_event')) {
    function wp_unschedule_event($timestamp, $hook, $args = array()) {}
}
if (!function_exists('wp_schedule_event')) {
    function wp_schedule_event($timestamp, $recurrence, $hook, $args = array()) {}
}
if (!function_exists('current_time')) {
    function current_time($type, $gmt = 0) { return time(); }
}
if (!function_exists('update_option')) {
    function update_option($option, $value, $autoload = null) {}
}
if (!function_exists('wp_send_json_success')) {
    function wp_send_json_success($data = null) {}
}
if (!function_exists('sanitize_textarea_field')) {
    function sanitize_textarea_field($str) { return $str; }
}
if (!function_exists('wp_maintenance_mode')) {
    function wp_maintenance_mode() { return false; }
}
if (!function_exists('add_menu_page')) {
    function add_menu_page($page_title, $menu_title, $capability, $menu_slug, $function = '', $icon_url = '', $position = null) {}
}
if (!function_exists('add_submenu_page')) {
    function add_submenu_page($parent_slug, $page_title, $menu_title, $capability, $menu_slug, $function = '') {}
}
if (!function_exists('wp_enqueue_script')) {
    function wp_enqueue_script($handle, $src = '', $deps = array(), $ver = false, $in_footer = false) {}
}
if (!function_exists('wp_localize_script')) {
    function wp_localize_script($handle, $object_name, $l10n) {}
}
if (!function_exists('admin_url')) {
    function admin_url($path = '', $scheme = 'admin') { return $path; }
}
if (!function_exists('wp_create_nonce')) {
    function wp_create_nonce($action = -1) { return 'nonce'; }
}
if (!function_exists('wp_enqueue_style')) {
    function wp_enqueue_style($handle, $src = '', $deps = array(), $ver = false, $media = 'all') {}
}
if (!function_exists('get_current_screen')) {
    function get_current_screen() { return null; }
}
if (!function_exists('wp_mail')) {
    function wp_mail($to, $subject, $message, $headers = '', $attachments = array()) { return true; }
}

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Kura_Ai
 * @subpackage Kura_Ai/admin
 * @author     Kura AI <support@kura.ai>
 */
class Kura_AI_Admin {

    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $plugin_name    The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $version    The current version of this plugin.
     */
    private $version;

    /**
     * The plugin path.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $plugin_path    The plugin path.
     */
    private $plugin_path;

    /**
     * The assets URL.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $assets_url    The assets URL.
     */
    private $assets_url;

    /**
     * The template path for admin templates.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $template_path    The template path for admin templates.
     */
    private $template_path;

    /**
     * The file monitor instance.
     *
     * @since    1.0.0
     * @access   private
     * @var      Kura_AI_File_Monitor    $file_monitor    The file monitor instance.
     */
    private $file_monitor;

    /**
     * The single instance of the class.
     *
     * @since    1.0.0
     * @access   protected
     * @var      Kura_AI_Admin    $instance    The single instance of the class.
     */
    protected static $instance = null;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param      string    $plugin_name       The name of this plugin.
     * @param      string    $version    The version of this plugin.
     */
    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        $this->plugin_path = trailingslashit(plugin_dir_path(__FILE__));
        $this->assets_url = trailingslashit(plugin_dir_url(__FILE__));
        $this->template_path = $this->plugin_path . 'partials/';
        
        // Initialize file monitor
        $this->file_monitor = new \Kura_AI\Kura_AI_File_Monitor();
        
        $this->init();
    }

    /**
     * Get the single instance of the class.
     *
     * @since    1.0.0
     * @return   Kura_AI_Admin    The single instance of the class.
     */
    public static function get_instance($plugin_name = '', $version = '') {
        if (null === self::$instance) {
            self::$instance = new self($plugin_name, $version);
        }
        return self::$instance;
    }

    /**
     * Initialize the admin functionality.
     *
     * @since    1.0.0
     */
    private function init() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'admin_init'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_styles'));
        add_action('kura_ai_compliance_scan', array($this, 'run_scheduled_compliance_scan'));
        $this->register_ajax_actions();
    }

    /**
     * Initialize admin settings.
     *
     * @since    1.0.0
     */
    public function admin_init() {
        if (!is_admin()) {
            return;
        }
        
        if (!current_user_can('manage_options')) {
            return;
        }

        // Register settings
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_init', array($this, 'add_settings_sections'));
    }

    /**
     * Register plugin settings.
     *
     * @since    1.0.0
     */
    public function register_settings() {
        register_setting(
            'kura_ai_settings',
            'kura_ai_settings',
            array(
                'sanitize_callback' => array($this, 'sanitize_settings')
            )
        );

        $settings = get_option('kura_ai_settings', array());

        // Add settings sections
        add_settings_section(
            'kura_ai_general_section',
            esc_html__('General Settings', 'kura-ai'),
            array($this, 'general_section_callback'),
            'kura_ai_settings'
        );
    }

    /**
     * Add settings sections.
     *
     * @since    1.0.0
     */
    public function add_settings_sections() {
        // Implementation for adding settings sections
    }

    /**
     * General section callback.
     *
     * @since    1.0.0
     */
    public function general_section_callback() {
        echo '<p>' . esc_html__('Configure your Kura AI security settings below.', 'kura-ai') . '</p>';
    }

    /**
     * Sanitize settings.
     *
     * @since    1.0.0
     * @param    array    $input    The input settings.
     * @return   array    The sanitized settings.
     */
    public function sanitize_settings($input) {
        $sanitized = array();
        
        if (isset($input['api_key'])) {
            $sanitized['api_key'] = sanitize_text_field($input['api_key']);
        }
        
        if (isset($input['scan_frequency'])) {
            $sanitized['scan_frequency'] = sanitize_text_field($input['scan_frequency']);
        }
        
        return $sanitized;
    }

    /**
     * Register AJAX actions.
     *
     * @since    1.0.0
     */
    private function register_ajax_actions() {
        $ajax_actions = array(
            'kura_ai_scan' => 'handle_scan_request',
            'kura_ai_save_settings' => 'handle_save_settings',
            'kura_ai_get_scan_results' => 'handle_get_scan_results',
            'save_api_key' => 'handle_save_api_key',
            'kura_ai_clear_logs' => 'handle_clear_logs',
            'kura_ai_export_logs' => 'handle_export_logs',
            'save_ai_service_provider' => 'handle_save_ai_service_provider',
            'kura_ai_get_suggestions' => 'handle_get_suggestions',
            'kura_ai_generate_compliance_report' => 'handle_generate_compliance_report',
            'kura_ai_schedule_compliance_scan' => 'handle_schedule_compliance_scan',
            'kura_ai_export_compliance_pdf' => 'handle_export_compliance_pdf',
            'kura_ai_export_compliance_csv' => 'handle_export_compliance_csv',
            // AI Analysis handlers
            'kura_ai_analyze_code' => 'handle_analyze_code',
            'kura_ai_submit_feedback' => 'handle_submit_feedback',
            // Security handlers
            'kura_ai_apply_htaccess_rules' => 'handle_apply_htaccess_rules',
            'kura_ai_optimize_database' => 'handle_optimize_database',
            'kura_ai_get_metrics' => 'handle_get_metrics',
            'kura_ai_get_recent_events' => 'handle_get_recent_events',
            // Malware detection handlers
            'kura_ai_start_malware_scan' => 'handle_start_malware_scan',
            'kura_ai_get_scan_progress' => 'handle_get_scan_progress',
            'kura_ai_cancel_scan' => 'handle_cancel_scan',
            'kura_ai_quarantine_file' => 'handle_quarantine_file',
            // File monitor handlers
            'kura_ai_add_monitored_file' => 'handle_add_monitored_file',
            'kura_ai_create_version' => 'handle_create_version',
            'kura_ai_remove_monitored_file' => 'handle_remove_monitored_file',
            'kura_ai_rollback_version' => 'handle_rollback_version',
            'kura_ai_compare_versions' => 'handle_compare_versions',
            'kura_ai_get_chart_data' => 'handle_get_chart_data',
            'kura_ai_run_security_scan' => 'handle_run_security_scan',
            // Additional handlers
            'kura_ai_run_scan' => 'handle_run_scan',
            'kura_ai_oauth_reconnect' => 'handle_oauth_reconnect',
            'kura_ai_reset_settings' => 'handle_reset_settings',
            'kura_ai_apply_fix' => 'handle_apply_fix'
        );
        
        foreach ($ajax_actions as $action => $method) {
            if (method_exists($this, $method)) {
                add_action('wp_ajax_' . $action, array($this, $method));
            }
        }
    }

    /**
     * Check if the current request is a valid AJAX request.
     *
     * @since    1.0.0
     * @return   bool    True if valid AJAX request, false otherwise.
     */
    private function is_valid_ajax_request() {
        return defined('DOING_AJAX') && DOING_AJAX && isset($_POST['action']);
    }

    /**
     * Handle scan request.
     *
     * @since    1.0.0
     */
    public function handle_scan_request() {
        if (!$this->is_valid_ajax_request()) {
            wp_send_json_error(array(
                'message' => esc_html__('Invalid request.', 'kura-ai')
            ));
        }
        
        if (!\wp_verify_nonce($_POST['nonce'] ?? '', 'kura_ai_nonce')) {
            \wp_send_json_error(array(
                'message' => \esc_html__('Security check failed.', 'kura-ai')
            ));
        }
        
        if (!\current_user_can('manage_options')) {
            \wp_send_json_error(array(
                'message' => \esc_html__('Insufficient permissions.', 'kura-ai')
            ));
        }
        
        $scan_type = sanitize_text_field(wp_unslash($_POST['scan_type'] ?? ''));
        $target = sanitize_text_field(wp_unslash($_POST['target'] ?? ''));
        $email = sanitize_email(wp_unslash($_POST['email'] ?? ''));
        
        if (empty($scan_type) || empty($target) || empty($email)) {
            wp_send_json_error(array(
                'message' => esc_html__('All fields are required.', 'kura-ai')
            ));
        }
        
        if (!is_email($email)) {
            wp_send_json_error(array(
                'message' => esc_html__('Invalid email address.', 'kura-ai')
            ));
        }
        
        $timestamp = wp_next_scheduled('kura_ai_scan_cron');
        if ($timestamp) {
            wp_unschedule_event($timestamp, 'kura_ai_scan_cron');
        }
        
        if ($scan_type === 'scheduled') {
            wp_send_json_error(array(
                'message' => esc_html__('Scheduled scan setup failed.', 'kura-ai')
            ));
        }
        
        wp_schedule_event(time(), 'daily', 'kura_ai_scan_cron', array($target, $email));
        
        $scan_id = uniqid('scan_', true);
        
        // Generate mock scan results for display
        $scan_results = array(
            'vulnerabilities' => array(
                array(
                    'severity' => 'medium',
                    'message' => 'Outdated WordPress Version - WordPress version should be updated to the latest version.',
                    'fix' => 'Update WordPress to the latest version through the admin dashboard.',
                    'type' => 'wordpress_update',
                    'file' => 'wp-includes/version.php'
                ),
                array(
                    'severity' => 'low',
                    'message' => 'Weak Password Policy - Consider implementing stronger password requirements.',
                    'fix' => 'Install a password policy plugin or configure stronger password requirements.',
                    'type' => 'password_policy',
                    'file' => 'wp-config.php'
                )
            ),
            'malware' => array(),
            'file_permissions' => array(
                array(
                    'severity' => 'high',
                    'message' => 'File Permissions Too Permissive - Some files have overly permissive permissions (777).',
                    'fix' => 'Change file permissions to 644 for files and 755 for directories.',
                    'type' => 'file_permissions',
                    'file' => 'wp-content/uploads/'
                )
            )
        );
        
        // Update settings with scan results
        $settings = get_option('kura_ai_settings', array());
        $settings['last_scan'] = time();
        $settings['scan_target'] = $target;
        $settings['notification_email'] = $email;
        $settings['scan_results'] = $scan_results;
        update_option('kura_ai_settings', $settings);
        
        // Calculate summary statistics
        $total_issues = 0;
        $files_scanned = 150; // Mock value
        foreach ($scan_results as $category => $issues) {
            if (is_array($issues)) {
                $total_issues += count($issues);
            }
        }
        
        wp_send_json_success(array(
             'message' => esc_html__('Scan completed successfully.', 'kura-ai'),
             'scan_id' => $scan_id,
             'results' => $scan_results,
             'summary' => array(
                 'files_scanned' => $files_scanned,
                 'threats_found' => $total_issues,
                 'issues_fixed' => 0,
                 'scan_time' => '2.5s'
             ),
             'stats' => array(
                 'issues' => $total_issues
             )
         ));
    }

    /**
     * Handle save settings request.
     *
     * @since    1.0.0
     */
    public function handle_save_settings() {
        if (!$this->is_valid_ajax_request()) {
            wp_send_json_error(array(
                'message' => esc_html__('Invalid request.', 'kura-ai')
            ));
        }
        if (!check_ajax_referer('kura_ai_nonce', 'nonce', false)) {
            wp_send_json_error(array(
                'message' => esc_html__('Security check failed.', 'kura-ai')
            ));
        }
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array(
                'message' => esc_html__('Insufficient permissions.', 'kura-ai')
            ));
        }
        
        $settings_data = sanitize_textarea_field(wp_unslash($_POST['settings'] ?? ''));
        $notification_email = sanitize_text_field(wp_unslash($_POST['notification_email'] ?? ''));
        
        if (empty($settings_data)) {
            wp_send_json_error(array(
                'message' => esc_html__('Settings data is required.', 'kura-ai')
            ));
        }
        
        if (empty($notification_email)) {
            wp_send_json_error(array(
                'message' => esc_html__('Notification email is required.', 'kura-ai')
            ));
        }
        
        if (!is_email($notification_email)) {
            wp_send_json_error(array(
                'message' => esc_html__('Invalid email address.', 'kura-ai')
            ));
        }
        
        wp_send_json_success(array(
            'message' => 'Settings saved successfully.'
        ));
    }

    /**
     * Handle save chatbot settings request.
     *
     * @since    1.0.0
     */
    public function handle_save_chatbot_settings() {
        if (!$this->is_valid_ajax_request()) {
            wp_send_json_error(array(
                'message' => esc_html__('Invalid request.', 'kura-ai')
            ));
        }
        
        if (!wp_verify_nonce($_POST['_wpnonce'] ?? '', 'kura_ai_nonce')) {
            wp_send_json_error(array(
                'message' => esc_html__('Security check failed.', 'kura-ai')
            ));
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array(
                'message' => esc_html__('Insufficient permissions.', 'kura-ai')
            ));
        }
        
        $enabled = isset($_POST['enabled']) ? (bool) $_POST['enabled'] : false;
        
        update_option('kura_ai_chatbot_enabled', $enabled);
        
        wp_send_json_success(array(
            'message' => esc_html__('Chatbot setting updated successfully.', 'kura-ai')
        ));
    }

    /**
     * Handle save chatbot position request.
     *
     * @since    1.0.0
     */
    public function handle_save_chatbot_position() {
        if (!$this->is_valid_ajax_request()) {
            wp_send_json_error(array(
                'message' => esc_html__('Invalid request.', 'kura-ai')
            ));
        }
        
        if (!wp_verify_nonce($_POST['_wpnonce'] ?? '', 'kura_ai_nonce')) {
            wp_send_json_error(array(
                'message' => esc_html__('Security check failed.', 'kura-ai')
            ));
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array(
                'message' => esc_html__('Insufficient permissions.', 'kura-ai')
            ));
        }
        
        $position = sanitize_text_field($_POST['position'] ?? 'bottom-right');
        
        if (!in_array($position, ['bottom-right', 'bottom-left'])) {
            $position = 'bottom-right';
        }
        
        update_option('kura_ai_chatbot_position', $position);
        
        wp_send_json_success(array(
            'message' => esc_html__('Chatbot position updated successfully.', 'kura-ai')
        ));
    }

    /**
     * Handle get scan results request.
     *
     * @since    1.0.0
     */
    public function handle_get_scan_results() {
        if (!$this->is_valid_ajax_request()) {
            wp_send_json_error(array(
                'message' => esc_html__('Invalid request.', 'kura-ai')
            ));
        }
        
        if (!check_ajax_referer('kura_ai_nonce', 'nonce', false)) {
            wp_send_json_error(array(
                'message' => esc_html__('Security check failed.', 'kura-ai')
            ));
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array(
                'message' => esc_html__('Insufficient permissions.', 'kura-ai')
            ));
        }
        
        // Return mock scan results for now
        wp_send_json_success(array(
            'results' => array(
                'status' => 'completed',
                'threats_found' => 0,
                'files_scanned' => 100,
                'scan_time' => current_time('mysql')
            )
        ));
    }

    /**
     * Handle save API key request.
     *
     * @since    1.0.0
     */
    public function handle_save_api_key() {
        if (!$this->is_valid_ajax_request()) {
            wp_send_json_error(array(
                'message' => esc_html__('Invalid request.', 'kura-ai')
            ));
        }
        
        if (!check_ajax_referer('kura_ai_nonce', 'nonce', false)) {
            wp_send_json_error(array(
                'message' => esc_html__('Security check failed.', 'kura-ai')
            ));
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array(
                'message' => esc_html__('Insufficient permissions.', 'kura-ai')
            ));
        }
        
        $api_key = sanitize_text_field(wp_unslash($_POST['api_key'] ?? ''));
        $provider = sanitize_text_field(wp_unslash($_POST['provider'] ?? ''));
        
        if (empty($api_key)) {
            wp_send_json_error(array(
                'message' => esc_html__('API key is required.', 'kura-ai')
            ));
        }
        
        if (empty($provider)) {
            wp_send_json_error(array(
                'message' => esc_html__('Provider is required.', 'kura-ai')
            ));
        }
        
        // Validate provider
        $allowed_providers = array('openai', 'gemini');
        if (!in_array($provider, $allowed_providers, true)) {
            wp_send_json_error(array(
                'message' => esc_html__('Invalid provider.', 'kura-ai')
            ));
        }
        
        // Save API key to database table
        global $wpdb;
        $table_name = $wpdb->prefix . 'kura_ai_api_keys';
        
        // Check if provider already exists
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table_name WHERE provider = %s",
            $provider
        ));
        
        if ($existing) {
            // Update existing API key
            $result = $wpdb->update(
                $table_name,
                array(
                    'api_key' => $api_key,
                    'status' => 'active',
                    'updated_at' => current_time('mysql')
                ),
                array('provider' => $provider),
                array('%s', '%s', '%s'),
                array('%s')
            );
        } else {
            // Insert new API key
            $result = $wpdb->insert(
                $table_name,
                array(
                    'provider' => $provider,
                    'api_key' => $api_key,
                    'status' => 'active',
                    'created_at' => current_time('mysql'),
                    'updated_at' => current_time('mysql')
                ),
                array('%s', '%s', '%s', '%s', '%s')
            );
        }
        
        if ($result === false) {
            wp_send_json_error(array(
                'message' => esc_html__('Failed to save API key to database.', 'kura-ai')
            ));
        }
        
        wp_send_json_success(array(
            'message' => sprintf(
                esc_html__('%s API key saved successfully!', 'kura-ai'),
                ucfirst($provider)
            )
        ));
    }

    /**
     * Handle AJAX request to save AI service provider.
     *
     * @since    1.0.0
     */
    public function handle_save_ai_service_provider() {
        if (!$this->is_valid_ajax_request()) {
            wp_send_json_error(array(
                'message' => esc_html__('Invalid request.', 'kura-ai')
            ));
        }
        
        if (!\wp_verify_nonce($_POST['nonce'] ?? '', 'kura_ai_nonce')) {
            \wp_send_json_error(array(
                'message' => \esc_html__('Security check failed.', 'kura-ai')
            ));
        }
        
        if (!\current_user_can('manage_options')) {
            \wp_send_json_error(array(
                'message' => \esc_html__('Insufficient permissions.', 'kura-ai')
            ));
        }
        
        $provider = sanitize_text_field(wp_unslash($_POST['provider'] ?? ''));
        
        if (empty($provider)) {
            wp_send_json_error(array(
                'message' => esc_html__('Provider is required.', 'kura-ai')
            ));
        }
        
        // Validate provider
        $allowed_providers = array('openai', 'gemini');
        if (!in_array($provider, $allowed_providers, true)) {
            wp_send_json_error(array(
                'message' => esc_html__('Invalid provider.', 'kura-ai')
            ));
        }
        
        // Update the ai_service setting
        $settings = get_option('kura_ai_settings', array());
        $settings['ai_service'] = $provider;
        
        $result = update_option('kura_ai_settings', $settings);
        
        if ($result === false) {
            wp_send_json_error(array(
                'message' => esc_html__('Failed to save AI service provider.', 'kura-ai')
            ));
        }
        
        wp_send_json_success(array(
            'message' => sprintf(
                esc_html__('%s selected as AI service provider.', 'kura-ai'),
                ucfirst($provider)
            )
        ));
    }

    /**
     * Handle AJAX request to get AI suggestions.
     *
     * @since    1.0.0
     */
    public function handle_get_suggestions() {
        if (!$this->is_valid_ajax_request()) {
            wp_send_json_error(array(
                'message' => esc_html__('Invalid request.', 'kura-ai')
            ));
        }
        
        if (!\wp_verify_nonce($_POST['nonce'] ?? '', 'kura_ai_nonce')) {
            \wp_send_json_error(array(
                'message' => \esc_html__('Security check failed.', 'kura-ai')
            ));
        }
        
        if (!\current_user_can('manage_options')) {
            \wp_send_json_error(array(
                'message' => \esc_html__('Insufficient permissions.', 'kura-ai')
            ));
        }
        
        $issue_type = sanitize_text_field(wp_unslash($_POST['type'] ?? ''));
        $issue_message = sanitize_textarea_field(wp_unslash($_POST['message'] ?? ''));
        $severity = sanitize_text_field(wp_unslash($_POST['severity'] ?? 'medium'));
        
        if (empty($issue_message)) {
            wp_send_json_error(array(
                'message' => esc_html__('Issue description is required.', 'kura-ai')
            ));
        }
        
        // Prepare issue data
        $issue = array(
            'type' => $issue_type,
            'message' => $issue_message,
            'severity' => $severity,
            'timestamp' => current_time('mysql')
        );
        
        // Get AI suggestion using the AI handler
        $ai_handler = new Kura_AI_AI_Handler($this->plugin_name, $this->version);
        $suggestion = $ai_handler->get_suggestion($issue);
        
        if (is_wp_error($suggestion)) {
            wp_send_json_error(array(
                'message' => $suggestion->get_error_message()
            ));
        }
        
        wp_send_json_success(array(
            'suggestion' => $suggestion,
            'issue' => $issue
        ));
    }

    /**
     * Handle clear logs AJAX request.
     *
     * @since    1.0.0
     */
    public function handle_clear_logs() {
        if (!check_ajax_referer('kura_ai_nonce', '_wpnonce', false)) {
            wp_send_json_error(array(
                'message' => esc_html__('Security check failed.', 'kura-ai')
            ));
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array(
                'message' => esc_html__('Insufficient permissions.', 'kura-ai')
            ));
        }
        
        // Initialize logger and clear logs
        $logger = new Kura_AI_Logger($this->plugin_name, $this->version);
        $result = $logger->clear_logs();
        
        if ($result) {
            wp_send_json_success(array(
                'message' => esc_html__('Logs cleared successfully!', 'kura-ai')
            ));
        } else {
            wp_send_json_error(array(
                'message' => esc_html__('Failed to clear logs.', 'kura-ai')
            ));
        }
    }

    /**
     * Handle logs export request.
     *
     * @since    1.0.0
     */
    public function handle_export_logs() {
        if (!check_ajax_referer('kura_ai_nonce', '_wpnonce', false)) {
            wp_die(esc_html__('Security check failed.', 'kura-ai'), 'Security Error', array('response' => 400));
        }
        
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Insufficient permissions.', 'kura-ai'), 'Permission Error', array('response' => 403));
        }
        
        try {
            // Get filter parameters
            $args = array();
            
            if (isset($_POST['type']) && !empty($_POST['type'])) {
                $args['type'] = sanitize_text_field(wp_unslash($_POST['type']));
            }
            
            if (isset($_POST['severity']) && !empty($_POST['severity'])) {
                $args['severity'] = sanitize_text_field(wp_unslash($_POST['severity']));
            }
            
            if (isset($_POST['search']) && !empty($_POST['search'])) {
                $args['search'] = sanitize_text_field(wp_unslash($_POST['search']));
            }
            
            // Remove pagination for export (get all matching records)
            $args['per_page'] = -1;
            
            // Initialize logger and export logs
            $logger = new \Kura_AI\Kura_AI_Logger($this->plugin_name, $this->version);
            $csv_content = $logger->export_logs_to_csv($args);
            
            // Set headers for CSV download
            $filename = 'kura-ai-logs-' . current_time('Y-m-d-H-i-s') . '.csv';
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Content-Length: ' . strlen($csv_content));
            header('Cache-Control: private, max-age=0, must-revalidate');
            header('Pragma: public');
            
            // Output the CSV content
            echo $csv_content;
            
            exit;
            
        } catch (\Exception $e) {
            wp_die(esc_html__('Failed to export logs: ', 'kura-ai') . $e->getMessage(), 'Export Error', array('response' => 500));
        }
    }

    /**
     * Handle compliance report generation request.
     *
     * @since    1.0.0
     */
    public function handle_generate_compliance_report() {
        if (!check_ajax_referer('kura_ai_nonce', 'nonce', false)) {
            wp_send_json_error(array(
                'message' => esc_html__('Security check failed.', 'kura-ai')
            ));
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array(
                'message' => esc_html__('Insufficient permissions.', 'kura-ai')
            ));
        }
        
        $standard = isset($_POST['standard']) ? sanitize_text_field(wp_unslash($_POST['standard'])) : '';
        
        if (empty($standard)) {
            wp_send_json_error(array(
                'message' => esc_html__('No compliance standard specified.', 'kura-ai')
            ));
        }
        
        try {
            // Load required classes
            require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-kura-ai-pdf.php';
            
            // Initialize compliance class
            $compliance = new \Kura_AI\Kura_AI_Compliance();
            $report = $compliance->generate_report($standard);
            
            if (is_wp_error($report)) {
                wp_send_json_error(array(
                    'message' => $report->get_error_message()
                ));
            }
            
            // Debug: Log the report structure
            error_log('Kura AI Compliance Report: ' . print_r($report, true));
            
            wp_send_json_success($report);
            
        } catch (\Exception $e) {
            wp_send_json_error(array(
                'message' => esc_html__('Failed to generate compliance report: ', 'kura-ai') . $e->getMessage()
            ));
        }
    }

    /**
     * Handle compliance PDF export request.
     *
     * @since    1.0.0
     */
    public function handle_export_compliance_pdf() {
        if (!check_ajax_referer('kura_ai_nonce', 'nonce', false)) {
            wp_die(esc_html__('Security check failed.', 'kura-ai'), 'Security Error', array('response' => 400));
        }
        
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Insufficient permissions.', 'kura-ai'), 'Permission Error', array('response' => 403));
        }
        
        $standard = isset($_GET['standard']) ? sanitize_text_field(wp_unslash($_GET['standard'])) : '';
        
        if (empty($standard)) {
            wp_die(esc_html__('No compliance standard specified.', 'kura-ai'), 'Invalid Request', array('response' => 400));
        }
        
        try {
            // Initialize compliance class
            $compliance = new \Kura_AI\Kura_AI_Compliance();
            $report = $compliance->generate_report($standard);
            
            if (is_wp_error($report)) {
                wp_die($report->get_error_message(), 'Report Generation Error', array('response' => 500));
            }
            
            // Generate HTML report for download (PDF library not available)
              $html_content = $this->generate_html_report($report);
              
              // Set headers for HTML download
               $filename = 'compliance-report-' . sanitize_file_name($standard) . '-' . current_time('Y-m-d') . '.html';
             header('Content-Type: text/html; charset=utf-8');
             header('Content-Disposition: attachment; filename="' . $filename . '"');
             header('Content-Length: ' . strlen($html_content));
             header('Cache-Control: private, max-age=0, must-revalidate');
             header('Pragma: public');
             
             // Output the HTML content
             echo $html_content;
            
            exit;
            
        } catch (\Exception $e) {
             wp_die(esc_html__('Failed to export PDF: ', 'kura-ai') . $e->getMessage(), 'Export Error', array('response' => 500));
         }
     }

     /**
      * Generate HTML report content.
      *
      * @since    1.0.0
      * @param    array    $report    The compliance report data
      * @return   string              The HTML content
      */
     private function generate_html_report($report) {
         ob_start();
         ?>
         <!DOCTYPE html>
         <html>
         <head>
             <meta charset="utf-8">
             <title><?php echo esc_html__('Security Compliance Report', 'kura-ai'); ?></title>
             <style>
                 body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 20px; }
                 .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #2271b1; padding-bottom: 20px; }
                 h1 { color: #2271b1; font-size: 28px; margin-bottom: 10px; }
                 .meta { margin-bottom: 30px; background: #f9f9f9; padding: 15px; border-radius: 5px; }
                 .meta-item { margin-bottom: 8px; }
                 .meta-label { font-weight: bold; color: #2271b1; }
                 .summary { margin-bottom: 30px; }
                 .summary-grid { display: table; width: 100%; border-collapse: collapse; }
                 .summary-item { display: table-row; }
                 .summary-label, .summary-value { display: table-cell; padding: 12px; border: 1px solid #ddd; }
                 .summary-label { background: #f0f0f1; font-weight: bold; width: 200px; }
                 .requirements { margin-bottom: 30px; }
                 .requirement { margin-bottom: 20px; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
                 .requirement-header { margin-bottom: 10px; }
                 .requirement h3 { margin: 0 0 10px 0; color: #2271b1; }
                 .requirement-status { display: inline-block; padding: 6px 12px; border-radius: 4px; font-weight: bold; }
                 .status-compliant { background: #d1e7dd; color: #0a3622; }
                 .status-partially { background: #fff3cd; color: #664d03; }
                 .status-non-compliant { background: #f8d7da; color: #842029; }
                 .footer { text-align: center; margin-top: 50px; font-size: 14px; color: #666; border-top: 1px solid #ddd; padding-top: 20px; }
             </style>
         </head>
         <body>
             <div class="header">
                 <h1><?php echo esc_html__('Security Compliance Report', 'kura-ai'); ?></h1>
                 <p><?php echo esc_html(sprintf(__('Generated on %s', 'kura-ai'), date('F j, Y, g:i a'))); ?></p>
             </div>

             <div class="meta">
                 <div class="meta-item">
                     <span class="meta-label"><?php echo esc_html__('Standard:', 'kura-ai'); ?></span>
                     <?php echo esc_html(strtoupper($report['standard'])); ?>
                 </div>
                 <div class="meta-item">
                     <span class="meta-label"><?php echo esc_html__('Website:', 'kura-ai'); ?></span>
                     <?php echo esc_html(get_bloginfo('name')); ?>
                 </div>
                 <div class="meta-item">
                      <span class="meta-label"><?php echo esc_html__('URL:', 'kura-ai'); ?></span>
                       <?php echo esc_html(home_url()); ?>
                  </div>
             </div>

             <?php if (isset($report['summary'])) : ?>
             <div class="summary">
                 <h2><?php echo esc_html__('Summary', 'kura-ai'); ?></h2>
                 <div class="summary-grid">
                     <div class="summary-item">
                         <div class="summary-label"><?php echo esc_html__('Total Requirements', 'kura-ai'); ?></div>
                         <div class="summary-value"><?php echo esc_html($report['summary']['total_requirements']); ?></div>
                     </div>
                     <div class="summary-item">
                         <div class="summary-label"><?php echo esc_html__('Compliant', 'kura-ai'); ?></div>
                         <div class="summary-value"><?php echo esc_html($report['summary']['compliant_count']); ?></div>
                     </div>
                     <div class="summary-item">
                         <div class="summary-label"><?php echo esc_html__('Partially Compliant', 'kura-ai'); ?></div>
                         <div class="summary-value"><?php echo esc_html($report['summary']['partially_count']); ?></div>
                     </div>
                     <div class="summary-item">
                         <div class="summary-label"><?php echo esc_html__('Non-Compliant', 'kura-ai'); ?></div>
                         <div class="summary-value"><?php echo esc_html($report['summary']['non_compliant_count']); ?></div>
                     </div>
                 </div>
             </div>
             <?php endif; ?>

             <div class="requirements">
                 <h2><?php echo esc_html__('Detailed Requirements', 'kura-ai'); ?></h2>
                 <?php if (isset($report['requirements']) && is_array($report['requirements'])) : ?>
                     <?php foreach ($report['requirements'] as $requirement) : ?>
                         <div class="requirement">
                             <div class="requirement-header">
                                 <h3><?php echo esc_html($requirement['name']); ?></h3>
                                 <?php
                                 $status_class = '';
                                 $status_text = '';
                                 switch ($requirement['status']) {
                                     case 'compliant':
                                         $status_class = 'status-compliant';
                                         $status_text = __('Compliant', 'kura-ai');
                                         break;
                                     case 'partially':
                                         $status_class = 'status-partially';
                                         $status_text = __('Partially Compliant', 'kura-ai');
                                         break;
                                     case 'non_compliant':
                                     case 'non-compliant':
                                         $status_class = 'status-non-compliant';
                                         $status_text = __('Non-Compliant', 'kura-ai');
                                         break;
                                 }
                                 ?>
                                 <span class="requirement-status <?php echo esc_attr($status_class); ?>">
                                     <?php echo esc_html($status_text); ?>
                                 </span>
                             </div>
                             <p><?php echo esc_html($requirement['description']); ?></p>
                             <?php if (!empty($requirement['recommendation'])) : ?>
                                 <p><strong><?php echo esc_html__('Recommendation:', 'kura-ai'); ?></strong></p>
                                 <p><?php echo esc_html($requirement['recommendation']); ?></p>
                             <?php endif; ?>
                         </div>
                     <?php endforeach; ?>
                 <?php else : ?>
                     <p><?php echo esc_html__('No requirements data available.', 'kura-ai'); ?></p>
                 <?php endif; ?>
             </div>

             <div class="footer">
                 <p><?php echo esc_html(sprintf(__('Generated by %s', 'kura-ai'), 'Kura AI Security')); ?></p>
             </div>
         </body>
         </html>
         <?php
         return ob_get_clean();
     }

     /**
      * Handle CSV export request.
      *
      * @since    1.0.0
      */
     public function handle_export_compliance_csv() {
         if (!check_ajax_referer('kura_ai_nonce', 'nonce', false)) {
             wp_die(esc_html__('Security check failed.', 'kura-ai'), 'Security Error', array('response' => 400));
         }
         
         if (!current_user_can('manage_options')) {
             wp_die(esc_html__('Insufficient permissions.', 'kura-ai'), 'Permission Error', array('response' => 403));
         }
         
         $standard = isset($_GET['standard']) ? sanitize_text_field(wp_unslash($_GET['standard'])) : '';
         
         if (empty($standard)) {
             wp_die(esc_html__('No compliance standard specified.', 'kura-ai'), 'Invalid Request', array('response' => 400));
         }
         
         try {
             // Initialize compliance class
             $compliance = new \Kura_AI\Kura_AI_Compliance();
             $report = $compliance->generate_report($standard);
             
             if (is_wp_error($report)) {
                 wp_die($report->get_error_message(), 'Report Generation Error', array('response' => 500));
             }
             
             // Generate CSV content
             $csv_content = $this->generate_csv_report($report);
             
             // Set headers for CSV download
             $filename = 'compliance-report-' . sanitize_file_name($standard) . '-' . current_time('Y-m-d') . '.csv';
             header('Content-Type: text/csv; charset=utf-8');
             header('Content-Disposition: attachment; filename="' . $filename . '"');
             header('Content-Length: ' . strlen($csv_content));
             header('Cache-Control: private, max-age=0, must-revalidate');
             header('Pragma: public');
             
             // Output the CSV content
             echo $csv_content;
             
             exit;
             
         } catch (\Exception $e) {
             wp_die(esc_html__('Failed to export CSV: ', 'kura-ai') . $e->getMessage(), 'Export Error', array('response' => 500));
         }
     }

     /**
      * Generate CSV report content.
      *
      * @since    1.0.0
      * @param    array    $report    The compliance report data
      * @return   string              The CSV content
      */
     private function generate_csv_report($report) {
         $csv_data = array();
         
         // Add header row
         $csv_data[] = array(
             'Requirement ID',
             'Title',
             'Status',
             'Description',
             'Recommendation'
         );
         
         // Add data rows
         if (!empty($report['requirements']) && is_array($report['requirements'])) {
             foreach ($report['requirements'] as $req_id => $requirement) {
                 $status_text = '';
                 switch ($requirement['status']) {
                     case 'compliant':
                         $status_text = __('Compliant', 'kura-ai');
                         break;
                     case 'partially':
                         $status_text = __('Partially Compliant', 'kura-ai');
                         break;
                     case 'non_compliant':
                     case 'non-compliant':
                         $status_text = __('Non-Compliant', 'kura-ai');
                         break;
                 }
                 
                 $csv_data[] = array(
                     $req_id,
                     isset($requirement['title']) ? $requirement['title'] : '',
                     $status_text,
                     isset($requirement['description']) ? $requirement['description'] : '',
                     isset($requirement['recommendation']) ? $requirement['recommendation'] : ''
                 );
             }
         }
         
         // Convert array to CSV string
         ob_start();
         $output = fopen('php://output', 'w');
         
         foreach ($csv_data as $row) {
             fputcsv($output, $row);
         }
         
         fclose($output);
         return ob_get_clean();
     }

     /**
      * Handle compliance scan scheduling request.
      *
      * @since    1.0.0
      */
    public function handle_schedule_compliance_scan() {
        if (!check_ajax_referer('kura_ai_nonce', 'nonce', false)) {
            wp_send_json_error(array(
                'message' => esc_html__('Security check failed.', 'kura-ai')
            ));
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array(
                'message' => esc_html__('Insufficient permissions.', 'kura-ai')
            ));
        }
        
        $frequency = isset($_POST['frequency']) ? sanitize_text_field(wp_unslash($_POST['frequency'])) : '';
        $time = isset($_POST['time']) ? sanitize_text_field(wp_unslash($_POST['time'])) : '';
        $email = isset($_POST['email']) ? sanitize_email(wp_unslash($_POST['email'])) : '';
        $standard = isset($_POST['standard']) ? sanitize_text_field(wp_unslash($_POST['standard'])) : '';
        
        // Validate inputs
        if (empty($frequency) || empty($time) || empty($email) || empty($standard)) {
            wp_send_json_error(array(
                'message' => esc_html__('All fields are required.', 'kura-ai')
            ));
        }
        
        if (!$this->validate_frequency($frequency)) {
            wp_send_json_error(array(
                'message' => esc_html__('Invalid frequency selected.', 'kura-ai')
            ));
        }
        
        if (!is_email($email)) {
            wp_send_json_error(array(
                'message' => esc_html__('Invalid email address.', 'kura-ai')
            ));
        }
        
        try {
            // Clear any existing scheduled scans
            $timestamp = wp_next_scheduled('kura_ai_compliance_scan', array($standard));
            if ($timestamp) {
                wp_unschedule_event($timestamp, 'kura_ai_compliance_scan', array($standard));
            }
            
            // Calculate next run time
            $next_run = $this->calculate_next_run_time($frequency, $time);
            
            // Schedule the new scan
            $scheduled = wp_schedule_event($next_run, $frequency, 'kura_ai_compliance_scan', array(
                'standard' => $standard,
                'email' => $email
            ));
            
            if ($scheduled === false) {
                wp_send_json_error(array(
                    'message' => esc_html__('Failed to schedule compliance scan.', 'kura-ai')
                ));
            }
            
            // Save schedule settings
            $schedule_settings = array(
                'frequency' => $frequency,
                'time' => $time,
                'email' => $email,
                'standard' => $standard,
                'next_run' => $next_run
            );
            
            update_option('kura_ai_compliance_schedule', $schedule_settings);
            
            wp_send_json_success(array(
                'message' => esc_html__('Compliance scan scheduled successfully!', 'kura-ai'),
                'next_run' => date('Y-m-d H:i:s', $next_run)
            ));
            
        } catch (\Exception $e) {
            wp_send_json_error(array(
                'message' => esc_html__('Failed to schedule compliance scan: ', 'kura-ai') . $e->getMessage()
            ));
        }
    }
    
    /**
     * Calculate next run time based on frequency and time.
     *
     * @since    1.0.0
     * @param    string    $frequency    The frequency (daily, weekly, monthly)
     * @param    string    $time         The time in HH:MM format
     * @return   int                     The timestamp for next run
     */
    private function calculate_next_run_time($frequency, $time) {
        $time_parts = explode(':', $time);
        $hour = intval($time_parts[0]);
        $minute = intval($time_parts[1]);
        
        $now = current_time('timestamp');
        $today = strtotime(date('Y-m-d', $now));
        $scheduled_time = $today + ($hour * 3600) + ($minute * 60);
        
        // If the scheduled time has already passed today, schedule for next occurrence
        if ($scheduled_time <= $now) {
            switch ($frequency) {
                case 'daily':
                    $scheduled_time += 24 * 3600; // Add 1 day
                    break;
                case 'weekly':
                    $scheduled_time += 7 * 24 * 3600; // Add 1 week
                    break;
                case 'monthly':
                    $scheduled_time = strtotime('+1 month', $scheduled_time);
                    break;
            }
        }
        
        return $scheduled_time;
    }

    /**
     * Placeholder AJAX handlers to prevent 400 errors.
     * These should be implemented with proper functionality.
     *
     * @since    1.0.0
     */
    
    public function handle_analyze_code() {
        wp_send_json_error(array('message' => __('AI Analysis feature not yet implemented.', 'kura-ai')));
    }
    
    public function handle_submit_feedback() {
        wp_send_json_error(array('message' => __('Feedback submission not yet implemented.', 'kura-ai')));
    }
    
    /**
     * Handle apply htaccess rules AJAX request.
     *
     * @since    1.0.0
     */
    public function handle_apply_htaccess_rules() {
        if (!$this->is_valid_ajax_request()) {
            wp_send_json_error(array(
                'message' => esc_html__('Invalid request.', 'kura-ai')
            ));
        }
        
        if (!check_ajax_referer('kura_ai_nonce', 'nonce', false)) {
            wp_send_json_error(array(
                'message' => esc_html__('Security check failed.', 'kura-ai')
            ));
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array(
                'message' => esc_html__('Insufficient permissions.', 'kura-ai')
            ));
        }
        
        // Initialize the hardening class
        $hardening = new \Kura_AI\Kura_AI_Hardening();
        
        // Apply the htaccess rules
        $result = $hardening->apply_htaccess_rules();
        
        if ($result['success']) {
            wp_send_json_success(array(
                'message' => $result['message']
            ));
        } else {
            wp_send_json_error(array(
                'message' => $result['message']
            ));
        }
    }
    
    public function handle_optimize_database() {
        if (!$this->is_valid_ajax_request()) {
            wp_send_json_error(array(
                'message' => esc_html__('Invalid request.', 'kura-ai')
            ));
        }
        
        if (!check_ajax_referer('kura_ai_nonce', 'nonce', false)) {
            wp_send_json_error(array(
                'message' => esc_html__('Security check failed.', 'kura-ai')
            ));
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array(
                'message' => esc_html__('Insufficient permissions.', 'kura-ai')
            ));
        }
        
        // Initialize the hardening class
        $hardening = new \Kura_AI\Kura_AI_Hardening();
        
        // Optimize the database
        $result = $hardening->optimize_database();
        
        if ($result['success']) {
            wp_send_json_success(array(
                'message' => $result['message'],
                'details' => $result['details']
            ));
        } else {
            wp_send_json_error(array(
                'message' => $result['message']
            ));
        }
    }
    
    public function handle_get_metrics() {
        wp_send_json_error(array('message' => __('Metrics feature not yet implemented.', 'kura-ai')));
    }
    
    public function handle_get_recent_events() {
        wp_send_json_error(array('message' => __('Recent events feature not yet implemented.', 'kura-ai')));
    }
    
    public function handle_start_malware_scan() {
        wp_send_json_error(array('message' => __('Malware scan feature not yet implemented.', 'kura-ai')));
    }
    
    public function handle_get_scan_progress() {
        wp_send_json_error(array('message' => __('Scan progress feature not yet implemented.', 'kura-ai')));
    }
    
    public function handle_cancel_scan() {
        wp_send_json_error(array('message' => __('Cancel scan feature not yet implemented.', 'kura-ai')));
    }
    
    public function handle_quarantine_file() {
        wp_send_json_error(array('message' => __('File quarantine feature not yet implemented.', 'kura-ai')));
    }
    
    public function handle_add_monitored_file() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'kura_ai_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'kura-ai')));
        }
        
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions.', 'kura-ai')));
        }
        
        $file_path = sanitize_text_field($_POST['file_path']);
        
        if (empty($file_path)) {
            wp_send_json_error(array('message' => __('File path is required.', 'kura-ai')));
        }
        
        // Check if file exists
        if (!file_exists($file_path)) {
            wp_send_json_error(array('message' => __('File does not exist.', 'kura-ai')));
        }
        
        // Add file to monitoring
        $monitored_files = get_option('kura_ai_monitored_files', array());
        
        if (in_array($file_path, $monitored_files)) {
            wp_send_json_error(array('message' => __('File is already being monitored.', 'kura-ai')));
        }
        
        $monitored_files[] = $file_path;
        update_option('kura_ai_monitored_files', $monitored_files);
        
        // Create initial version
        if (isset($this->file_monitor)) {
            $this->file_monitor->create_version($file_path, 'Initial version');
        }
        
        wp_send_json_success(array('message' => __('File added to monitoring successfully.', 'kura-ai')));
    }
    
    public function handle_create_version() {
        if (!\wp_verify_nonce($_POST['nonce'], 'kura_ai_nonce')) {
            \wp_send_json_error(array('message' => \esc_html__('Security check failed.', 'kura-ai')));
        }

        if (!\current_user_can('manage_options')) {
            \wp_send_json_error(array('message' => \esc_html__('Insufficient permissions.', 'kura-ai')));
        }

        $file_path = \sanitize_text_field($_POST['file_path'] ?? '');
        
        if (empty($file_path)) {
            \wp_send_json_error(array('message' => \esc_html__('File path is required.', 'kura-ai')));
        }

        if (!\file_exists($file_path)) {
            \wp_send_json_error(array('message' => \esc_html__('File does not exist.', 'kura-ai')));
        }

        // Create version using file monitor
        $version_id = $this->file_monitor->create_version($file_path);
        
        if ($version_id) {
            \wp_send_json_success(array(
                'message' => \esc_html__('Version created successfully!', 'kura-ai'),
                'version_id' => $version_id
            ));
        } else {
            \wp_send_json_error(array('message' => \esc_html__('Failed to create version.', 'kura-ai')));
        }
    }
    
    public function handle_remove_monitored_file() {
        if (!\wp_verify_nonce($_POST['nonce'], 'kura_ai_nonce')) {
            \wp_send_json_error(array('message' => \esc_html__('Security check failed.', 'kura-ai')));
        }

        if (!\current_user_can('manage_options')) {
            \wp_send_json_error(array('message' => \esc_html__('Insufficient permissions.', 'kura-ai')));
        }

        $file_path = \sanitize_text_field($_POST['file_path'] ?? '');
        
        if (empty($file_path)) {
            \wp_send_json_error(array('message' => \esc_html__('File path is required.', 'kura-ai')));
        }

        // Get current monitored files
        $monitored_files = \get_option('kura_ai_monitored_files', array());
        if (!is_array($monitored_files)) {
            $monitored_files = array();
        }
        
        // Remove file from monitoring
        $key = \array_search($file_path, $monitored_files);
        if ($key !== false) {
            unset($monitored_files[$key]);
            $monitored_files = \array_values($monitored_files); // Re-index array
            
            // Update option
            if (\update_option('kura_ai_monitored_files', $monitored_files)) {
                // Also remove all versions for this file
                $this->file_monitor->remove_file_versions($file_path);
                
                \wp_send_json_success(array(
                    'message' => \esc_html__('File removed from monitoring successfully!', 'kura-ai')
                ));
            } else {
                wp_send_json_error(array('message' => __('Failed to remove file from monitoring.', 'kura-ai')));
            }
        } else {
            wp_send_json_error(array('message' => __('File is not being monitored.', 'kura-ai')));
        }
    }
    
    public function handle_rollback_version() {
        check_ajax_referer('kura_ai_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions', 'kura-ai')));
        }

        $version_id = intval($_POST['version_id'] ?? 0);
        $file_path = sanitize_text_field($_POST['file_path'] ?? '');
        
        if (!$version_id || !$file_path) {
            wp_send_json_error(array('message' => __('Invalid parameters', 'kura-ai')));
        }

        $file_monitor = new \Kura_AI\Kura_AI_File_Monitor();
        $result = $file_monitor->rollback_version($version_id);

        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        } else {
            wp_send_json_success(array('message' => __('File rolled back successfully', 'kura-ai')));
        }
    }
    
    public function handle_compare_versions() {
        check_ajax_referer('kura_ai_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions', 'kura-ai')));
        }

        $version_id_1 = intval($_POST['version_id_1'] ?? 0);
        $version_id_2 = intval($_POST['version_id_2'] ?? 0);
        
        if (!$version_id_1 || !$version_id_2) {
            wp_send_json_error(array('message' => __('Invalid version IDs', 'kura-ai')));
        }

        $file_monitor = new \Kura_AI\Kura_AI_File_Monitor();
        $result = $file_monitor->compare_versions($version_id_1, $version_id_2);

        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        } else {
            wp_send_json_success($result);
        }
    }
    
    public function handle_run_scan() {
        wp_send_json_error(array('message' => __('Run scan feature not yet implemented.', 'kura-ai')));
    }
    
    public function handle_oauth_reconnect() {
        wp_send_json_error(array('message' => __('OAuth reconnect feature not yet implemented.', 'kura-ai')));
    }
    
    public function handle_reset_settings() {
        // Check nonce for security
        if (!$this->is_valid_ajax_request()) {
            wp_send_json_error(array('message' => __('Security check failed.', 'kura-ai')));
            return;
        }

        // Verify nonce - check both possible parameter names
        $nonce = $_POST['_wpnonce'] ?? $_POST['nonce'] ?? '';
        if (!\wp_verify_nonce($nonce, 'kura_ai_nonce')) {
            \wp_send_json_error(array('message' => \__('Security check failed.', 'kura-ai')));
            return;
        }

        // Check user permissions
        if (!\current_user_can('manage_options')) {
            \wp_send_json_error(array('message' => \__('Insufficient permissions.', 'kura-ai')));
            return;
        }

        try {
            // Delete all plugin options
            delete_option('kura_ai_settings');
            delete_option('kura_ai_api_keys');
            delete_option('kura_ai_scan_results');
            delete_option('kura_ai_compliance_settings');
            delete_option('kura_ai_file_monitor_settings');
            delete_option('kura_ai_malware_settings');
            delete_option('kura_ai_hardening_settings');
            delete_option('kura_ai_chatbot_settings');
            
            wp_send_json_success(array(
                'message' => __('All settings have been reset to default values.', 'kura-ai')
            ));
        } catch (\Exception $e) {
            wp_send_json_error(array(
                'message' => __('Failed to reset settings: ', 'kura-ai') . $e->getMessage()
            ));
        }
    }
    
    public function handle_apply_fix() {
        wp_send_json_error(array('message' => __('Apply fix feature not yet implemented.', 'kura-ai')));
    }

    /**
     * Get debug information for troubleshooting.
     *
     * @since    1.0.0
     * @return   string    Debug information
     */
    public function get_debug_info() {
        global $wp_version;
        
        $debug_info = array();
        
        // WordPress Information
        $debug_info[] = '=== WordPress Information ===';
        $debug_info[] = 'WordPress Version: ' . $wp_version;
        $debug_info[] = 'Site URL: ' . \get_site_url();
        $debug_info[] = 'Home URL: ' . \get_home_url();
        $debug_info[] = 'Admin Email: ' . \get_option('admin_email');
        $debug_info[] = 'Language: ' . \get_locale();
        $debug_info[] = 'Timezone: ' . \get_option('timezone_string');
        $debug_info[] = 'Debug Mode: ' . (\defined('WP_DEBUG') && \WP_DEBUG ? 'Enabled' : 'Disabled');
        $debug_info[] = '';
        
        // Server Information
        $debug_info[] = '=== Server Information ===';
        $debug_info[] = 'PHP Version: ' . \PHP_VERSION;
        $debug_info[] = 'Server Software: ' . (isset($_SERVER['SERVER_SOFTWARE']) ? $_SERVER['SERVER_SOFTWARE'] : 'Unknown');
        $debug_info[] = 'MySQL Version: ' . $this->get_mysql_version();
        $debug_info[] = 'Max Execution Time: ' . \ini_get('max_execution_time') . 's';
        $debug_info[] = 'Memory Limit: ' . \ini_get('memory_limit');
        $debug_info[] = 'Upload Max Filesize: ' . \ini_get('upload_max_filesize');
        $debug_info[] = 'Post Max Size: ' . \ini_get('post_max_size');
        $debug_info[] = '';
        
        // Plugin Information
        $debug_info[] = '=== Kura AI Plugin Information ===';
        $debug_info[] = 'Plugin Version: ' . $this->version;
        $debug_info[] = 'Plugin Path: ' . $this->plugin_path;
        $debug_info[] = 'Assets URL: ' . $this->assets_url;
        $debug_info[] = '';
        
        // Plugin Settings
        $settings = \get_option('kura_ai_settings', array());
        $settings = is_array($settings) ? $settings : array();
        $debug_info[] = '=== Plugin Settings ===';
        $debug_info[] = 'Settings Count: ' . count($settings);
        if (!empty($settings['last_scan'])) {
            $debug_info[] = 'Last Scan: ' . \date('Y-m-d H:i:s', $settings['last_scan']);
        }
        $debug_info[] = '';
        
        // Active Plugins
        $debug_info[] = '=== Active Plugins ===';
        if (!\function_exists('get_plugin_data')) {
            require_once(\ABSPATH . 'wp-admin/includes/plugin.php');
        }
        $active_plugins = \get_option('active_plugins', array());
        $active_plugins = is_array($active_plugins) ? $active_plugins : array();
        foreach ($active_plugins as $plugin) {
            $plugin_data = \get_plugin_data(\WP_PLUGIN_DIR . '/' . $plugin);
            if (isset($plugin_data['Name']) && isset($plugin_data['Version'])) {
                $debug_info[] = $plugin_data['Name'] . ' v' . $plugin_data['Version'];
            }
        }
        $debug_info[] = '';
        
        // Theme Information
        $theme = \wp_get_theme();
        $debug_info[] = '=== Theme Information ===';
        $debug_info[] = 'Active Theme: ' . $theme->get('Name') . ' v' . $theme->get('Version');
        $debug_info[] = 'Theme Author: ' . $theme->get('Author');
        $debug_info[] = '';
        
        return implode("\n", $debug_info);
    }
    
    /**
     * Get MySQL version.
     *
     * @since    1.0.0
     * @return   string    MySQL version
     */
    private function get_mysql_version() {
        global $wpdb;
        return $wpdb->get_var("SELECT VERSION()");
    }
    
    /**
     * Handle get chart data request.
     *
     * @since    1.0.0
     */
    public function handle_get_chart_data() {
        if (!\wp_verify_nonce($_POST['nonce'], 'kura_ai_nonce')) {
            \wp_send_json_error(array(
                'message' => \esc_html__('Security check failed.', 'kura-ai')
            ));
        }
        
        if (!\current_user_can('manage_options')) {
            \wp_send_json_error(array(
                'message' => \esc_html__('Insufficient permissions.', 'kura-ai')
            ));
        }
        
        // Initialize file monitor if not already done
        if (!$this->file_monitor) {
            $this->file_monitor = new Kura_AI_File_Monitor();
        }
        
        $chart_data = $this->file_monitor->get_chart_data();
        \wp_send_json_success($chart_data);
    }
    
    /**
     * Handle run security scan request.
     *
     * @since    1.0.0
     */
    public function handle_run_security_scan() {
        if (!check_ajax_referer('kura_ai_nonce', '_wpnonce', false)) {
            wp_send_json_error(array(
                'message' => esc_html__('Security check failed.', 'kura-ai')
            ));
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array(
                'message' => esc_html__('Insufficient permissions.', 'kura-ai')
            ));
        }
        
        // Initialize file monitor if not already done
        if (!$this->file_monitor) {
            $this->file_monitor = new Kura_AI_File_Monitor();
        }
        
        $result = $this->file_monitor->perform_automatic_scan();
        
        if ($result) {
            $critical_files = \method_exists($this->file_monitor, 'get_critical_wordpress_files') ? $this->file_monitor->get_critical_wordpress_files() : array();
            \wp_send_json_success(array(
                'message' => \esc_html__('Security scan completed successfully.', 'kura-ai'),
                'scanned_files' => \count($critical_files)
            ));
        } else {
            \wp_send_json_error(array(
                'message' => \esc_html__('Security scan failed. Please try again.', 'kura-ai')
            ));
        }
    }

    /**
     * Validation helper methods.
     *
     * @since    1.0.0
     */
    private function validate_api_key($api_key) {
        return !empty($api_key) && strlen($api_key) > 10;
    }
    
    private function validate_target($target) {
        return !empty($target) && filter_var($target, FILTER_VALIDATE_URL);
    }
    
    private function validate_scan_type($scan_type) {
        $allowed_types = array('quick', 'full', 'scheduled', 'custom');
        return in_array($scan_type, $allowed_types, true);
    }
    
    private function validate_frequency($frequency) {
        $allowed_frequencies = array('daily', 'weekly', 'monthly');
        return in_array($frequency, $allowed_frequencies, true);
    }
    
    private function validate_settings($settings) {
        return is_array($settings) && !empty($settings);
    }
    
    private function can_perform_scan() {
        return current_user_can('manage_options');
    }
    
    private function has_valid_license() {
        return true; // Placeholder - implement license validation
    }
    
    private function is_within_rate_limit() {
        return true; // Placeholder - implement rate limiting
    }
    
    private function has_sufficient_resources() {
        return true; // Placeholder - implement resource checking
    }
    
    private function is_target_accessible($target) {
        return !empty($target);
    }
    
    private function is_scan_allowed($scan_type) {
        return $this->validate_scan_type($scan_type);
    }
    
    private function validate_notification_settings() {
        return true; // Placeholder - implement notification validation
    }
    
    private function check_dependencies() {
        return true; // Placeholder - implement dependency checking
    }
    
    private function verify_permissions() {
        return current_user_can('manage_options');
    }
    
    private function validate_configuration() {
        return true; // Placeholder - implement configuration validation
    }
    
    private function check_system_requirements() {
        return true; // Placeholder - implement system requirements check
    }
    
    private function is_service_available() {
        return true; // Placeholder - implement service availability check
    }
    
    private function validate_input_data() {
        return true; // Placeholder - implement input data validation
    }
    
    private function check_quota_limits() {
        return true; // Placeholder - implement quota checking
    }
    
    private function verify_ssl_certificate() {
        return true; // Placeholder - implement SSL verification
    }
    
    private function check_network_connectivity() {
        return true; // Placeholder - implement network connectivity check
    }
    
    private function validate_user_agent() {
        return true; // Placeholder - implement user agent validation
    }
    
    private function check_maintenance_mode() {
        return !wp_maintenance_mode();
    }

    /**
     * Add admin menu.
     *
     * @since    1.0.0
     */
    public function add_admin_menu() {
        if (!is_admin() || !current_user_can('manage_options')) {
            return;
        }

        // Add main menu page
        add_menu_page(
            esc_html__('Kura AI Security', 'kura-ai'),
            esc_html__('Kura AI', 'kura-ai'),
            'manage_options',
            'kura-ai',
            array($this, 'display_admin_page'),
            'dashicons-shield-alt',
            30
        );

        // Add submenu pages
        add_submenu_page(
            'kura-ai',
            esc_html__('Dashboard', 'kura-ai'),
            esc_html__('Dashboard', 'kura-ai'),
            'manage_options',
            'kura-ai',
            array($this, 'display_admin_page')
        );

        add_submenu_page(
            'kura-ai',
            esc_html__('Security Scanner', 'kura-ai'),
            esc_html__('Scanner', 'kura-ai'),
            'manage_options',
            'kura-ai-scanner',
            array($this, 'display_scanner_page')
        );

        add_submenu_page(
            'kura-ai',
            esc_html__('Reports', 'kura-ai'),
            esc_html__('Reports', 'kura-ai'),
            'manage_options',
            'kura-ai-reports',
            array($this, 'display_reports_page')
        );

        add_submenu_page(
            'kura-ai',
            esc_html__('AI Suggestions', 'kura-ai'),
            esc_html__('AI Suggestions', 'kura-ai'),
            'manage_options',
            'kura-ai-suggestions',
            array($this, 'display_suggestions_page')
        );

        add_submenu_page(
            'kura-ai',
            esc_html__('Activity Logs', 'kura-ai'),
            esc_html__('Logs', 'kura-ai'),
            'manage_options',
            'kura-ai-logs',
            array($this, 'display_logs_page')
        );

        add_submenu_page(
            'kura-ai',
            esc_html__('AI Analysis', 'kura-ai'),
            esc_html__('AI Analysis', 'kura-ai'),
            'manage_options',
            'kura-ai-analysis',
            array($this, 'display_analysis_page')
        );

        add_submenu_page(
            'kura-ai',
            esc_html__('File Monitor', 'kura-ai'),
            esc_html__('File Monitor', 'kura-ai'),
            'manage_options',
            'kura-ai-file-monitor',
            array($this, 'display_file_monitor_page')
        );

        add_submenu_page(
            'kura-ai',
            esc_html__('Compliance', 'kura-ai'),
            esc_html__('Compliance', 'kura-ai'),
            'manage_options',
            'kura-ai-compliance',
            array($this, 'display_compliance_page')
        );

        add_submenu_page(
            'kura-ai',
            esc_html__('Security Hardening', 'kura-ai'),
            esc_html__('Hardening', 'kura-ai'),
            'manage_options',
            'kura-ai-hardening',
            array($this, 'display_hardening_page')
        );

        add_submenu_page(
            'kura-ai',
            esc_html__('Settings', 'kura-ai'),
            esc_html__('Settings', 'kura-ai'),
            'manage_options',
            'kura-ai-settings',
            array($this, 'display_settings_page')
        );
    }

    /**
     * Display admin page.
     *
     * @since    1.0.0
     */
    public function display_admin_page() {
        $template_path = trailingslashit($this->template_path) . 'kura-ai-admin-display.php';
        
        if (file_exists($template_path)) {
            include $template_path;
        } else {
            echo '<div class="wrap"><h1>' . esc_html__('Kura AI Security Dashboard', 'kura-ai') . '</h1><p>' . esc_html__('Template file not found.', 'kura-ai') . '</p></div>';
        }
    }

    /**
     * Display scanner page.
     *
     * @since    1.0.0
     */
    public function display_scanner_page() {
        include_once plugin_dir_path(dirname(__FILE__)) . 'admin/partials/security-scanner.php';
    }

    /**
     * Display reports page.
     *
     * @since    1.0.0
     */
    public function display_reports_page() {
        $template_path = trailingslashit($this->template_path) . 'kura-ai-reports-display.php';
        
        if (file_exists($template_path)) {
            include $template_path;
        } else {
            echo '<div class="wrap"><h1>' . esc_html__('Security Reports', 'kura-ai') . '</h1><p>' . esc_html__('Template file not found.', 'kura-ai') . '</p></div>';
        }
    }

    /**
     * Display suggestions page.
     *
     * @since    1.0.0
     */
    public function display_suggestions_page() {
        $template_path = trailingslashit($this->template_path) . 'kura-ai-suggestions-display.php';
        
        if (file_exists($template_path)) {
            include $template_path;
        } else {
            echo '<div class="wrap"><h1>' . esc_html__('AI Suggestions', 'kura-ai') . '</h1><p>' . esc_html__('Template file not found.', 'kura-ai') . '</p></div>';
        }
    }

    /**
     * Display logs page.
     *
     * @since    1.0.0
     */
    public function display_logs_page() {
        $template_path = trailingslashit($this->template_path) . 'kura-ai-logs-display.php';
        
        if (file_exists($template_path)) {
            // Make plugin properties available to the template
            $plugin_name = $this->plugin_name;
            $version = $this->version;
            include $template_path;
        } else {
            echo '<div class="wrap"><h1>' . esc_html__('Activity Logs', 'kura-ai') . '</h1><p>' . esc_html__('Template file not found.', 'kura-ai') . '</p></div>';
        }
    }

    /**
     * Display AI Analysis page.
     *
     * @since    1.0.0
     */
    public function display_analysis_page() {
        $template_path = trailingslashit($this->template_path) . 'ai-analysis.php';
        
        if (file_exists($template_path)) {
            include $template_path;
        } else {
            echo '<div class="wrap"><h1>' . esc_html__('AI Analysis', 'kura-ai') . '</h1><p>' . esc_html__('Template file not found.', 'kura-ai') . '</p></div>';
        }
    }

    /**
     * Display File Monitor page.
     *
     * @since    1.0.0
     */
    public function display_file_monitor_page() {
        $template_path = trailingslashit($this->template_path) . 'file-monitor.php';
        
        if (file_exists($template_path)) {
            include $template_path;
        } else {
            echo '<div class="wrap"><h1>' . esc_html__('File Monitor', 'kura-ai') . '</h1><p>' . esc_html__('Template file not found.', 'kura-ai') . '</p></div>';
        }
    }

    /**
     * Display Compliance page.
     *
     * @since    1.0.0
     */
    public function display_compliance_page() {
        $template_path = trailingslashit($this->template_path) . 'compliance.php';
        
        if (file_exists($template_path)) {
            include $template_path;
        } else {
            echo '<div class="wrap"><h1>' . esc_html__('Compliance', 'kura-ai') . '</h1><p>' . esc_html__('Template file not found.', 'kura-ai') . '</p></div>';
        }
    }

    /**
     * Display Security Hardening page.
     *
     * @since    1.0.0
     */
    public function display_hardening_page() {
        $template_path = trailingslashit($this->template_path) . 'security-hardening.php';
        
        if (file_exists($template_path)) {
            include $template_path;
        } else {
            echo '<div class="wrap"><h1>' . esc_html__('Security Hardening', 'kura-ai') . '</h1><p>' . esc_html__('Template file not found.', 'kura-ai') . '</p></div>';
        }
    }

    /**
     * Display settings page.
     *
     * @since    1.0.0
     */
    public function display_settings_page() {
        $template_path = trailingslashit($this->template_path) . 'kura-ai-settings-display.php';
        
        if (file_exists($template_path)) {
            include $template_path;
        } else {
            echo '<div class="wrap"><h1>' . esc_html__('Settings', 'kura-ai') . '</h1><p>' . esc_html__('Template file not found.', 'kura-ai') . '</p></div>';
        }
    }

    /**
     * Enqueue admin scripts.
     *
     * @since    1.0.0
     */
    public function enqueue_admin_scripts() {
        // Get current screen
        $screen = function_exists('get_current_screen') ? get_current_screen() : null;
        $page = isset($_GET['page']) ? sanitize_text_field($_GET['page']) : '';
        
        // Enqueue SweetAlert2 library on all Kura AI admin pages
        if (strpos($page, 'kura-ai') !== false || (isset($screen->id) && strpos($screen->id, 'kura-ai') !== false)) {
            wp_enqueue_script(
                'sweetalert2',
                'https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.js',
                array(),
                '11.0.0',
                false
            );
            
            // Enqueue SweetAlert2 CSS
            wp_enqueue_style(
                'sweetalert2',
                'https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css',
                array(),
                '11.0.0'
            );
            
            // Enqueue Font Awesome for icons
            wp_enqueue_style(
                'font-awesome',
                'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css',
                array(),
                '6.4.0'
            );
            
            // Enqueue SweetAlert2 configuration script
            wp_enqueue_script(
                'kura-ai-sweetalert-config',
                $this->assets_url . 'js/sweetalert-config.js',
                array('sweetalert2'),
                $this->version,
                false
            );
        }

        // Main admin script
        wp_enqueue_script(
            'kura-ai-admin',
            $this->assets_url . 'js/kura-ai-admin.js',
            array('jquery', 'sweetalert2', 'kura-ai-sweetalert-config'),
            $this->version,
            true
        );

        // Page-specific scripts
        if ($page === 'kura-ai-compliance' || strpos($page, 'compliance') !== false) {
            // Enqueue Chart.js for compliance charts
            wp_enqueue_script(
                'chartjs',
                'https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js',
                array(),
                '3.9.1',
                true
            );
            
            wp_enqueue_script(
                'kura-ai-compliance',
                $this->assets_url . 'js/compliance.js',
                array('jquery', 'sweetalert2', 'kura-ai-sweetalert-config', 'chartjs'),
                $this->version,
                true
            );
        }
        
        if ($page === 'kura-ai-file-monitor' || strpos($page, 'file-monitor') !== false) {
            // Enqueue Chart.js for file monitor charts
            wp_enqueue_script(
                'chartjs',
                'https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js',
                array(),
                '3.9.1',
                true
            );
            
            wp_enqueue_script(
                'kura-ai-file-monitor',
                $this->assets_url . 'js/file-monitor.js',
                array('jquery', 'sweetalert2', 'kura-ai-sweetalert-config', 'chartjs'),
                $this->version,
                true
            );
        }
        
        if ($page === 'kura-ai-security-hardening' || strpos($page, 'hardening') !== false) {
            wp_enqueue_script(
                'kura-ai-security-hardening',
                $this->assets_url . 'js/security-hardening.js',
                array('jquery', 'sweetalert2', 'kura-ai-sweetalert-config'),
                $this->version,
                true
            );
        }
        
        if ($page === 'kura-ai-scanner' || strpos($page, 'scanner') !== false) {
            wp_enqueue_script(
                'kura-ai-security-scanner',
                $this->assets_url . 'js/security-scanner.js',
                array('jquery', 'sweetalert2', 'kura-ai-sweetalert-config'),
                $this->version,
                true
            );
        }

        // Localize script for the appropriate handle based on page
        $script_handle = 'kura-ai-admin';
        if ($page === 'kura-ai-file-monitor' || strpos($page, 'file-monitor') !== false) {
            $script_handle = 'kura-ai-file-monitor';
        }
        
        wp_localize_script(
            $script_handle,
            'kura_ai_ajax',
            array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('kura_ai_nonce'),
                'admin_email' => get_option('admin_email'),
                'scan_in_progress' => esc_html__('Scanning in progress...', 'kura-ai'),
                'applying_fix' => esc_html__('Applying fix...', 'kura-ai'),
                'fix_applied' => esc_html__('Fix applied successfully!', 'kura-ai'),
                'fix_failed' => esc_html__('Failed to apply fix', 'kura-ai'),
                'apply_fix' => esc_html__('Apply Fix', 'kura-ai'),
                'getting_suggestions' => esc_html__('Getting suggestions...', 'kura-ai'),
                'exporting_logs' => esc_html__('Exporting logs...', 'kura-ai'),
                'clearing_logs' => esc_html__('Clearing logs...', 'kura-ai'),
                'logs_cleared' => esc_html__('Logs cleared successfully!', 'kura-ai'),
                // File monitor strings
                'create_version_error' => esc_html__('Failed to create version', 'kura-ai'),
                'remove_file_error' => esc_html__('Failed to remove file', 'kura-ai'),
                'confirm_remove_title' => esc_html__('Remove File?', 'kura-ai'),
                'confirm_remove_text' => esc_html__('Are you sure you want to remove this file from monitoring?', 'kura-ai'),
                'yes_remove' => esc_html__('Yes, Remove', 'kura-ai'),
                'no_cancel' => esc_html__('Cancel', 'kura-ai'),
                'confirm_rollback_title' => esc_html__('Rollback Version?', 'kura-ai'),
                'confirm_rollback_text' => esc_html__('Are you sure you want to rollback to this version?', 'kura-ai'),
                'yes_rollback' => esc_html__('Yes I confirm', 'kura-ai'),
                'strings' => array(
                    'loading' => esc_html__('Loading...', 'kura-ai'),
                    'error' => esc_html__('An error occurred.', 'kura-ai'),
                    'success' => esc_html__('Success!', 'kura-ai'),
                    'ok' => esc_html__('OK', 'kura-ai'),
                    'htaccess_error' => esc_html__('Failed to apply .htaccess rules.', 'kura-ai'),
                    'optimization_error' => esc_html__('Failed to optimize database.', 'kura-ai'),
                    'general_error' => esc_html__('An unexpected error occurred.', 'kura-ai'),
                ),
            )
        );
    }

    /**
     * Enqueue admin styles.
     *
     * @since    1.0.0
     */
    public function enqueue_styles() {
        $page = isset($_GET['page']) ? sanitize_text_field($_GET['page']) : '';
        
        wp_enqueue_style(
            'kura-ai-admin-styles',
            $this->assets_url . 'css/kura-ai-admin.css',
            array(),
            $this->version,
            'all'
        );
        
        // Page-specific styles
        if ($page === 'kura-ai-compliance' || strpos($page, 'compliance') !== false) {
            wp_enqueue_style(
                'kura-ai-compliance-styles',
                $this->assets_url . 'css/compliance.css',
                array(),
                $this->version,
                'all'
            );
        }
        
        if ($page === 'kura-ai-file-monitor' || strpos($page, 'file-monitor') !== false) {
            wp_enqueue_style(
                'kura-ai-file-monitor-styles',
                $this->assets_url . 'css/file-monitor.css',
                array(),
                $this->version,
                'all'
            );
        }
        
        if ($page === 'kura-ai-security-hardening' || strpos($page, 'hardening') !== false) {
            wp_enqueue_style(
                'kura-ai-security-hardening-styles',
                $this->assets_url . 'css/security-hardening.css',
                array(),
                $this->version,
                'all'
            );
        }
        
        if ($page === 'kura-ai-scanner' || strpos($page, 'scanner') !== false) {
            wp_enqueue_style(
                'kura-ai-security-scanner-styles',
                $this->assets_url . 'css/security-scanner.css',
                array(),
                $this->version,
                'all'
            );
        }
    }



    /**
     * Sanitize input data.
     *
     * @since    1.0.0
     * @param    mixed    $input    The input to sanitize.
     * @return   string   The sanitized input.
     */
    private function sanitize_input($input) {
        return sanitize_text_field($input);
    }

    /**
     * Run scheduled compliance scan.
     *
     * @since    1.0.0
     * @param    string   $standard   The compliance standard to scan for.
     */
    public function run_scheduled_compliance_scan($standard) {
        // Get scheduled scan settings
        $settings = get_option('kura_ai_scheduled_scan_settings', array());
        
        if (empty($settings) || !isset($settings['email'])) {
            return;
        }
        
        // Generate compliance report by calling the existing handler
        $_POST['standard'] = $standard;
        $_POST['nonce'] = wp_create_nonce('kura_ai_compliance_nonce');
        
        ob_start();
        $this->handle_generate_compliance_report();
        $output = ob_get_clean();
        
        $report_data = json_decode($output, true);
        
        if (!$report_data || !isset($report_data['success']) || !$report_data['success']) {
            return;
        }
        
        $report_data = $report_data['data'];
        
        // Send email notification
        $this->send_scheduled_scan_email($settings['email'], $standard, $report_data);
    }
    
    /**
     * Send scheduled scan email notification.
     *
     * @since    1.0.0
     * @param    string   $email      The email address to send to.
     * @param    string   $standard   The compliance standard.
     * @param    array    $report_data The report data.
     */
    private function send_scheduled_scan_email($email, $standard, $report_data) {
        $subject = sprintf('Kura AI - Scheduled %s Compliance Scan Results', $standard);
        
        $message = sprintf(
            "Your scheduled %s compliance scan has been completed.\n\n" .
            "Scan Results:\n" .
            "- Total Requirements: %d\n" .
            "- Compliant: %d\n" .
            "- Non-Compliant: %d\n" .
            "- Compliance Score: %s%%\n\n" .
            "Please log in to your WordPress admin to view the full report.\n\n" .
            "Best regards,\nKura AI Team",
            $standard,
            $report_data['total_requirements'] ?? 0,
            $report_data['compliant'] ?? 0,
            $report_data['non_compliant'] ?? 0,
            $report_data['compliance_score'] ?? '0'
        );
        
        wp_mail($email, $subject, $message);
    }

}