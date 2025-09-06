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
        // Drop existing tables to ensure clean installation
        self::drop_existing_tables();
        
        // Create required database tables
        self::create_database_tables();

        // Set default options
        self::set_default_options();

        // Schedule cron jobs
        self::schedule_cron_jobs();
    }

    /**
     * Drop existing tables to ensure clean installation.
     *
     * @since    1.0.0
     */
    private static function drop_existing_tables()
    {
        global $wpdb;
        
        $tables = array(
            $wpdb->prefix . 'kura_ai_logs',
            $wpdb->prefix . 'kura_ai_api_keys'
        );
        
        foreach ($tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS $table");
        }
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

        // Create logs table
        $logs_table = $wpdb->prefix . 'kura_ai_logs';
        $logs_sql = "CREATE TABLE $logs_table (
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

        // Create API keys table
        $api_keys_table = $wpdb->prefix . 'kura_ai_api_keys';
        $api_keys_sql = "CREATE TABLE $api_keys_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            provider varchar(50) NOT NULL,
            api_key text NOT NULL,
            status varchar(20) NOT NULL DEFAULT 'active',
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY provider (provider)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($logs_sql);
        dbDelta($api_keys_sql);

        // Add default API key for OpenAI if not exists
        $existing_key = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $api_keys_table WHERE provider = %s",
            'openai'
        ));

        if ($existing_key == 0) {
            $wpdb->insert(
                $api_keys_table,
                array(
                    'provider' => 'openai',
                    'api_key' => '',
                    'status' => 'inactive'
                ),
                array('%s', '%s', '%s')
            );
        }
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
            'ai_service' => 'openai',
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