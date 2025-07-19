<?php
/**
 * Plugin Name: KuraAI - AI-Powered WordPress Security
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
define('KURA_AI_VERSION', '1.0.0');
define('KURA_AI_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('KURA_AI_PLUGIN_URL', plugin_dir_url(__FILE__));
define('KURA_AI_BASENAME', plugin_basename(__FILE__));

// AI Integrations constants
define('KURA_AI_OPENAI_CLIENT_ID', 'your_client_id_here');
define('KURA_AI_OPENAI_CLIENT_SECRET', 'your_client_secret_here');
define('KURA_AI_GEMINI_CLIENT_ID', 'your_client_id_here');
define('KURA_AI_GEMINI_CLIENT_SECRET', 'your_client_secret_here');

/**
 * The code that runs during plugin activation.
 */
require_once KURA_AI_PLUGIN_DIR . 'includes/class-kura-ai-activator.php';
register_activation_hook(__FILE__, array('Kura_AI_Activator', 'activate'));

/**
 * The code that runs during plugin deactivation.
 */
require_once KURA_AI_PLUGIN_DIR . 'includes/class-kura-ai-deactivator.php';
register_deactivation_hook(__FILE__, array('Kura_AI_Deactivator', 'deactivate'));

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require_once KURA_AI_PLUGIN_DIR . 'includes/class-kura-ai.php';
// add helper functions
function get_core_files($directory)
{
    $wp_files = array();
    $wp_dir = @dir($directory);

    if ($wp_dir) {
        while (($file = $wp_dir->read()) !== false) {
            if ('.' === $file[0]) {
                continue;
            }
            if (is_dir($directory . $file)) {
                $wp_files = array_merge($wp_files, get_core_files($directory . $file . '/'));
            } else {
                $wp_files[] = $file;
            }
        }
        $wp_dir->close();
    }

    return $wp_files;
}
// catch all plugin errors in one file
require_once KURA_AI_PLUGIN_DIR . 'includes/class-kura-ai-error-handler.php';
// Register the error handler
set_error_handler(array('Kura_AI_Error_Handler', 'handle_error'));

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
    $plugin = new Kura_AI();
    $plugin->run();
}
run_kura_ai();