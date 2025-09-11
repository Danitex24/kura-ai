<?php
/**
 * The logging functionality of the plugin.
 *
 * @package    Kura_AI
 * @subpackage Kura_AI/includes
 * @author     Daniel Abughdyer <daniel@danovatesolutions.org>
 */

namespace Kura_AI;

// Define WordPress ABSPATH if not defined
if (!defined('ABSPATH')) {
    if (defined('\ABSPATH')) {
        define('ABSPATH', \ABSPATH);
    } else {
        define('ABSPATH', dirname(__FILE__, 3) . '/');
    }
}

// Check if WordPress functions are available
if (!function_exists('\maybe_serialize')) {
    require_once ABSPATH . 'wp-includes/functions.php';
}

// Check if WordPress time functions are available
if (!function_exists('\current_time')) {
    require_once ABSPATH . 'wp-includes/functions.php';
}

// Make sure WordPress database functions are available
if (!isset($GLOBALS['wpdb'])) {
    require_once ABSPATH . 'wp-includes/wp-db.php';
    global $wpdb;
}

// Define a wpdb class if it doesn't exist in this namespace
if (!class_exists('\Kura_AI\wpdb')) {
    /**
     * Proxy class for WordPress global $wpdb
     */
    class wpdb {
        /**
         * Table prefix
         * @var string
         */
        public $prefix = '';
        
        /**
         * Last insert ID
         * @var int
         */
        public $insert_id = 0;
        
        /**
         * Last error
         * @var string
         */
        public $last_error = '';
        
        /**
         * Constructor
         */
        public function __construct() {
            global $wpdb;
            if (isset($wpdb)) {
                $this->prefix = $wpdb->prefix;
            }
        }
        
        /**
         * Get a variable from the database
         * 
         * @param string $query The query to run
         * @return mixed The result
         */
        public function get_var($query) {
            global $wpdb;
            return $wpdb->get_var($query);
        }
        
        /**
         * Get a row from the database
         * 
         * @param string $query The query to run
         * @param string $output The output format
         * @return array|object|null The result
         */
        public function get_row($query, $output = OBJECT) {
            global $wpdb;
            return $wpdb->get_row($query, $output);
        }
        
        /**
         * Get results from the database
         * 
         * @param string $query The query to run
         * @param string $output The output format
         * @return array|object|null The results
         */
        public function get_results($query, $output = OBJECT) {
            global $wpdb;
            return $wpdb->get_results($query, $output);
        }
        
        /**
         * Insert data into the database
         * 
         * @param string $table The table to insert into
         * @param array $data The data to insert
         * @return int|false The number of rows affected
         */
        public function insert($table, $data) {
            global $wpdb;
            $result = $wpdb->insert($table, $data);
            if ($result !== false) {
                $this->insert_id = $wpdb->insert_id;
            }
            return $result;
        }
        
        /**
         * Update data in the database
         * 
         * @param string $table The table to update
         * @param array $data The data to update
         * @param array $where The where clause
         * @return int|false The number of rows affected
         */
        public function update($table, $data, $where) {
            global $wpdb;
            return $wpdb->update($table, $data, $where);
        }
        
        /**
         * Run a query
         * 
         * @param string $query The query to run
         * @return int|false The number of rows affected
         */
        public function query($query) {
            global $wpdb;
            return $wpdb->query($query);
        }
        
        /**
         * Prepare a query
         * 
         * @param string $query The query to prepare
         * @param mixed $args The arguments to prepare with
         * @return string The prepared query
         */
        public function prepare($query, ...$args) {
            global $wpdb;
            // Make sure we have arguments and the query has placeholders before calling prepare
            // This prevents WordPress warnings about incorrect usage of wpdb::prepare
            if (!empty($args) && count($args) > 0 && strpos($query, '%') !== false) {
                return $wpdb->prepare($query, ...$args);
            }
            // If no placeholders or no args, just return the query
            // This avoids the WordPress warning about prepare without placeholders
            return $query;
        }
        
        /**
         * Escape like wildcards
         * 
         * @param string $text The text to escape
         * @return string The escaped text
         */
        public function esc_like($text) {
            global $wpdb;
            return $wpdb->esc_like($text);
        }
        
        /**
         * Get the insert ID
         * 
         * @return int The insert ID
         */
        public function __get($name) {
            global $wpdb;
            if ($name === 'insert_id') {
                return $wpdb->insert_id;
            } elseif ($name === 'last_error') {
                return $wpdb->last_error;
            } elseif ($name === 'prefix') {
                return $wpdb->prefix;
            }
            return null;
        }
    }
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
        $wpdb = new \Kura_AI\wpdb();

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
        $wpdb = new \Kura_AI\wpdb();

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
            // Create placeholders based on the number of parameters
            $placeholders = array_fill(0, count($query_params), '%s');
            $count_query = $wpdb->prepare($count_query, ...$query_params);
        }

        $total_items = $wpdb->get_var($count_query);

        // Prepare main query
        $orderby = \in_array($args['orderby'], array('id', 'log_type', 'severity', 'created_at')) ? $args['orderby'] : 'created_at';
        $order = strtoupper($args['order']) === 'ASC' ? 'ASC' : 'DESC';

        if ($args['per_page'] == -1) {
            // Export all records without pagination
            $query = "SELECT * FROM $table_name $where_clause ORDER BY $orderby $order";
            if (!empty($query_params)) {
                // Create placeholders based on the number of parameters
                $placeholders = array_fill(0, count($query_params), '%s');
                $query = $wpdb->prepare($query, ...$query_params);
            }
        } else {
            // Apply pagination
            $offset = ($args['page'] - 1) * $args['per_page'];
            $query = "SELECT * FROM $table_name $where_clause ORDER BY $orderby $order LIMIT %d OFFSET %d";
            $query_params[] = $args['per_page'];
            $query_params[] = $offset;
            $query = $wpdb->prepare($query, ...$query_params);
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
        $wpdb = new \Kura_AI\wpdb();

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
            // Create placeholders based on the number of parameters
            $placeholders = array_fill(0, count($query_params), '%s');
            $query = $wpdb->prepare($query, ...$query_params);
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