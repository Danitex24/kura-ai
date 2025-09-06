<?php
/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Kura_AI
 * @subpackage Kura_AI/includes
 * @author     Daniel Abughdyer <daniel@danovatesolutions.org>
 */

namespace Kura_AI;

// No need to import the admin class with namespace as it's in the same namespace

class Kura_AI {

    protected $loader;
    protected $plugin_name;
    protected $version;

    public function __construct() {
        $this->version = KURA_AI_VERSION;
        $this->plugin_name = 'kura-ai';

        $this->load_dependencies();
        $this->set_locale();
        $this->define_admin_hooks();
        $this->define_public_hooks();
    }

    private function load_dependencies() {
        // Core functionality
        require_once KURA_AI_PLUGIN_DIR . 'includes/class-kura-ai-loader.php';
        require_once KURA_AI_PLUGIN_DIR . 'includes/class-kura-ai-i18n.php';
        
        // Security components
        require_once KURA_AI_PLUGIN_DIR . 'includes/class-kura-ai-security-scanner.php';
        require_once KURA_AI_PLUGIN_DIR . 'includes/class-kura-ai-logger.php';
        require_once KURA_AI_PLUGIN_DIR . 'includes/class-kura-ai-notifier.php';
        require_once KURA_AI_PLUGIN_DIR . 'includes/class-kura-ai-malware-detector.php';
        
        // AI Integration
        require_once KURA_AI_PLUGIN_DIR . 'includes/class-kura-ai-ai-handler.php';
        require_once KURA_AI_PLUGIN_DIR . 'includes/ai-integrations/class-kura-ai-interface.php';
        require_once KURA_AI_PLUGIN_DIR . 'includes/ai-integrations/class-kura-ai-openai.php';
        require_once KURA_AI_PLUGIN_DIR . 'includes/ai-integrations/class-kura-ai-gemini.php';
        require_once KURA_AI_PLUGIN_DIR . 'includes/ai-integrations/class-kura-ai-claude.php';
        
        // Admin and public interfaces
        require_once KURA_AI_PLUGIN_DIR . 'admin/class-kura-ai-admin.php';
        require_once KURA_AI_PLUGIN_DIR . 'public/class-kura-ai-public.php';

        $this->loader = new Kura_AI_Loader();
    }

    private function set_locale() {
        $plugin_i18n = new Kura_AI_i18n();
        $this->loader->add_action('plugins_loaded', $plugin_i18n, 'load_plugin_textdomain');
    }

    private function define_admin_hooks() {
        $plugin_admin = new Kura_AI_Admin($this->get_plugin_name(), $this->get_version());

        // Core admin hooks
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_styles');
        $this->loader->add_action('admin_menu', $plugin_admin, 'add_admin_menu');

        // AJAX handlers are registered in the admin class via register_ajax_actions()
        
        // All AJAX handlers are registered in the admin class via register_ajax_actions()
    }

    private function define_public_hooks() {
        $plugin_public = new Kura_AI_Public($this->get_plugin_name(), $this->get_version());
        $this->loader->add_action('\wp_enqueue_scripts', $plugin_public, 'enqueue_styles');
        $this->loader->add_action('\wp_enqueue_scripts', $plugin_public, 'enqueue_scripts');
    }

    public function run() {
        $this->loader->run();
    }

    public function get_plugin_name() {
        return $this->plugin_name;
    }

    public function get_loader() {
        return $this->loader;
    }

    public function get_version() {
        return $this->version;
    }
}
