<?php
/**
 * The admin-specific functionality for audit logs.
 *
 * @package    Kura_AI
 * @subpackage Kura_AI/admin
 * @author     Daniel Abughdyer <daniel@danovatesolutions.org>
 */

namespace Kura_AI;

// If this file is called directly, abort.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * The admin-specific functionality for audit logs.
 *
 * Defines the plugin name, version, and hooks for
 * the admin area functionality specific to audit logs.
 *
 * @package    Kura_AI
 * @subpackage Kura_AI/admin
 */
class Kura_AI_Audit_Log_Admin {

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
     * The audit log instance.
     *
     * @since    1.0.0
     * @access   private
     * @var      Kura_AI_Audit_Log    $audit_log    The audit log instance.
     */
    private $audit_log;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param    string       $plugin_name    The name of this plugin.
     * @param    string       $version        The version of this plugin.
     * @param    Kura_AI_Audit_Log  $audit_log      The audit log instance.
     */
    public function __construct($plugin_name, $version, $audit_log) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        $this->audit_log = $audit_log;
    }

    /**
     * Register the stylesheets for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_styles() {
        // Get plugin directory URL with fallback
        $plugin_url = '';
        if (\function_exists('\plugin_dir_url')) {
            $plugin_url = \plugin_dir_url(__FILE__);
        } else if (function_exists('plugin_dir_url')) {
            $plugin_url = plugin_dir_url(__FILE__);
        } else {
            // Simple fallback that works for basic cases
            $base_url = 'http' . (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 's' : '') . '://' . $_SERVER['HTTP_HOST'];
            $wp_content_url = defined('\WP_CONTENT_URL') ? \WP_CONTENT_URL : $base_url . '/wp-content';
            $plugin_url = $wp_content_url . '/plugins/' . basename(dirname(__FILE__)) . '/';
        }
        
        wp_enqueue_style(
            $this->plugin_name . '-audit-log',
            $plugin_url . 'css/kura-ai-audit-log-admin.css',
            array(),
            $this->version,
            'all'
        );
    }

    /**
     * Register the JavaScript for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts() {
        // Get plugin directory URL with fallback
        $plugin_url = '';
        if (\function_exists('\plugin_dir_url')) {
            $plugin_url = \plugin_dir_url(__FILE__);
        } else if (function_exists('plugin_dir_url')) {
            $plugin_url = plugin_dir_url(__FILE__);
        } else {
            // Simple fallback that works for basic cases
            $base_url = 'http' . (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 's' : '') . '://' . $_SERVER['HTTP_HOST'];
            $wp_content_url = defined('\WP_CONTENT_URL') ? \WP_CONTENT_URL : $base_url . '/wp-content';
            $plugin_url = $wp_content_url . '/plugins/' . basename(dirname(__FILE__)) . '/';
        }
        
        wp_enqueue_script(
            $this->plugin_name . '-audit-log',
            $plugin_url . 'js/kura-ai-audit-log-admin.js',
            array('jquery'),
            $this->version,
            false
        );

        // Add nonce for AJAX actions
        wp_localize_script(
            $this->plugin_name . '-audit-log',
            'kura_ai_audit_log',
            array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('kura_ai_audit_log_nonce'),
                'confirm_clear' => __('Are you sure you want to clear these logs? This action cannot be undone.', 'kura-ai'),
                'confirm_export' => __('Are you sure you want to export these logs?', 'kura-ai')
            )
        );
    }

    /**
     * Register the administration menu for audit logs.
     *
     * @since    1.0.0
     */
    public function add_admin_menu() {
        // Add audit log tab to the main logs page
        add_action('kura_ai_logs_tabs', array($this, 'add_audit_log_tab'), 20);
        
        // Register the audit log settings section
        add_action('kura_ai_settings_sections', array($this, 'add_settings_section'), 30);
        add_action('kura_ai_settings_fields', array($this, 'add_settings_fields'), 30);
    }

    /**
     * Add audit log tab to the logs page.
     *
     * @since    1.0.0
     * @param    string    $current_tab    The current active tab.
     */
    public function add_audit_log_tab($current_tab) {
        $audit_tab = 'audit';
        $class = ($current_tab === $audit_tab) ? 'nav-tab-active' : '';
        echo '<a class="nav-tab ' . esc_attr($class) . '" href="?page=kura-ai-logs&tab=' . esc_attr($audit_tab) . '">';
        echo '<span class="dashicons dashicons-shield"></span> ' . esc_html__('Security Audit', 'kura-ai');
        echo '</a>';
    }

    /**
     * Add settings section for audit logs.
     *
     * @since    1.0.0
     * @param    array    $sections    The existing settings sections.
     * @return   array                The modified settings sections.
     */
    public function add_settings_section($sections) {
        $sections['audit_logs'] = array(
            'id' => 'audit_logs',
            'title' => __('Security Audit Logs', 'kura-ai'),
            'description' => __('Configure security audit logging settings.', 'kura-ai'),
            'icon' => 'dashicons-shield',
        );
        
        return $sections;
    }

    /**
     * Add settings fields for audit logs.
     *
     * @since    1.0.0
     * @param    array    $fields    The existing settings fields.
     * @return   array               The modified settings fields.
     */
    public function add_settings_fields($fields) {
        $fields['audit_logs'] = array(
            array(
                'id' => 'kura_ai_audit_logging_scope',
                'label' => __('Logging Scope', 'kura-ai'),
                'description' => __('Choose which events to log.', 'kura-ai'),
                'type' => 'select',
                'options' => array(
                    'all' => __('All Events', 'kura-ai'),
                    'significant' => __('Significant Events Only', 'kura-ai'),
                ),
                'default' => 'all',
            ),
            array(
                'id' => 'kura_ai_audit_retention_period',
                'label' => __('Log Retention Period', 'kura-ai'),
                'description' => __('How long to keep audit logs before automatic deletion.', 'kura-ai'),
                'type' => 'select',
                'options' => array(
                    '30' => __('30 Days', 'kura-ai'),
                    '60' => __('60 Days', 'kura-ai'),
                    '90' => __('90 Days', 'kura-ai'),
                    '180' => __('6 Months', 'kura-ai'),
                    '365' => __('1 Year', 'kura-ai'),
                    '0' => __('Keep Indefinitely', 'kura-ai'),
                ),
                'default' => '90',
            ),
            array(
                'id' => 'kura_ai_audit_email_reporting',
                'label' => __('Email Reports', 'kura-ai'),
                'description' => __('Send email reports of security audit logs.', 'kura-ai'),
                'type' => 'select',
                'options' => array(
                    'off' => __('Disabled', 'kura-ai'),
                    'daily' => __('Daily', 'kura-ai'),
                    'weekly' => __('Weekly', 'kura-ai'),
                    'monthly' => __('Monthly', 'kura-ai'),
                ),
                'default' => 'off',
            ),
            array(
                'id' => 'kura_ai_audit_email_recipients',
                'label' => __('Email Recipients', 'kura-ai'),
                'description' => __('Email addresses to receive audit log reports (comma separated). Leave blank to use admin email.', 'kura-ai'),
                'type' => 'text',
                'default' => get_option('admin_email'),
                'sanitize_callback' => 'sanitize_text_field',
            ),
        );
        
        return $fields;
    }

    /**
     * Register the AJAX handlers for audit logs.
     *
     * @since    1.0.0
     */
    public function register_ajax_handlers() {
        add_action('wp_ajax_kura_ai_clear_audit_logs', array($this, 'ajax_clear_audit_logs'));
        add_action('wp_ajax_kura_ai_export_audit_logs', array($this, 'ajax_export_audit_logs'));
    }

    /**
     * AJAX handler for clearing audit logs.
     *
     * @since    1.0.0
     */
    public function ajax_clear_audit_logs() {
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('You do not have permission to perform this action.', 'kura-ai')));
        }

        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'kura_ai_audit_log_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'kura-ai')));
        }

        // Get filter parameters
        $args = array();
        
        if (!empty($_POST['event_type'])) {
            $args['event_type'] = sanitize_text_field($_POST['event_type']);
        }
        
        if (!empty($_POST['severity'])) {
            $args['severity'] = sanitize_text_field($_POST['severity']);
        }
        
        if (!empty($_POST['older_than'])) {
            $args['older_than'] = sanitize_text_field($_POST['older_than']);
        }

        // Clear logs
        $result = $this->audit_log->clear_logs($args);

        if ($result !== false) {
            wp_send_json_success(array(
                'message' => sprintf(__('Successfully cleared %d audit log entries.', 'kura-ai'), $result),
                'count' => $result
            ));
        } else {
            wp_send_json_error(array('message' => __('Failed to clear audit logs.', 'kura-ai')));
        }
    }

    /**
     * AJAX handler for exporting audit logs.
     *
     * @since    1.0.0
     */
    public function ajax_export_audit_logs() {
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('You do not have permission to perform this action.', 'kura-ai')));
        }

        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'kura_ai_audit_log_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'kura-ai')));
        }

        // Get filter parameters
        $args = array();
        
        if (!empty($_POST['event_type'])) {
            $args['event_type'] = sanitize_text_field($_POST['event_type']);
        }
        
        if (!empty($_POST['action'])) {
            $args['action'] = sanitize_text_field($_POST['action']);
        }
        
        if (!empty($_POST['severity'])) {
            $args['severity'] = sanitize_text_field($_POST['severity']);
        }
        
        if (!empty($_POST['user_id'])) {
            $args['user_id'] = intval($_POST['user_id']);
        }
        
        if (!empty($_POST['date_from'])) {
            $args['date_from'] = sanitize_text_field($_POST['date_from']);
        }
        
        if (!empty($_POST['date_to'])) {
            $args['date_to'] = sanitize_text_field($_POST['date_to']);
        }

        // Get export format
        $format = isset($_POST['format']) ? sanitize_text_field($_POST['format']) : 'csv';

        // Export logs
        if ($format === 'json') {
            $data = $this->audit_log->export_logs_to_json($args);
            $filename = 'kura-ai-audit-logs-' . date('Y-m-d') . '.json';
            $content_type = 'application/json';
        } else {
            $data = $this->audit_log->export_logs_to_csv($args);
            $filename = 'kura-ai-audit-logs-' . date('Y-m-d') . '.csv';
            $content_type = 'text/csv';
        }

        // Send file headers
        header('Content-Type: ' . $content_type);
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');

        // Output file content
        echo $data;
        exit;
    }

    /**
     * Display the audit log page content.
     *
     * @since    1.0.0
     */
    public function display_audit_log_page() {
        // Check if user has permission
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'kura-ai'));
        }

        // Get filter parameters from URL
        $event_type = isset($_GET['event_type']) ? sanitize_text_field($_GET['event_type']) : '';
        $action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : '';
        $severity = isset($_GET['severity']) ? sanitize_text_field($_GET['severity']) : '';
        $user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : '';
        $search = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';
        $date_from = isset($_GET['date_from']) ? sanitize_text_field($_GET['date_from']) : '';
        $date_to = isset($_GET['date_to']) ? sanitize_text_field($_GET['date_to']) : '';

        // Pagination parameters
        $page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $per_page = 20;

        // Get logs with filters
        $logs = $this->audit_log->get_logs(array(
            'event_type' => $event_type,
            'action' => $action,
            'severity' => $severity,
            'user_id' => $user_id,
            'search' => $search,
            'date_from' => $date_from,
            'date_to' => $date_to,
            'per_page' => $per_page,
            'page' => $page,
        ));

        // Get unique event types and actions for filters
        global $wpdb;
        $table_name = $wpdb->prefix . 'kura_ai_audit_logs';
        $event_types = $wpdb->get_col("SELECT DISTINCT event_type FROM $table_name ORDER BY event_type ASC");
        $actions = $wpdb->get_col("SELECT DISTINCT action FROM $table_name ORDER BY action ASC");
        $severities = array('info', 'low', 'medium', 'high', 'critical');

        // Include the view template
        include plugin_dir_path(__FILE__) . 'partials/kura-ai-audit-log-display.php';
    }

    /**
     * Schedule cleanup of old audit logs.
     *
     * @since    1.0.0
     */
    public function schedule_log_cleanup() {
        if (!wp_next_scheduled('kura_ai_audit_log_cleanup')) {
            wp_schedule_event(time(), 'daily', 'kura_ai_audit_log_cleanup');
        }

        add_action('kura_ai_audit_log_cleanup', array($this, 'cleanup_old_logs'));
    }

    /**
     * Clean up old audit logs based on retention settings.
     *
     * @since    1.0.0
     */
    public function cleanup_old_logs() {
        $retention_days = get_option('kura_ai_audit_retention_period', '90');
        
        // If retention is set to 0 (keep indefinitely), don't delete anything
        if ($retention_days === '0') {
            return;
        }
        
        // Calculate the date threshold
        $date_threshold = date('Y-m-d H:i:s', strtotime('-' . intval($retention_days) . ' days'));
        
        // Clear logs older than the threshold
        $this->audit_log->clear_logs(array('older_than' => $date_threshold));
    }

    /**
     * Handle email report scheduling.
     *
     * @since    1.0.0
     */
    public function setup_email_reports() {
        // Schedule email reports based on settings
        $this->audit_log->schedule_email_reports();
        
        // Add hooks for the scheduled events
        add_action('kura_ai_audit_daily_report', array($this, 'send_daily_report'));
        add_action('kura_ai_audit_weekly_report', array($this, 'send_weekly_report'));
        add_action('kura_ai_audit_monthly_report', array($this, 'send_monthly_report'));
    }

    /**
     * Send daily email report.
     *
     * @since    1.0.0
     */
    public function send_daily_report() {
        $this->audit_log->send_email_report('daily');
    }

    /**
     * Send weekly email report.
     *
     * @since    1.0.0
     */
    public function send_weekly_report() {
        $this->audit_log->send_email_report('weekly');
    }

    /**
     * Send monthly email report.
     *
     * @since    1.0.0
     */
    public function send_monthly_report() {
        $this->audit_log->send_email_report('monthly');
    }
}