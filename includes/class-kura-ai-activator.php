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

namespace Kura_AI;

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
            $wpdb->prefix . 'kura_ai_api_keys',
            $wpdb->prefix . 'kura_ai_malware_patterns',
            $wpdb->prefix . 'kura_ai_file_integrity',
            $wpdb->prefix . 'kura_ai_user_activity',
            $wpdb->prefix . 'kura_ai_api_security',
            $wpdb->prefix . 'kura_ai_security_reports',
            $wpdb->prefix . 'kura_ai_vulnerabilities',
            $wpdb->prefix . 'kura_ai_hardening_rules',
            $wpdb->prefix . 'kura_ai_ai_analysis',
            $wpdb->prefix . 'kura_ai_file_versions',
            $wpdb->prefix . 'kura_ai_feedback',
            $wpdb->prefix . 'kura_ai_analytics'
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

        // Create malware patterns table
        $malware_patterns_table = $wpdb->prefix . 'kura_ai_malware_patterns';
        $malware_patterns_sql = "CREATE TABLE $malware_patterns_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            pattern_name varchar(100) NOT NULL,
            pattern_type varchar(50) NOT NULL,
            pattern_signature text NOT NULL,
            severity varchar(20) NOT NULL DEFAULT 'medium',
            ai_confidence float NOT NULL DEFAULT 0,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY pattern_type (pattern_type),
            KEY severity (severity)
        ) $charset_collate;";

        // Create file integrity table
        $file_integrity_table = $wpdb->prefix . 'kura_ai_file_integrity';
        $file_integrity_sql = "CREATE TABLE $file_integrity_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            file_path varchar(255) NOT NULL,
            file_hash varchar(64) NOT NULL,
            file_size bigint(20) NOT NULL,
            last_modified datetime NOT NULL,
            version_history longtext,
            status varchar(20) NOT NULL DEFAULT 'unchanged',
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY file_path (file_path),
            KEY status (status)
        ) $charset_collate;";

        // Create user activity table
        $user_activity_table = $wpdb->prefix . 'kura_ai_user_activity';
        $user_activity_sql = "CREATE TABLE $user_activity_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            activity_type varchar(50) NOT NULL,
            activity_data longtext,
            ip_address varchar(45),
            user_agent text,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY user_id (user_id),
            KEY activity_type (activity_type),
            KEY created_at (created_at)
        ) $charset_collate;";

        // Create API security table
        $api_security_table = $wpdb->prefix . 'kura_ai_api_security';
        $api_security_sql = "CREATE TABLE $api_security_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            endpoint varchar(255) NOT NULL,
            request_method varchar(10) NOT NULL,
            request_count int NOT NULL DEFAULT 0,
            last_request datetime,
            rate_limit int NOT NULL DEFAULT 100,
            blocked_ips longtext,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY endpoint_method (endpoint(191), request_method)
        ) $charset_collate;";

        // Create security reports table
        $security_reports_table = $wpdb->prefix . 'kura_ai_security_reports';
        $security_reports_sql = "CREATE TABLE $security_reports_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            report_type varchar(50) NOT NULL,
            report_data longtext NOT NULL,
            compliance_standard varchar(50),
            compliance_score float,
            recommendations longtext,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY report_type (report_type),
            KEY compliance_standard (compliance_standard)
        ) $charset_collate;";

        // Create vulnerabilities table
        $vulnerabilities_table = $wpdb->prefix . 'kura_ai_vulnerabilities';
        $vulnerabilities_sql = "CREATE TABLE $vulnerabilities_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            vulnerability_type varchar(50) NOT NULL,
            cve_id varchar(20),
            severity varchar(20) NOT NULL DEFAULT 'medium',
            affected_component varchar(255),
            description text,
            fix_available boolean DEFAULT false,
            fix_description text,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY vulnerability_type (vulnerability_type),
            KEY severity (severity),
            KEY cve_id (cve_id)
        ) $charset_collate;";

        // Create hardening rules table
        $hardening_rules_table = $wpdb->prefix . 'kura_ai_hardening_rules';
        $hardening_rules_sql = "CREATE TABLE $hardening_rules_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            rule_name varchar(100) NOT NULL,
            rule_type varchar(50) NOT NULL,
            rule_content text NOT NULL,
            is_active boolean DEFAULT true,
            priority int NOT NULL DEFAULT 10,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY rule_type (rule_type),
            KEY is_active (is_active)
        ) $charset_collate;";

        // Create AI analysis table
        $ai_analysis_table = $wpdb->prefix . 'kura_ai_ai_analysis';
        $ai_analysis_sql = "CREATE TABLE $ai_analysis_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            analysis_type varchar(50) NOT NULL,
            provider varchar(50) NOT NULL,
            analysis_data longtext NOT NULL,
            confidence_score float,
            execution_time float,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY analysis_type (analysis_type),
            KEY provider (provider)
        ) $charset_collate;";

        // Create file versions table
        $file_versions_table = $wpdb->prefix . 'kura_ai_file_versions';
        $file_versions_sql = "CREATE TABLE $file_versions_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            file_path varchar(255) NOT NULL,
            content longtext NOT NULL,
            hash varchar(64) NOT NULL,
            description text,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY file_path (file_path),
            KEY hash (hash),
            KEY created_at (created_at)
        ) $charset_collate;";

        // Create feedback table
        $feedback_table = $wpdb->prefix . 'kura_ai_feedback';
        $feedback_sql = "CREATE TABLE $feedback_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            analysis_id bigint(20) NOT NULL DEFAULT 0,
            user_id bigint(20) NOT NULL,
            feedback varchar(20) NOT NULL,
            comment text,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY analysis_id (analysis_id),
            KEY user_id (user_id),
            KEY feedback (feedback)
        ) $charset_collate;";

        // Create analytics table
        $analytics_table = $wpdb->prefix . 'kura_ai_analytics';
        $analytics_sql = "CREATE TABLE $analytics_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            code_length int NOT NULL,
            analysis_time float NOT NULL,
            analysis_level varchar(20) NOT NULL DEFAULT 'standard',
            health_score float NOT NULL DEFAULT 0,
            pass_status varchar(10) NOT NULL DEFAULT 'fail',
            analysis_type varchar(50) NOT NULL DEFAULT 'security',
            provider varchar(50) NOT NULL DEFAULT 'openai',
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY user_id (user_id),
            KEY analysis_level (analysis_level),
            KEY pass_status (pass_status),
            KEY created_at (created_at)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($logs_sql);
        dbDelta($api_keys_sql);
        dbDelta($malware_patterns_sql);
        dbDelta($file_integrity_sql);
        dbDelta($user_activity_sql);
        dbDelta($api_security_sql);
        dbDelta($security_reports_sql);
        dbDelta($vulnerabilities_sql);
        dbDelta($hardening_rules_sql);
        dbDelta($ai_analysis_sql);
        dbDelta($file_versions_sql);
        dbDelta($feedback_sql);
        dbDelta($analytics_sql);

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
            'notification_email' => \get_option('admin_email'),
            'ai_service' => 'openai',
            'last_scan' => 0,
            'scan_results' => array(),
            'malware_detection' => array(
                'enabled' => true,
                'ai_threshold' => 0.8,
                'scan_frequency' => 'daily'
            ),
            'file_monitoring' => array(
                'enabled' => true,
                'monitored_directories' => array(\ABSPATH),
                'exclude_patterns' => array('*.log', '*.tmp')
            ),
            'user_security' => array(
                'enabled' => true,
                '2fa_required' => false,
                'activity_monitoring' => true
            ),
            'api_security' => array(
                'enabled' => true,
                'rate_limiting' => true,
                'default_rate_limit' => 100
            ),
            'compliance_reporting' => array(
                'enabled' => true,
                'standards' => array('PCI-DSS', 'GDPR'),
                'report_frequency' => 'weekly'
            ),
            'vulnerability_assessment' => array(
                'enabled' => true,
                'auto_update' => true,
                'notification_threshold' => 'high'
            ),
            'security_hardening' => array(
                'enabled' => true,
                'auto_fix' => false,
                'backup_before_fix' => true
            ),
            'ai_analysis' => array(
                'enabled' => true,
                'providers' => array('openai'),
                'confidence_threshold' => 0.7
            )
        );

        if (!\get_option('kura_ai_settings')) {
            \update_option('kura_ai_settings', $default_options);
        }
    }

    /**
     * Schedule cron jobs for regular scans.
     *
     * @since    1.0.0
     */
    private static function schedule_cron_jobs()
    {
        // Reduced cron events to prevent overload and "could_not_set" errors
        $schedules = array(
            'kura_ai_daily_scan' => 'daily',
            'kura_ai_cleanup_logs' => 'daily'
        );

        // Space out the scheduling to prevent conflicts
        $delay = 0;
        foreach ($schedules as $hook => $frequency) {
            if (!wp_next_scheduled($hook)) {
                // Add delay between events to prevent cron conflicts
                $schedule_time = time() + $delay;
                $result = wp_schedule_event($schedule_time, $frequency, $hook);
                
                // Log if scheduling fails
                if ($result === false) {
                    error_log("Kura AI: Failed to schedule cron event: $hook");
                }
                
                $delay += 300; // 5 minute delay between events
            }
        }
    }
}