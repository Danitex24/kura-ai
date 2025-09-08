<?php
/**
 * Plugin Name: KuraAI - AI Security
 * Plugin URI: https://www.danovatesolutions.org/kura-ai
 * Description: An AI-powered WordPress security plugin that monitors vulnerabilities and provides intelligent fixes.
 * Version: 1.0.0
 * Author: Daniel Abughdyer
 * Author URI: https://www.danovatesolutions.org
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: kura-ai
 * Domain Path: /languages
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Define plugin constants
define('KURA_AI_VERSION', '1.0.1');

// WordPress functions will be available when plugin is loaded
// These constants will be properly defined by WordPress
define('KURA_AI_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('KURA_AI_PLUGIN_URL', plugin_dir_url(__FILE__));
define('KURA_AI_BASENAME', plugin_basename(__FILE__));

/**
 * The code that runs during plugin activation.
 */
require_once plugin_dir_path(__FILE__) . 'includes/class-kura-ai-activator.php';
register_activation_hook(__FILE__, array('Kura_AI\Kura_AI_Activator', 'activate'));

/**
 * Ensure required tables exist on plugin load
 */
function kura_ai_ensure_tables() {
    global $wpdb;
    
    $charset_collate = $wpdb->get_charset_collate();
    
    // Check and create security_reports table
    $security_reports_table = $wpdb->prefix . 'kura_ai_security_reports';
    $security_reports_exists = $wpdb->get_var("SHOW TABLES LIKE '$security_reports_table'");
    
    if (!$security_reports_exists) {
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
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($security_reports_sql);
    }
    
    // Check and create malware_patterns table
    $malware_patterns_table = $wpdb->prefix . 'kura_ai_malware_patterns';
    $malware_patterns_exists = $wpdb->get_var("SHOW TABLES LIKE '$malware_patterns_table'");
    
    if (!$malware_patterns_exists) {
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
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($malware_patterns_sql);
    }
    
    // Check and create logs table
    $logs_table = $wpdb->prefix . 'kura_ai_logs';
    $logs_exists = $wpdb->get_var("SHOW TABLES LIKE '$logs_table'");
    
    if (!$logs_exists) {
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
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($logs_sql);
    }
    
    // Check and create api_keys table
    $api_keys_table = $wpdb->prefix . 'kura_ai_api_keys';
    $api_keys_exists = $wpdb->get_var("SHOW TABLES LIKE '$api_keys_table'");
    
    if (!$api_keys_exists) {
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
    
    // Check and create file_versions table
    $file_versions_table = $wpdb->prefix . 'kura_ai_file_versions';
    $file_versions_exists = $wpdb->get_var("SHOW TABLES LIKE '$file_versions_table'");
    
    if (!$file_versions_exists) {
        $file_versions_sql = "CREATE TABLE $file_versions_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            file_path varchar(255) NOT NULL,
            content longtext NOT NULL,
            hash varchar(64) NOT NULL,
            description text,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY file_path (file_path),
            KEY hash (hash),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($file_versions_sql);
    }
    
    // Check and create analytics table
    $analytics_table = $wpdb->prefix . 'kura_ai_analytics';
    $analytics_exists = $wpdb->get_var("SHOW TABLES LIKE '$analytics_table'");
    
    if (!$analytics_exists) {
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
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY analysis_level (analysis_level),
            KEY pass_status (pass_status),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($analytics_sql);
        
        // Insert sample data for testing if table was just created
        $sample_data = array(
            array(
                'user_id' => 1,
                'code_length' => 150,
                'analysis_time' => 2.5,
                'analysis_level' => 'standard',
                'health_score' => 85.5,
                'pass_status' => 'pass',
                'analysis_type' => 'security',
                'provider' => 'openai'
            ),
            array(
                'user_id' => 1,
                'code_length' => 200,
                'analysis_time' => 3.2,
                'analysis_level' => 'advanced',
                'health_score' => 92.0,
                'pass_status' => 'pass',
                'analysis_type' => 'security',
                'provider' => 'openai'
            ),
            array(
                'user_id' => 1,
                'code_length' => 75,
                'analysis_time' => 1.8,
                'analysis_level' => 'basic',
                'health_score' => 65.0,
                'pass_status' => 'fail',
                'analysis_type' => 'security',
                'provider' => 'openai'
            ),
            array(
                'user_id' => 1,
                'code_length' => 300,
                'analysis_time' => 4.1,
                'analysis_level' => 'advanced',
                'health_score' => 78.5,
                'pass_status' => 'pass',
                'analysis_type' => 'security',
                'provider' => 'openai'
            ),
            array(
                'user_id' => 1,
                'code_length' => 120,
                'analysis_time' => 2.0,
                'analysis_level' => 'standard',
                'health_score' => 45.0,
                'pass_status' => 'fail',
                'analysis_type' => 'security',
                'provider' => 'openai'
            )
        );
        
        foreach ($sample_data as $data) {
            $wpdb->insert(
                $analytics_table,
                $data,
                array('%d', '%d', '%f', '%s', '%f', '%s', '%s', '%s')
            );
        }
    }
}

// Run table check on admin_init to ensure database is available
add_action('admin_init', 'kura_ai_ensure_tables');

/**
 * The code that runs during plugin deactivation.
 */
require_once plugin_dir_path(__FILE__) . 'includes/class-kura-ai-deactivator.php';
register_deactivation_hook(__FILE__, array('Kura_AI\Kura_AI_Deactivator', 'deactivate'));

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require_once KURA_AI_PLUGIN_DIR . 'includes/class-kura-ai.php';

// Helper functions
function get_core_files($directory)
{
    $wp_files = array();
    $wp_dir = @\dir($directory);

    if ($wp_dir) {
        while (($file = $wp_dir->read()) !== false) {
            if ('.' === $file[0]) {
                continue;
            }
            if (\is_dir($directory . $file)) {
                $wp_files = \array_merge($wp_files, \get_core_files($directory . $file . '/'));
            } else {
                $wp_files[] = $file;
            }
        }
        $wp_dir->close();
    }

    return $wp_files;
}

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_kura_ai()
{
    $plugin = new Kura_AI\Kura_AI();
    $plugin->run();
}
\run_kura_ai();