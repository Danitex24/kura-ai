<?php
/**
 * The admin-specific functionality for the audit log.
 *
 * @link       https://kura.io
 * @since      1.0.0
 *
 * @package    Kura_AI
 * @subpackage Kura_AI/admin
 */

/**
 * The admin-specific functionality for the audit log.
 *
 * Defines the plugin name, version, and hooks for the admin area functionality
 * related to the audit log.
 *
 * @package    Kura_AI
 * @subpackage Kura_AI/admin
 * @author     Kura AI <info@kura.io>
 */
class Kura_AI_Audit_Admin {

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
     * @param    string    $plugin_name       The name of this plugin.
     * @param    string    $version           The version of this plugin.
     * @param    Kura_AI_Audit_Log    $audit_log    The audit log instance.
     */
    public function __construct($plugin_name, $version, $audit_log) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        $this->audit_log = $audit_log;
    }

    /**
     * Register the hooks related to the admin area functionality.
     *
     * @since    1.0.0
     */
    public function init() {
        // Add AJAX handlers
        add_action('wp_ajax_kura_ai_export_logs', array($this, 'export_logs'));
        add_action('wp_ajax_kura_ai_clear_logs', array($this, 'clear_logs'));
        
        // Add admin scripts and styles
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        
        // Add localized script data
        add_filter('kura_ai_localize_script', array($this, 'localize_script'));
    }

    /**
     * Register the JavaScript for the admin area.
     *
     * @since    1.0.0
     * @param    string    $hook    The current admin page.
     */
    public function enqueue_scripts($hook) {
        // Only load on our plugin pages
        if (strpos($hook, 'kura-ai') === false) {
            return;
        }
        
        // No need to enqueue additional scripts as we're using the existing ones
    }

    /**
     * Add localized script data for the audit log.
     *
     * @since    1.0.0
     * @param    array    $data    The localized script data.
     * @return   array             The modified localized script data.
     */
    public function localize_script($data) {
        $data['showSettings'] = __('Show Settings', 'kura-ai');
        $data['hideSettings'] = __('Hide Settings', 'kura-ai');
        $data['allEventsDesc'] = __('Logs all security events including minor changes.', 'kura-ai');
        $data['significantEventsDesc'] = __('Logs only significant security events (auth, config, plugins/themes).', 'kura-ai');
        
        return $data;
    }

    /**
     * Export logs as CSV or JSON.
     *
     * @since    1.0.0
     */
    public function export_logs() {
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'kura-ai'));
        }
        
        // Verify nonce
        if (!isset($_REQUEST['_wpnonce']) || !wp_verify_nonce($_REQUEST['_wpnonce'], 'kura_ai_nonce')) {
            wp_die(__('Security check failed.', 'kura-ai'));
        }
        
        $format = isset($_REQUEST['format']) ? sanitize_text_field($_REQUEST['format']) : 'csv';
        $log_type = isset($_REQUEST['log_type']) ? sanitize_text_field($_REQUEST['log_type']) : 'activity';
        
        // Get logs
        $args = array(
            'per_page' => -1, // Get all logs
        );
        
        // Apply filters if provided
        if (isset($_REQUEST['event_type']) && !empty($_REQUEST['event_type'])) {
            $args['event_type'] = sanitize_text_field($_REQUEST['event_type']);
        }
        
        if (isset($_REQUEST['severity']) && !empty($_REQUEST['severity'])) {
            $args['severity'] = sanitize_text_field($_REQUEST['severity']);
        }
        
        if (isset($_REQUEST['date_from']) && !empty($_REQUEST['date_from'])) {
            $args['date_from'] = sanitize_text_field($_REQUEST['date_from']);
        }
        
        if (isset($_REQUEST['date_to']) && !empty($_REQUEST['date_to'])) {
            $args['date_to'] = sanitize_text_field($_REQUEST['date_to']);
        }
        
        if (isset($_REQUEST['search']) && !empty($_REQUEST['search'])) {
            $args['search'] = sanitize_text_field($_REQUEST['search']);
        }
        
        // Get logs based on log type
        $logs = array();
        if ($log_type === 'audit') {
            $logs = $this->audit_log->get_logs($args);
        } else {
            // Use the existing logger for activity logs
            $logger = new Kura_AI_Logger();
            $logs = $logger->get_logs($args);
        }
        
        if (empty($logs['items'])) {
            wp_die(__('No logs found to export.', 'kura-ai'));
        }
        
        // Set filename
        $site_name = sanitize_title_with_dashes(get_bloginfo('name'));
        $date = date('Y-m-d');
        $filename = $site_name . '-' . $log_type . '-logs-' . $date;
        
        if ($format === 'json') {
            // Export as JSON
            header('Content-Type: application/json');
            header('Content-Disposition: attachment; filename="' . $filename . '.json"');
            echo wp_json_encode($logs['items']);
            exit;
        } else {
            // Export as CSV
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
            
            $output = fopen('php://output', 'w');
            
            // CSV headers
            if ($log_type === 'audit') {
                fputcsv($output, array(
                    __('ID', 'kura-ai'),
                    __('Date', 'kura-ai'),
                    __('Event Type', 'kura-ai'),
                    __('Action', 'kura-ai'),
                    __('Summary', 'kura-ai'),
                    __('User ID', 'kura-ai'),
                    __('IP Address', 'kura-ai'),
                    __('Severity', 'kura-ai'),
                    __('Details', 'kura-ai')
                ));
                
                // CSV data
                foreach ($logs['items'] as $log) {
                    $details = is_array($log['details']) ? wp_json_encode($log['details']) : $log['details'];
                    
                    fputcsv($output, array(
                        $log['id'],
                        $log['created_at'],
                        $log['event_type'],
                        $log['action'],
                        $log['summary'],
                        $log['user_id'],
                        $log['ip_address'],
                        $log['severity'],
                        $details
                    ));
                }
            } else {
                // Activity logs CSV format
                fputcsv($output, array(
                    __('ID', 'kura-ai'),
                    __('Date', 'kura-ai'),
                    __('Type', 'kura-ai'),
                    __('Severity', 'kura-ai'),
                    __('Message', 'kura-ai'),
                    __('Details', 'kura-ai')
                ));
                
                // CSV data
                foreach ($logs['items'] as $log) {
                    $details = is_array($log['log_data']) ? wp_json_encode($log['log_data']) : $log['log_data'];
                    
                    fputcsv($output, array(
                        $log['id'],
                        $log['created_at'],
                        $log['log_type'],
                        $log['severity'],
                        $log['log_message'],
                        $details
                    ));
                }
            }
            
            fclose($output);
            exit;
        }
    }

    /**
     * Clear logs.
     *
     * @since    1.0.0
     */
    public function clear_logs() {
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('You do not have sufficient permissions to access this page.', 'kura-ai')));
        }
        
        // Verify nonce
        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'kura_ai_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'kura-ai')));
        }
        
        $log_type = isset($_POST['log_type']) ? sanitize_text_field($_POST['log_type']) : 'activity';
        
        if ($log_type === 'audit') {
            // Clear audit logs
            $result = $this->audit_log->clear_logs();
            
            if ($result) {
                // Log the clearing action
                $user = wp_get_current_user();
                $this->audit_log->log('system', 'logs_cleared', sprintf(__('Audit logs cleared by %s', 'kura-ai'), $user->user_login), array(
                    'user_id' => $user->ID,
                    'user_login' => $user->user_login
                ), 'medium');
                
                wp_send_json_success(array('message' => __('Audit logs cleared successfully.', 'kura-ai')));
            } else {
                wp_send_json_error(array('message' => __('Error clearing audit logs.', 'kura-ai')));
            }
        } else {
            // Clear activity logs using the existing logger
            $logger = new Kura_AI_Logger();
            $result = $logger->clear_logs();
            
            if ($result) {
                wp_send_json_success(array('message' => __('Activity logs cleared successfully.', 'kura-ai')));
            } else {
                wp_send_json_error(array('message' => __('Error clearing activity logs.', 'kura-ai')));
            }
        }
    }
}