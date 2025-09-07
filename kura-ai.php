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
define('KURA_AI_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('KURA_AI_PLUGIN_URL', plugin_dir_url(__FILE__));
define('KURA_AI_BASENAME', plugin_basename(__FILE__));

// Function stubs removed to prevent conflicts with WordPress core functions
// WordPress will provide all necessary functions when the plugin is loaded

/**
 * The code that runs during plugin activation.
 */
require_once KURA_AI_PLUGIN_DIR . 'includes/class-kura-ai-activator.php';
register_activation_hook(__FILE__, array('Kura_AI\Kura_AI_Activator', 'activate'));

/**
 * Ensure required tables exist on plugin load
 */
function kura_ai_ensure_tables() {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'kura_ai_file_versions';
    
    // Check if table exists
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'");
    
    if (!$table_exists) {
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
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
        dbDelta($sql);
    }
}

// Run table check on admin_init to ensure database is available
add_action('admin_init', 'kura_ai_ensure_tables');

/**
 * The code that runs during plugin deactivation.
 */
require_once KURA_AI_PLUGIN_DIR . 'includes/class-kura-ai-deactivator.php';
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