<?php
/**
 * The logging functionality of the plugin.
 *
 * @package    Kura_AI
 * @subpackage Kura_AI/includes
 * @author     Daniel Abughdyer <daniel@danovatesolutions.org>
 */

namespace Kura_AI;

// Exit if accessed directly
if (!defined('\ABSPATH')) {
    exit;
}

class Kura_AI_Logger
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
    }

    /**
     * Log a message to the database.
     *
     * @since    1.0.0
     * @param    string    $type       The log type (scan, fix, alert, etc.)
     * @param    string    $message    The log message
     * @param    array     $data       Additional data to store with the log
     * @param    string    $severity   The severity level (info, warning, error, critical)
     * @return   int|false             The log ID or false on failure
     */
    public function log($type, $message, $data = array(), $severity = 'info')
    {
        return $this->log_event($type, $message, $data, $severity);
    }
    
    /**
     * Log an event to the database (alias for log method).
     *
     * @since    1.0.0
     * @param    string    $type       The log type (scan, fix, alert, etc.)
     * @param    string    $message    The log message
     * @param    array     $data       Additional data to store with the log
     * @param    string    $severity   The severity level (info, warning, error, critical)
     * @return   int|false             The log ID or false on failure
     */
    public function log_event($type, $message, $data = array(), $severity = 'info')
    {
        global $wpdb;

        $table_name = $wpdb->prefix . 'kura_ai_logs';

        $insert_data = array(
            'log_type' => \sanitize_text_field($type),
            'log_message' => \sanitize_text_field($message),
            'log_data' => \maybe_serialize($data),
            'severity' => \in_array($severity, array('info', 'warning', 'error', 'critical')) ? $severity : 'info',
            'created_at' => \current_time('mysql')
        );

        $result = $wpdb->insert($table_name, $insert_data);

        if ($result === false) {
            \error_log('KuraAI: Failed to log message - ' . $wpdb->last_error);
            return false;
        }

        return $wpdb->insert_id;
    }

    /**
     * Get logs from the database.
     *
     * @since    1.0.0
     * @param    array     $args    Query arguments
     * @return   array              Array of log entries
     */
    public function get_logs($args = array())
    {
        global $wpdb;

        $table_name = $wpdb->prefix . 'kura_ai_logs';

        $defaults = array(
            'type' => '',
            'severity' => '',
            'search' => '',
            'per_page' => 20,
            'page' => 1,
            'orderby' => 'created_at',
            'order' => 'DESC'
        );

        $args = array_merge($defaults, $args);

        $where = array();
        $query_params = array();

        if (!empty($args['type'])) {
            $where[] = 'log_type = %s';
            $query_params[] = $args['type'];
        }

        if (!empty($args['severity'])) {
            $where[] = 'severity = %s';
            $query_params[] = $args['severity'];
        }

        if (!empty($args['search'])) {
            $where[] = '(log_message LIKE %s OR log_data LIKE %s)';
            $search_term = '%' . $wpdb->esc_like($args['search']) . '%';
            $query_params[] = $search_term;
            $query_params[] = $search_term;
        }

        $where_clause = empty($where) ? '' : 'WHERE ' . implode(' AND ', $where);

        // Count total items for pagination
        $count_query = "SELECT COUNT(*) FROM $table_name $where_clause";
        if (!empty($query_params)) {
            $count_query = $wpdb->prepare($count_query, $query_params);
        }

        $total_items = $wpdb->get_var($count_query);

        // Prepare main query
        $orderby = \in_array($args['orderby'], array('id', 'log_type', 'severity', 'created_at')) ? $args['orderby'] : 'created_at';
        $order = strtoupper($args['order']) === 'ASC' ? 'ASC' : 'DESC';

        if ($args['per_page'] == -1) {
            // Export all records without pagination
            $query = "SELECT * FROM $table_name $where_clause ORDER BY $orderby $order";
            if (!empty($query_params)) {
                $query = $wpdb->prepare($query, $query_params);
            }
        } else {
            // Apply pagination
            $offset = ($args['page'] - 1) * $args['per_page'];
            $query = "SELECT * FROM $table_name $where_clause ORDER BY $orderby $order LIMIT %d OFFSET %d";
            $query_params[] = $args['per_page'];
            $query_params[] = $offset;
            $query = $wpdb->prepare($query, $query_params);
        }
        $items = $wpdb->get_results($query, ARRAY_A);

        // Unserialize log data
        foreach ($items as &$item) {
            $item['log_data'] = is_string($item['log_data']) ? unserialize($item['log_data']) : $item['log_data'];
        }

        return array(
            'items' => $items,
            'total_items' => $total_items,
            'total_pages' => ceil($total_items / $args['per_page']),
            'current_page' => $args['page']
        );
    }

    /**
     * Clear logs from the database.
     *
     * @since    1.0.0
     * @param    array     $args    Optional arguments to filter what to clear
     * @return   int|false          Number of rows deleted or false on failure
     */
    public function clear_logs($args = array())
    {
        global $wpdb;

        $table_name = $wpdb->prefix . 'kura_ai_logs';

        $defaults = array(
            'type' => '',
            'severity' => '',
            'older_than' => ''
        );

        $args = array_merge($defaults, $args);

        $where = array();
        $query_params = array();

        if (!empty($args['type'])) {
            $where[] = 'log_type = %s';
            $query_params[] = $args['type'];
        }

        if (!empty($args['severity'])) {
            $where[] = 'severity = %s';
            $query_params[] = $args['severity'];
        }

        if (!empty($args['older_than'])) {
            $where[] = 'created_at < %s';
            $query_params[] = \date('Y-m-d H:i:s', \strtotime($args['older_than']));
        }

        if (empty($where)) {
            // If no filters, truncate the table for better performance
            return $wpdb->query("TRUNCATE TABLE $table_name");
        }

        $where_clause = 'WHERE ' . implode(' AND ', $where);
        $query = "DELETE FROM $table_name $where_clause";

        if (!empty($query_params)) {
            $query = $wpdb->prepare($query, $query_params);
        }

        return $wpdb->query($query);
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
        $logs = $this->get_logs($args);

        $output = fopen('php://output', 'w');
        ob_start();

        // Write header
        fputcsv($output, array(
            'ID',
            'Type',
            'Severity',
            'Message',
            'Data',
            'Date'
        ));

        // Write data
        foreach ($logs['items'] as $log) {
            $data = \is_array($log['log_data']) ? \json_encode($log['log_data']) : $log['log_data'];

            fputcsv($output, array(
                $log['id'],
                $log['log_type'],
                $log['severity'],
                $log['log_message'],
                $data,
                $log['created_at']
            ));
        }

        fclose($output);
        $csv = ob_get_clean();

        return $csv;
    }
}