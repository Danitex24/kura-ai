<?php
/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @package    Kura_AI
 * @subpackage Kura_AI/includes
 * @author     Daniel Abughdyer <daniel@danovatesolutions.org>
 */
class Kura_AI_Activator
{

    /**
     * Short Description. (use period)
     *
     * Long Description.
     *
     * @since    1.0.0
     */
    public static function activate()
    {
        // Create required database tables
        self::create_database_tables();

        // Set default options
        self::set_default_options();

        // Schedule cron jobs
        self::schedule_cron_jobs();
    }

    /**
     * Create database tables needed by the plugin.
     *
     * @since    1.0.0
     */
    private static function create_database_tables()
    {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        $table_name = $wpdb->prefix . 'kura_ai_logs';

        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            log_type varchar(50) NOT NULL,
            log_message text NOT NULL,
            log_data longtext,
            severity varchar(20) NOT NULL DEFAULT 'info',
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY log_type (log_type),
            KEY severity (severity),
            KEY created_at (created_at)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        $table_name = $wpdb->prefix . 'kura_ai_audit_history';

        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            date datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
            suggestion longtext NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        dbDelta($sql);

        $table_name = $wpdb->prefix . 'kura_ai_activity_logs';

        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            type varchar(50) NOT NULL,
            product_id bigint(20) NOT NULL,
            data longtext,
            date datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY type (type),
            KEY product_id (product_id)
        ) $charset_collate;";

        dbDelta($sql);
    }

    /**
     * Set default plugin options.
     *
     * @since    1.0.0
     */
    private static function set_default_options()
    {
        $default_options = array(
            'scan_frequency' => 'daily',
            'email_notifications' => 1,
            'notification_email' => get_option('admin_email'),
            'enable_ai' => 0,
            'ai_service' => 'openai',
            'api_key' => '',
            'last_scan' => 0,
            'scan_results' => array()
        );

        if (!get_option('kura_ai_settings')) {
            update_option('kura_ai_settings', $default_options);
        }
    }

    /**
     * Schedule cron jobs for regular scans.
     *
     * @since    1.0.0
     */
    private static function schedule_cron_jobs()
    {
        if (!wp_next_scheduled('kura_ai_daily_scan')) {
            wp_schedule_event(time(), 'daily', 'kura_ai_daily_scan');
        }
    }
}