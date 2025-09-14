<?php
/**
 * The security audit logging functionality of the plugin.
 *
 * @package    Kura_AI
 * @subpackage Kura_AI/includes
 * @author     Daniel Abughdyer <daniel@danovatesolutions.org>
 */

namespace Kura_AI;

// If this file is called directly, abort.
if (!defined('ABSPATH')) {
    die('Direct access not permitted.');
}

// Ensure WordPress core is loaded
require_once(ABSPATH . 'wp-load.php');

// Include WordPress core files needed for this class
require_once(ABSPATH . 'wp-admin/includes/upgrade.php'); // For dbDelta
require_once(ABSPATH . 'wp-includes/pluggable.php'); // For user functions
require_once(ABSPATH . 'wp-includes/plugin.php'); // For hooks and filters

// Import WordPress functions for use within namespace
use function \dbDelta;
use function \wp_is_post_revision;
use function \wp_is_post_autosave;
use function \get_post_type_object;
use function \get_post;
use function \get_userdata;
use function \do_action;
use function \admin_url;
use function \wp_mail;
use function \get_plugin_data;
use function \get_bloginfo;
use function \get_current_user_id;
use function \apply_filters;
use function \current_time;
use function \maybe_serialize;
use function \sanitize_text_field;
use function \maybe_unserialize;
use function \add_action;
use function \get_option;
use function \wp_next_scheduled;
use function \wp_schedule_event;
use function \wp_clear_scheduled_hook;
use function \__;
use function \esc_attr;
use function \esc_js;

// Import PHP native functions
// Note: empty, isset, defined are language constructs, not functions
use function \function_exists;
use function \dirname;
use function \strpos;
use function \strtolower;
use function \array_merge;
use function \strtoupper;
use function \is_string;
use function \ceil;
use function \date;
use function \strtotime;
use function \intval;
use function \fopen;
use function \ob_start;
use function \fputcsv;
use function \is_array;
use function \json_encode;
use function \fclose;
use function \ob_get_clean;
use function \count;
use function \ucfirst;
use function \array_filter;
use function \in_array;
use function \implode;
use function \error_log;

// No need for duplicate imports as they're already imported above
// If this file is called directly, abort.
if (!defined('ABSPATH')) {
    exit();
}

// Define WordPress ABSPATH if not defined
if (!defined('ABSPATH')) {
    if (defined('\ABSPATH')) {
        define('ABSPATH', \ABSPATH);
    } else {
        define('ABSPATH', dirname(__FILE__, 3) . '/');
    }
}

// Check if WordPress core functions are available
if (!function_exists('\maybe_serialize') || !function_exists('\maybe_unserialize') || 
    !function_exists('\current_time') || !function_exists('\get_current_user_id') || 
    !function_exists('\get_userdata') || !function_exists('\apply_filters') || 
    !function_exists('\do_action')) {
    require_once ABSPATH . 'wp-includes/functions.php';
}

// Check if WordPress formatting functions are available
if (!function_exists('\sanitize_text_field') || !function_exists('\esc_attr') || 
    !function_exists('\esc_js')) {
    require_once ABSPATH . 'wp-includes/formatting.php';
}

// Check if WordPress plugin functions are available
if (!function_exists('\get_plugin_data')) {
    require_once ABSPATH . 'wp-admin/includes/plugin.php';
}

// Check if WordPress post functions are available
if (!function_exists('\wp_is_post_revision') || !function_exists('\wp_is_post_autosave') || 
    !function_exists('\get_post') || !function_exists('\get_post_type_object')) {
    require_once ABSPATH . 'wp-includes/post.php';
}

// Check if WordPress translation functions are available
if (!function_exists('\__')) {
    require_once ABSPATH . 'wp-includes/l10n.php';
}

// Check if WordPress admin functions are available
if (!function_exists('\admin_url')) {
    require_once ABSPATH . 'wp-includes/link-template.php';
}

// Check if WordPress mail functions are available
if (!function_exists('wp_mail')) {
    require_once ABSPATH . 'wp-includes/pluggable.php';
}

// Check if WordPress blog info functions are available
if (!function_exists('get_bloginfo')) {
    require_once ABSPATH . 'wp-includes/general-template.php';
}

// Make sure WordPress database functions are available
if (!isset($GLOBALS['wpdb'])) {
    require_once ABSPATH . 'wp-includes/wp-db.php';
    global $wpdb;
}

class Kura_AI_Audit_Log
{
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
     * The database table name for audit logs.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $table_name    The database table name.
     */
    private $table_name;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param    string    $plugin_name       The name of this plugin.
     * @param    string    $version    The version of this plugin.
     */
    public function __construct($plugin_name, $version)
    {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'kura_ai_audit_logs';
        
        // Register hooks for WordPress events
        $this->register_hooks();
    }

    /**
     * Register hooks for WordPress events to be logged.
     *
     * @since    1.0.0
     */
    private function register_hooks()
    {
        // Ensure database table structure is correct
        $this->create_table();
        
        // User events
        add_action('user_register', array($this, 'log_user_created'), 10, 1);
        add_action('profile_update', array($this, 'log_user_updated'), 10, 2);
        add_action('delete_user', array($this, 'log_user_deleted'), 10, 1);
        add_action('set_user_role', array($this, 'log_role_changed'), 10, 3);
        
        // Authentication events
        add_action('wp_login', array($this, 'log_successful_login'), 10, 2);
        add_action('wp_login_failed', array($this, 'log_failed_login'), 10, 1);
        add_action('wp_logout', array($this, 'log_logout'), 10, 1);
        
        // Plugin/Theme events
        add_action('activated_plugin', array($this, 'log_plugin_activated'), 10, 2);
        add_action('deactivated_plugin', array($this, 'log_plugin_deactivated'), 10, 2);
        add_action('deleted_plugin', array($this, 'log_plugin_deleted'), 10, 2);
        add_action('upgrader_process_complete', array($this, 'log_plugin_update'), 10, 2);
        add_action('switch_theme', array($this, 'log_theme_switched'), 10, 3);
        
        // Content events
        add_action('wp_insert_post', array($this, 'log_post_created'), 10, 3);
        add_action('post_updated', array($this, 'log_post_updated'), 10, 3);
        add_action('delete_post', array($this, 'log_post_deleted'), 10, 1);
        add_action('wp_trash_post', array($this, 'log_post_trashed'), 10, 1);
        
        // WordPress core events
        add_action('_core_updated_successfully', array($this, 'log_core_update'), 10, 1);
        
        // Options events
        add_action('updated_option', array($this, 'log_option_updated'), 10, 3);
        
        // Custom hook for other plugins to log events
        add_action('kura_ai_audit_log_event', array($this, 'log'), 10, 6);
        
        // Register hooks for email reports
        add_action('kura_ai_audit_daily_report', array($this, 'send_email_report'));
        add_action('kura_ai_audit_weekly_report', array($this, 'send_email_report'));
        add_action('kura_ai_audit_monthly_report', array($this, 'send_email_report'));
    }

    /**
     * Create the audit logs table if it doesn't exist.
     * This method ensures the schema matches the one in the activator class.
     *
     * @since    1.0.0
     */
    public function create_table()
    {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$this->table_name} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            event_type varchar(50) NOT NULL,
            action varchar(50) DEFAULT NULL,
            severity varchar(20) NOT NULL DEFAULT 'info',
            message text NOT NULL,
            user_id bigint(20) DEFAULT NULL,
            user_role varchar(50) DEFAULT NULL,
            ip_address varchar(45) DEFAULT NULL,
            object_type varchar(50) DEFAULT NULL,
            object_id varchar(50) DEFAULT NULL,
            data longtext DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY event_type (event_type),
            KEY action (action),
            KEY severity (severity),
            KEY user_id (user_id),
            KEY object_type (object_type),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        // Make sure the table exists and has the correct schema
        // This will help prevent issues with missing columns

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        // Check if the action column exists, if not add it
        $this->check_and_add_action_column();
    }
    
    /**
     * Check if the action column exists in the audit logs table and add it if missing.
     * This helps fix the issue with the 'Unknown column action' error.
     *
     * @since    1.0.0
     */
    private function check_and_add_action_column()
    {
        global $wpdb;
        
        // Check if the column exists
        $column_exists = $wpdb->get_results("SHOW COLUMNS FROM {$this->table_name} LIKE 'action'");
        
        // If the column doesn't exist, add it
        if (empty($column_exists)) {
            $wpdb->query("ALTER TABLE {$this->table_name} ADD COLUMN action varchar(50) DEFAULT NULL AFTER event_type");
            $wpdb->query("ALTER TABLE {$this->table_name} ADD INDEX action (action)");
            
            error_log('KuraAI: Added missing action column to audit logs table');
        }
    }

    /**
     * Log a security audit event.
     *
     * @since    1.0.0
     * @param    string    $event_type    The event type (user, auth, plugin, theme, content, core, option)
     * @param    string    $action        The action performed (created, updated, deleted, etc.)
     * @param    string    $object_type   The specific object type affected
     * @param    string    $message       A brief summary of the event
     * @param    array     $data          Additional details about the event
     * @param    string    $severity      The severity level (info, low, medium, high, critical)
     * @param    int       $user_id       Optional. User ID. Default is current user.
     * @return   int|false                The log ID or false on failure
     */
    public function log($event_type, $action, $object_type, $message, $data = array(), $severity = 'info', $user_id = null)
    {
        global $wpdb;

        // Get current user if not specified
        if ($user_id === null) {
            $user_id = \get_current_user_id();
        }
        
        // Get user role or username for user_role field
        $user_role = '';
        if ($user_id > 0) {
            $user = \get_userdata($user_id);
            if ($user) {
                if (!empty($user->roles)) {
                    $user_role = implode(', ', $user->roles);
                } else {
                    $user_role = $user->user_login;
                }
            }
        } else {
            $user_role = 'system';
        }
        
        // Get IP address
        $ip_address = $this->get_client_ip();
        
        // Validate severity
        $valid_severities = array('info', 'low', 'medium', 'high', 'critical');
        if (!in_array($severity, $valid_severities)) {
            $severity = 'info';
        }
        
        // Allow filtering of log data before saving
        $log_data = \apply_filters('kura_ai_audit_log_data', array(
            'created_at' => \current_time('mysql'),
            'user_id' => $user_id,
            'user_role' => $user_role,
            'ip_address' => $ip_address,
            'event_type' => \sanitize_text_field($event_type),
            'action' => \sanitize_text_field($action),
            'object_type' => \sanitize_text_field($object_type),
            'object_id' => 0, // Default object_id
            'message' => \sanitize_text_field($message),
            'data' => \maybe_serialize($data),
            'severity' => $severity
        ));
        
        // Allow plugins to prevent logging of specific events
        $should_log = \apply_filters('kura_ai_should_log_event', true, $event_type, $object_type, $severity);
        if (!$should_log) {
            return false;
        }
        
        // Check if we should log based on settings
        $logging_scope = \get_option('kura_ai_audit_logging_scope', 'all');
        if ($logging_scope === 'significant' && $severity === 'info') {
            return false;
        }

        $result = $wpdb->insert($this->table_name, $log_data);

        if ($result === false) {
            error_log('KuraAI: Failed to log audit event - ' . $wpdb->last_error);
            return false;
        }

        return $wpdb->insert_id;
    }

    /**
     * Get client IP address.
     *
     * @since    1.0.0
     * @return   string    The client IP address
     */
    private function get_client_ip()
    {
        $ip = '127.0.0.1';
        
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = \sanitize_text_field($_SERVER['HTTP_CLIENT_IP']);
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = \sanitize_text_field($_SERVER['HTTP_X_FORWARDED_FOR']);
        } elseif (!empty($_SERVER['REMOTE_ADDR'])) {
            $ip = \sanitize_text_field($_SERVER['REMOTE_ADDR']);
        }
        
        return $ip;
    }

    /**
     * Get audit logs from the database.
     *
     * @since    1.0.0
     * @param    array     $args    Query arguments
     * @return   array              Array of log entries
     */
    public function get_logs($args = array())
    {
        global $wpdb;

        $defaults = array(
            'event_type' => '',
            'action' => '',
            'severity' => '',
            'user_id' => '',
            'search' => '',
            'date_from' => '',
            'date_to' => '',
            'per_page' => 20,
            'page' => 1,
            'orderby' => 'created_at',
            'order' => 'DESC'
        );

        $args = array_merge($defaults, $args);

        $where = array();
        $query_params = array();

        if (!empty($args['event_type'])) {
            $where[] = 'event_type = %s';
            $query_params[] = $args['event_type'];
        }

        if (!empty($args['action'])) {
            $where[] = 'action = %s';
            $query_params[] = $args['action'];
        }

        if (!empty($args['severity'])) {
            $where[] = 'severity = %s';
            $query_params[] = $args['severity'];
        }

        if (!empty($args['user_id'])) {
            $where[] = 'user_id = %d';
            $query_params[] = $args['user_id'];
        }

        if (!empty($args['search'])) {
            $where[] = '(message LIKE %s OR data LIKE %s OR user_role LIKE %s)';
            $search_term = '%' . $wpdb->esc_like($args['search']) . '%';
            $query_params[] = $search_term;
            $query_params[] = $search_term;
            $query_params[] = $search_term;
        }

        if (!empty($args['date_from'])) {
            $where[] = 'created_at >= %s';
            $query_params[] = $args['date_from'] . ' 00:00:00';
        }

        if (!empty($args['date_to'])) {
            $where[] = 'created_at <= %s';
            $query_params[] = $args['date_to'] . ' 23:59:59';
        }

        $where_clause = empty($where) ? '' : 'WHERE ' . implode(' AND ', $where);

        // Count total items for pagination
        $count_query = "SELECT COUNT(*) FROM {$this->table_name} $where_clause";
        if (!empty($query_params)) {
            $count_query = $wpdb->prepare($count_query, ...$query_params);
        }

        $total_items = $wpdb->get_var($count_query);

        // Prepare main query
        $orderby = \in_array($args['orderby'], array('id', 'created_at', 'event_type', 'object_type', 'severity', 'user_role')) ? $args['orderby'] : 'created_at';
        // Map old column names to new ones if needed
        if ($orderby === 'action') $orderby = 'object_type';
        if ($orderby === 'username') $orderby = 'user_role';
        $order = \strtoupper($args['order']) === 'ASC' ? 'ASC' : 'DESC';

        if ($args['per_page'] == -1) {
            // Export all records without pagination
            $query = "SELECT * FROM {$this->table_name} $where_clause ORDER BY $orderby $order";
            if (!empty($query_params)) {
                $query = $wpdb->prepare($query, ...$query_params);
            }
        } else {
            // Apply pagination
            $offset = ($args['page'] - 1) * $args['per_page'];
            $query = "SELECT * FROM {$this->table_name} $where_clause ORDER BY $orderby $order LIMIT %d OFFSET %d";
            $query_params[] = $args['per_page'];
            $query_params[] = $offset;
            $query = $wpdb->prepare($query, ...$query_params);
        }

        $items = $wpdb->get_results($query);

        // Unserialize data
        foreach ($items as &$item) {
            $item->data = \is_string($item->data) ? \maybe_unserialize($item->data) : $item->data;
            // Map column names for backward compatibility
            $item->details = $item->data;
            $item->summary = $item->message;
            // Only set action if it doesn't exist (for backward compatibility)
            if (!isset($item->action) || empty($item->action)) {
                $item->action = $item->object_type;
            }
            $item->username = $item->user_role;
        }

        return array(
            'items' => $items,
            'total_items' => $total_items,
            'total_pages' => \ceil($total_items / $args['per_page']),
            'current_page' => $args['page']
        );
    }

    /**
     * Clear audit logs from the database.
     *
     * @since    1.0.0
     * @param    array     $args    Optional arguments to filter what to clear
     * @return   int|false          Number of rows deleted or false on failure
     */
    public function clear_logs($args = array())
    {
        global $wpdb;

        $defaults = array(
            'event_type' => '',
            'severity' => '',
            'older_than' => ''
        );

        $args = \array_merge($defaults, $args);

        $where = array();
        $query_params = array();

        if (!empty($args['event_type'])) {
            $where[] = 'event_type = %s';
            $query_params[] = $args['event_type'];
        }

        if (!empty($args['severity'])) {
            $where[] = 'severity = %s';
            $query_params[] = $args['severity'];
        }

        if (!empty($args['older_than'])) {
            $where[] = 'created_at < %s';
            $query_params[] = date('Y-m-d H:i:s', strtotime('-' . intval($args['older_than']) . ' days'));
        }

        // Log the clearing of logs
        $user_id = \get_current_user_id();
        $this->log('audit', 'logs_cleared', 'system', 'Audit logs were cleared', $args, 'medium', $user_id);

        if (empty($where)) {
            // If no filters, truncate the table for better performance
            // But keep the log of clearing logs
            $last_log_id = $wpdb->insert_id;
            $result = $wpdb->query("DELETE FROM {$this->table_name} WHERE id != $last_log_id");
            \do_action('kura_ai_audit_logs_cleared');
            return $result;
        }

        $where_clause = 'WHERE ' . implode(' AND ', $where);
        $query = "DELETE FROM {$this->table_name} $where_clause";

        if (!empty($query_params)) {
            $query = $wpdb->prepare($query, ...$query_params);
        }

        $result = $wpdb->query($query);
        \do_action('kura_ai_audit_logs_cleared');
        return $result;
    }

    /**
     * Export logs to CSV.
     *
     * @since    1.0.0
     * @param    array     $args    Query arguments
     * @return   string             CSV content
     */
    public function export_logs_to_csv($args = array())
    {
        $logs = $this->get_logs(array_merge($args, array('per_page' => -1)));

        $output = fopen('php://output', 'w');
        ob_start();

        // Write header
        fputcsv($output, array(
            'ID',
            'Timestamp',
            'User ID',
            'Username',
            'IP Address',
            'Event Type',
            'Action',
            'Summary',
            'Details',
            'Severity'
        ));

        // Write data
        foreach ($logs['items'] as $log) {
            $details = is_array($log->details) ? json_encode($log->details) : $log->details;

            fputcsv($output, array(
                $log->id,
                $log->created_at,
                $log->user_id,
                $log->username,
                $log->ip_address,
                $log->event_type,
                $log->action,
                $log->summary,
                $details,
                $log->severity
            ));
        }

        fclose($output);
        $csv = ob_get_clean();

        return $csv;
    }

    /**
     * Export logs to JSON.
     *
     * @since    1.0.0
     * @param    array     $args    Query arguments
     * @return   string             JSON content
     */
    public function export_logs_to_json($args = array())
    {
        $logs = $this->get_logs(array_merge($args, array('per_page' => -1)));
        return json_encode($logs['items']);
    }

    /**
     * Send email report of audit logs.
     *
     * @since    1.0.0
     * @param    string    $period    The period to report on (daily, weekly, monthly)
     * @return   boolean              Whether the email was sent
     */
    public function send_email_report($period = 'daily')
    {
        $report_enabled = \get_option('kura_ai_audit_email_reporting', 'off');
        if ($report_enabled === 'off' || $report_enabled !== $period) {
            return false;
        }

        $recipients = \get_option('kura_ai_audit_email_recipients', \get_option('admin_email'));
        if (empty($recipients)) {
            return false;
        }

        // Determine date range based on period
        $end_date = \current_time('mysql');
        switch ($period) {
            case 'daily':
                $start_date = date('Y-m-d H:i:s', strtotime('-1 day', strtotime($end_date)));
                $subject = 'Daily Security Audit Log Report - ' . \get_bloginfo('name');
                break;
            case 'weekly':
                $start_date = date('Y-m-d H:i:s', strtotime('-1 week', strtotime($end_date)));
                $subject = 'Weekly Security Audit Log Report - ' . \get_bloginfo('name');
                break;
            case 'monthly':
                $start_date = date('Y-m-d H:i:s', strtotime('-1 month', strtotime($end_date)));
                $subject = 'Monthly Security Audit Log Report - ' . \get_bloginfo('name');
                break;
            default:
                $start_date = date('Y-m-d H:i:s', strtotime('-1 day', strtotime($end_date)));
                $subject = 'Security Audit Log Report - ' . \get_bloginfo('name');
        }

        // Get logs for the period
        $args = array(
            'date_from' => date('Y-m-d', strtotime($start_date)),
            'date_to' => date('Y-m-d', strtotime($end_date)),
            'per_page' => -1,
            'orderby' => 'severity',
            'order' => 'DESC'
        );

        $logs = $this->get_logs($args);

        // If no logs, don't send email
        if (empty($logs['items'])) {
            return false;
        }

        // Count logs by severity
        $severity_counts = array(
            'critical' => 0,
            'high' => 0,
            'medium' => 0,
            'low' => 0,
            'info' => 0
        );

        foreach ($logs['items'] as $log) {
            if (isset($severity_counts[$log['severity']])) {
                $severity_counts[$log['severity']]++;
            }
        }

        // Build email content
        $content = "<h2>Security Audit Log Report</h2>\n";
        $content .= "<p>Period: " . date('Y-m-d', strtotime($start_date)) . " to " . date('Y-m-d', strtotime($end_date)) . "</p>\n";
        $content .= "<p>Total Events: " . count($logs['items']) . "</p>\n";

        $content .= "<h3>Summary by Severity</h3>\n";
        $content .= "<ul>\n";
        foreach ($severity_counts as $severity => $count) {
            if ($count > 0) {
                $content .= "<li><strong>" . ucfirst($severity) . ":</strong> " . $count . "</li>\n";
            }
        }
        $content .= "</ul>\n";

        // Add critical and high severity events
        $high_severity_logs = array_filter($logs['items'], function($log) {
            return in_array($log['severity'], array('critical', 'high'));
        });

        if (!empty($high_severity_logs)) {
            $content .= "<h3>Critical & High Severity Events</h3>\n";
            $content .= "<table border='1' cellpadding='5' cellspacing='0'>\n";
            $content .= "<tr><th>Time</th><th>User</th><th>Event</th><th>Summary</th><th>Severity</th></tr>\n";

            foreach ($high_severity_logs as $log) {
                $content .= "<tr>\n";
                $content .= "<td>" . $log['created_at'] . "</td>\n";
                $content .= "<td>" . $log['user_role'] . "</td>\n";
                $content .= "<td>" . $log['event_type'] . " - " . $log['object_type'] . "</td>\n";
                $content .= "<td>" . $log['message'] . "</td>\n";
                $content .= "<td>" . $log['severity'] . "</td>\n";
                $content .= "</tr>\n";
            }

            $content .= "</table>\n";
        }

        // Add link to view all logs
        $content .= "<p><a href='" . \admin_url('admin.php?page=kura-ai-logs&tab=audit') . "'>View All Logs</a></p>\n";

        // Allow filtering of email content
        $content = \apply_filters('kura_ai_audit_email_content', $content, $logs['items'], $period);

        // Send email
        $headers = array('Content-Type: text/html; charset=UTF-8');
        $result = \wp_mail($recipients, $subject, $content, $headers);

        if ($result) {
            $this->log('audit', 'email_report_sent', "Audit log email report sent for period: $period", array(
                'recipients' => $recipients,
                'period' => $period,
                'log_count' => count($logs['items'])
            ), 'info');
        } else {
            $this->log('audit', 'email_report_failed', "Failed to send audit log email report for period: $period", array(
                'recipients' => $recipients,
                'period' => $period,
                'log_count' => count($logs['items'])
            ), 'medium');
        }

        return $result;
    }

    /**
     * Schedule email reports.
     *
     * @since    1.0.0
     */
    public function schedule_email_reports()
    {
        $reporting_frequency = \get_option('kura_ai_audit_email_reporting', 'off');
        
        // Clear existing schedules
        \wp_clear_scheduled_hook('kura_ai_audit_daily_report');
        \wp_clear_scheduled_hook('kura_ai_audit_weekly_report');
        \wp_clear_scheduled_hook('kura_ai_audit_monthly_report');
        
        // Schedule new report based on frequency
        switch ($reporting_frequency) {
            case 'daily':
                if (!\wp_next_scheduled('kura_ai_audit_daily_report')) {
                    \wp_schedule_event(strtotime('tomorrow 00:00:00'), 'daily', 'kura_ai_audit_daily_report');
                }
                break;
            case 'weekly':
                if (!\wp_next_scheduled('kura_ai_audit_weekly_report')) {
                    \wp_schedule_event(strtotime('next monday 00:00:00'), 'weekly', 'kura_ai_audit_weekly_report');
                }
                break;
            case 'monthly':
                if (!\wp_next_scheduled('kura_ai_audit_monthly_report')) {
                    \wp_schedule_event(strtotime('first day of next month 00:00:00'), 'monthly', 'kura_ai_audit_monthly_report');
                }
                break;
        }
    }

    /**
     * Log user created event.
     *
     * @since    1.0.0
     * @param    int       $user_id    The user ID
     */
    public function log_user_created($user_id)
    {
        $user = \get_userdata($user_id);
        if ($user) {
            $this->log('user', 'created', 'user', "User '{$user->user_login}' was created", array(
                'user_id' => $user_id,
                'user_email' => $user->user_email,
                'user_role' => implode(', ', $user->roles)
            ), 'medium');
        }
    }

    /**
     * Log user updated event.
     *
     * @since    1.0.0
     * @param    int       $user_id       The user ID
     * @param    object    $old_user_data The old user data
     */
    public function log_user_updated($user_id, $old_user_data)
    {
        $user = \get_userdata($user_id);
        if ($user) {
            $this->log('user', 'updated', 'user', "User '{$user->user_login}' was updated", array(
                'user_id' => $user_id,
                'user_email' => $user->user_email,
                'old_email' => $old_user_data->user_email
            ), 'low');
        }
    }

    /**
     * Log user deleted event.
     *
     * @since    1.0.0
     * @param    int       $user_id    The user ID
     */
    public function log_user_deleted($user_id)
    {
        $user = \get_userdata($user_id);
        if ($user) {
            $this->log('user', 'deleted', 'user', "User '{$user->user_login}' was deleted", array(
                'user_id' => $user_id,
                'user_email' => $user->user_email,
                'user_role' => \implode(', ', $user->roles)
            ), 'high');
        }
    }

    /**
     * Log role changed event.
     *
     * @since    1.0.0
     * @param    int       $user_id    The user ID
     * @param    string    $role       The new role
     * @param    array     $old_roles  The old roles
     */
    public function log_role_changed($user_id, $role, $old_roles)
    {
        $user = \get_userdata($user_id);
        if ($user) {
            $this->log('user', 'role_changed', 'user', "User '{$user->user_login}' role was changed to '$role'", array(
                'user_id' => $user_id,
                'new_role' => $role,
                'old_roles' => \implode(', ', $old_roles)
            ), 'medium');
        }
    }

    /**
     * Log successful login event.
     *
     * @since    1.0.0
     * @param    string    $user_login    The user login
     * @param    object    $user          The user object
     */
    public function log_successful_login($user_login, $user)
    {
        $this->log('auth', 'login_success', 'user', "User '{$user_login}' logged in successfully", array(
            'user_id' => $user->ID,
            'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : ''
        ), 'low');
    }

    /**
     * Log failed login event.
     *
     * @since    1.0.0
     * @param    string    $username    The username
     */
    public function log_failed_login($username)
    {
        $this->log('auth', 'login_failed', 'user', "Failed login attempt for user '{$username}'", array(
            'username' => $username,
            'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : ''
        ), 'medium');
    }

    /**
     * Log logout event.
     *
     * @since    1.0.0
     * @param    int       $user_id    The user ID
     */
    public function log_logout($user_id)
    {
        $user = \get_userdata($user_id);
        if ($user) {
            $this->log('auth', 'logout', 'user', "User '{$user->user_login}' logged out", array(
                'user_id' => $user_id
            ), 'info');
        }
    }

    /**
     * Log plugin activated event.
     *
     * @since    1.0.0
     * @param    string    $plugin    The plugin path
     * @param    bool      $network   Whether the plugin was network activated
     */
    public function log_plugin_activated($plugin, $network)
    {
        if (!\function_exists('get_plugin_data')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        $plugin_data = \get_plugin_data(WP_PLUGIN_DIR . '/' . $plugin);
        $plugin_name = !empty($plugin_data['Name']) ? $plugin_data['Name'] : $plugin;
        
        $this->log('plugin', 'activated', "Plugin '{$plugin_name}' was activated", array(
            'plugin' => $plugin,
            'plugin_version' => isset($plugin_data['Version']) ? $plugin_data['Version'] : '',
            'network_wide' => $network
        ), 'medium');
    }

    /**
     * Log plugin deactivated event.
     *
     * @since    1.0.0
     * @param    string    $plugin    The plugin path
     * @param    bool      $network   Whether the plugin was network deactivated
     */
    public function log_plugin_deactivated($plugin, $network)
    {
        if (!\function_exists('get_plugin_data')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        $plugin_data = \get_plugin_data(WP_PLUGIN_DIR . '/' . $plugin);
        $plugin_name = !empty($plugin_data['Name']) ? $plugin_data['Name'] : $plugin;
        
        $this->log('plugin', 'deactivated', "Plugin '{$plugin_name}' was deactivated", array(
            'plugin' => $plugin,
            'plugin_version' => isset($plugin_data['Version']) ? $plugin_data['Version'] : '',
            'network_wide' => $network
        ), 'medium');
    }

    /**
     * Log plugin deleted event.
     *
     * @since    1.0.0
     * @param    string    $plugin    The plugin path
     * @param    bool      $deleted   Whether the plugin was deleted
     */
    public function log_plugin_deleted($plugin, $deleted)
    {
        if ($deleted) {
            $this->log('plugin', 'deleted', "Plugin '{$plugin}' was deleted", array(
                'plugin' => $plugin
            ), 'high');
        }
    }

    /**
     * Log plugin update event.
     *
     * @since    1.0.0
     * @param    object    $upgrader    The upgrader object
     * @param    array     $options     The options array
     */
    public function log_plugin_update($upgrader, $options)
    {
        if ($options['action'] == 'update' && $options['type'] == 'plugin') {
            if (isset($options['plugins'])) {
                if (!\function_exists('get_plugin_data')) {
                    require_once ABSPATH . 'wp-admin/includes/plugin.php';
                }
                foreach ($options['plugins'] as $plugin) {
                    $plugin_data = \get_plugin_data(WP_PLUGIN_DIR . '/' . $plugin);
                    $plugin_name = !empty($plugin_data['Name']) ? $plugin_data['Name'] : $plugin;
                    
                    $this->log('plugin', 'updated', "Plugin '{$plugin_name}' was updated", array(
                        'plugin' => $plugin,
                        'plugin_version' => isset($plugin_data['Version']) ? $plugin_data['Version'] : ''
                    ), 'medium');
                }
            }
        }
    }

    /**
     * Log theme switched event.
     *
     * @since    1.0.0
     * @param    string    $new_name    The new theme name
     * @param    object    $new_theme   The new theme object
     * @param    object    $old_theme   The old theme object
     */
    public function log_theme_switched($new_name, $new_theme, $old_theme)
    {
        $old_name = $old_theme->get('Name');
        $this->log('theme', 'switched', "Theme switched from '{$old_name}' to '{$new_name}'", array(
            'old_theme' => $old_name,
            'new_theme' => $new_name,
            'old_version' => $old_theme->get('Version'),
            'new_version' => $new_theme->get('Version')
        ), 'medium');
    }

    /**
     * Log post created event.
     *
     * @since    1.0.0
     * @param    int       $post_id    The post ID
     * @param    object    $post       The post object
     * @param    bool      $update     Whether this is an update
     */
    public function log_post_created($post_id, $post, $update)
    {
        // Skip revisions and auto-saves
        if ($update || \wp_is_post_revision($post_id) || \wp_is_post_autosave($post_id)) {
            return;
        }
        
        // Get post type label
        $post_type_obj = \get_post_type_object($post->post_type);
        $post_type_label = $post_type_obj ? $post_type_obj->labels->singular_name : $post->post_type;
        
        $this->log('content', 'created', "New {$post_type_label} created: {$post->post_title}", array(
            'post_id' => $post_id,
            'post_type' => $post->post_type,
            'post_status' => $post->post_status
        ), 'medium');
    }

    /**
     * Log post updated event.
     *
     * @since    1.0.0
     * @param    int       $post_id    The post ID
     * @param    object    $post       The post object
     * @param    object    $post_before The post before update
     */
    public function log_post_updated($post_id, $post_after, $post_before)
    {
        if (\wp_is_post_revision($post_id) || \wp_is_post_autosave($post_id)) {
            return;
        }
        
        $post_type = \get_post_type_object($post_after->post_type);
        $post_type_label = $post_type ? $post_type->labels->singular_name : $post_after->post_type;
        
        $this->log('content', 'updated', "{$post_type_label} '{$post_after->post_title}' was updated", array(
            'post_id' => $post_id,
            'post_type' => $post_after->post_type,
            'post_status' => $post_after->post_status,
            'previous_status' => $post_before->post_status
        ), 'low');
    }

    /**
     * Log post deleted event.
     *
     * @since    1.0.0
     * @param    int       $post_id    The post ID
     */
    public function log_post_deleted($post_id)
    {
        if (\wp_is_post_revision($post_id) || \wp_is_post_autosave($post_id)) {
            return;
        }
        
        $post = \get_post($post_id);
        if ($post) {
            $post_type = \get_post_type_object($post->post_type);
            $post_type_label = $post_type ? $post_type->labels->singular_name : $post->post_type;
            
            $this->log('content', 'deleted', "{$post_type_label} '{$post->post_title}' was permanently deleted", array(
                'post_id' => $post_id,
                'post_type' => $post->post_type
            ), 'medium');
        }
    }

    /**
     * Log post trashed event.
     *
     * @since    1.0.0
     * @param    int       $post_id    The post ID
     */
    public function log_post_trashed($post_id)
    {
        if (\wp_is_post_revision($post_id) || \wp_is_post_autosave($post_id)) {
            return;
        }
        
        $post = \get_post($post_id);
        if ($post) {
            $post_type = \get_post_type_object($post->post_type);
            $post_type_label = $post_type ? $post_type->labels->singular_name : $post->post_type;
            
            $this->log('content', 'trashed', "{$post_type_label} '{$post->post_title}' was moved to trash", array(
                'post_id' => $post_id,
                'post_type' => $post->post_type
            ), 'low');
        }
    }

    /**
     * Log WordPress core update event.
     *
     * @since    1.0.0
     * @param    string    $version    The new WordPress version
     */
    public function log_core_update($version)
    {
        $this->log('core', 'updated', "WordPress core was updated to version {$version}", array(
            'new_version' => $version,
            'old_version' => \get_bloginfo('version')
        ), 'high');
    }

    /**
     * Log option updated event.
     *
     * @since    1.0.0
     * @param    string    $option    The option name
     * @param    mixed     $old_value The old option value
     * @param    mixed     $value     The new option value
     */
    public function log_option_updated($option, $old_value, $value)
    {
        // Skip transients and other frequently changed options
        $skip_options = array(
            '_transient_', '_site_transient_', 'cron', 'active_plugins',
            'recently_edited', 'widget_', 'theme_mods_', 'kura_ai_logs_',
            'kura_ai_audit_logs_', 'kura_ai_last_scan_'
        );
        
        foreach ($skip_options as $skip) {
            if (strpos($option, $skip) === 0) {
                return;
            }
        }
        
        // Only log significant options
        $significant_options = array(
            'siteurl', 'home', 'blogname', 'blogdescription', 'admin_email',
            'users_can_register', 'default_role', 'mailserver_url', 'mailserver_login',
            'mailserver_pass', 'mailserver_port', 'permalink_structure', 'upload_path',
            'upload_url_path', 'blog_public', 'default_category', 'default_email_category',
            'default_link_category', 'default_comment_status', 'default_ping_status',
            'comments_notify', 'moderation_notify', 'comment_moderation',
            'require_name_email', 'comment_whitelist', 'comment_max_links',
            'moderation_keys', 'blacklist_keys', 'show_avatars', 'avatar_rating',
            'avatar_default', 'enable_app', 'enable_xmlrpc', 'kura_ai_'
        );
        
        $log_option = false;
        foreach ($significant_options as $significant) {
            if (strpos($option, $significant) === 0) {
                $log_option = true;
                break;
            }
        }
        
        if ($log_option) {
            // Mask sensitive values
            $masked_options = array('mailserver_pass', 'api_key', 'secret', 'password', 'token');
            $masked_value = $value;
            $masked_old_value = $old_value;
            
            foreach ($masked_options as $masked) {
                if (strpos(strtolower($option), $masked) !== false) {
                    $masked_value = '********';
                    $masked_old_value = '********';
                    break;
                }
            }
            
            $this->log('option', 'updated', "Option '{$option}' was updated", array(
                'option' => $option,
                'old_value' => $masked_old_value,
                'new_value' => $masked_value
            ), 'medium');
        }
    }
}