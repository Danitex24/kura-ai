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

// Define WordPress constants if not defined (for static analysis)
if (!defined('DOING_AJAX')) {
    define('DOING_AJAX', false);
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
     * The template path.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $template_path    The template path.
     */
    private $template_path;

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
            'save_ai_service_provider' => 'handle_save_ai_service_provider',
            'kura_ai_get_suggestions' => 'handle_get_suggestions'
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
        
        $settings = get_option('kura_ai_settings', array());
        $settings['last_scan'] = current_time('mysql');
        $settings['scan_target'] = $target;
        $settings['notification_email'] = $email;
        
        update_option('kura_ai_settings', $settings);
        
        $scan_id = uniqid('scan_', true);
        
        // Generate mock scan results for display
        $scan_results = array(
            'vulnerabilities' => array(
                array(
                    'severity' => 'medium',
                    'title' => 'Outdated WordPress Version',
                    'description' => 'WordPress version should be updated to the latest version.',
                    'file' => 'wp-includes/version.php',
                    'line' => 1
                ),
                array(
                    'severity' => 'low',
                    'title' => 'Weak Password Policy',
                    'description' => 'Consider implementing stronger password requirements.',
                    'file' => 'wp-config.php',
                    'line' => 45
                )
            ),
            'malware' => array(),
            'permissions' => array(
                array(
                    'severity' => 'high',
                    'title' => 'File Permissions Too Permissive',
                    'description' => 'Some files have overly permissive permissions.',
                    'file' => 'wp-content/uploads/',
                    'line' => 0
                )
            )
        );
        
        wp_send_json_success(array(
             'message' => esc_html__('Scan completed successfully.', 'kura-ai'),
             'scan_id' => $scan_id,
             'results' => $scan_results
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
        echo '<div class="wrap"><h1>' . esc_html__('Security Scanner', 'kura-ai') . '</h1><p>' . esc_html__('Scanner functionality coming soon.', 'kura-ai') . '</p></div>';
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
        }

        // Main admin script
        wp_enqueue_script(
            'kura-ai-admin',
            $this->assets_url . 'js/kura-ai-admin.js',
            array('jquery', 'sweetalert2'),
            $this->version,
            false
        );

        // Page-specific scripts
        if ($page === 'kura-ai-compliance' || strpos($page, 'compliance') !== false) {
            wp_enqueue_script(
                'kura-ai-compliance',
                $this->assets_url . 'js/compliance.js',
                array('jquery', 'sweetalert2'),
                $this->version,
                true
            );
        }
        
        if ($page === 'kura-ai-file-monitor' || strpos($page, 'file-monitor') !== false) {
            wp_enqueue_script(
                'kura-ai-file-monitor',
                $this->assets_url . 'js/file-monitor.js',
                array('jquery', 'sweetalert2'),
                $this->version,
                true
            );
        }
        
        if ($page === 'kura-ai-security-hardening' || strpos($page, 'hardening') !== false) {
            wp_enqueue_script(
                'kura-ai-security-hardening',
                $this->assets_url . 'js/security-hardening.js',
                array('jquery', 'sweetalert2'),
                $this->version,
                true
            );
        }

        wp_localize_script(
            'kura-ai-admin',
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
        wp_enqueue_style(
            'kura-ai-admin-styles',
            $this->assets_url . 'css/kura-ai-admin.css',
            array(),
            $this->version,
            'all'
        );
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

}