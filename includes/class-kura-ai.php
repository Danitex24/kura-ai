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
class Kura_AI
{

    /**
     * The loader that's responsible for maintaining and registering all hooks that power
     * the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      Kura_AI_Loader    $loader    Maintains and registers all hooks for the plugin.
     */
    protected $loader;

    /**
     * The unique identifier of this plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $plugin_name    The string used to uniquely identify this plugin.
     */
    protected $plugin_name;

    /**
     * The current version of the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $version    The current version of the plugin.
     */
    protected $version;

    /**
     * Define the core functionality of the plugin.
     *
     * Set the plugin name and the plugin version that can be used throughout the plugin.
     * Load the dependencies, define the locale, and set the hooks for the admin area and
     * the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function __construct()
    {
        $this->version = KURA_AI_VERSION;
        $this->plugin_name = 'kura-ai';

        $this->load_dependencies();
        $this->set_locale();
        $this->define_admin_hooks();
        $this->define_public_hooks();
    }

    /**
     * Load the required dependencies for this plugin.
     *
     * Include the following files that make up the plugin:
     *
     * - Kura_AI_Loader. Orchestrates the hooks of the plugin.
     * - Kura_AI_i18n. Defines internationalization functionality.
     * - Kura_AI_Admin. Defines all hooks for the admin area.
     * - Kura_AI_Public. Defines all hooks for the public side.
     *
     * Create an instance of the loader which will be used to register the hooks
     * with WordPress.
     *
     * @since    1.0.0
     * @access   private
     */
    private function load_dependencies()
    {
        require_once KURA_AI_PLUGIN_DIR . 'includes/class-kura-ai-loader.php';
        require_once KURA_AI_PLUGIN_DIR . 'includes/class-kura-ai-i18n.php';
        require_once KURA_AI_PLUGIN_DIR . 'includes/class-kura-ai-security-scanner.php';
        require_once KURA_AI_PLUGIN_DIR . 'includes/class-kura-ai-ai-handler.php';
        require_once KURA_AI_PLUGIN_DIR . 'includes/class-kura-ai-logger.php';
        require_once KURA_AI_PLUGIN_DIR . 'includes/class-kura-ai-notifier.php';


        require_once KURA_AI_PLUGIN_DIR . 'includes/ai-integrations/class-kura-ai-interface.php';
        require_once KURA_AI_PLUGIN_DIR . 'includes/ai-integrations/class-kura-ai-openai.php';  
        require_once KURA_AI_PLUGIN_DIR . 'includes/ai-integrations/class-kura-ai-claude.php';   
        require_once KURA_AI_PLUGIN_DIR . 'includes/ai-integrations/class-kura-ai-gemini.php';
        require_once KURA_AI_PLUGIN_DIR . 'includes/class-kura-ai-ai-handler.php';

        require_once KURA_AI_PLUGIN_DIR . 'admin/class-kura-ai-admin.php';
        require_once KURA_AI_PLUGIN_DIR . 'public/class-kura-ai-public.php';


        $this->loader = new Kura_AI_Loader();
    }

    /**
     * Define the locale for this plugin for internationalization.
     *
     * Uses the Kura_AI_i18n class in order to set the domain and to register the hook
     * with WordPress.
     *
     * @since    1.0.0
     * @access   private
     */
    private function set_locale()
    {
        $plugin_i18n = new Kura_AI_i18n();
        $this->loader->add_action('plugins_loaded', $plugin_i18n, 'load_plugin_textdomain');
    }

    /**
     * Register all of the hooks related to the admin area functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_admin_hooks()
    {
        $plugin_admin = new Kura_AI_Admin($this->get_plugin_name(), $this->get_version());

        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_styles');
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts');
        $this->loader->add_action('admin_menu', $plugin_admin, 'add_admin_menu');
        $this->loader->add_action('admin_init', $plugin_admin, 'register_settings');

        // Add AJAX handlers
        $this->loader->add_action('wp_ajax_kura_ai_run_scan', $plugin_admin, 'ajax_run_scan');
        $this->loader->add_action('wp_ajax_kura_ai_get_suggestions', $plugin_admin, 'ajax_get_suggestions');
        $this->loader->add_action('wp_ajax_kura_ai_apply_fix', $plugin_admin, 'ajax_apply_fix');
        $this->loader->add_action('wp_ajax_kura_ai_export_logs', $plugin_admin, 'ajax_export_logs');
        $this->loader->add_action('wp_ajax_kura_ai_clear_logs', $plugin_admin, 'ajax_clear_logs');
        $this->loader->add_action('wp_ajax_kura_ai_reset_settings', $plugin_admin, 'ajax_reset_settings');
    }

    /**
     * Register all of the hooks related to the public-facing functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_public_hooks()
    {
        $plugin_public = new Kura_AI_Public($this->get_plugin_name(), $this->get_version());

        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_styles');
        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_scripts');
    }

    /**
     * Run the loader to execute all of the hooks with WordPress.
     *
     * @since    1.0.0
     */
    public function run()
    {
        $this->loader->run();
    }

    /**
     * The name of the plugin used to uniquely identify it within the context of
     * WordPress and to define internationalization functionality.
     *
     * @since     1.0.0
     * @return    string    The name of the plugin.
     */
    public function get_plugin_name()
    {
        return $this->plugin_name;
    }

    /**
     * The reference to the class that orchestrates the hooks with the plugin.
     *
     * @since     1.0.0
     * @return    Kura_AI_Loader    Orchestrates the hooks of the plugin.
     */
    public function get_loader()
    {
        return $this->loader;
    }

    /**
     * Retrieve the version number of the plugin.
     *
     * @since     1.0.0
     * @return    string    The version number of the plugin.
     */
    public function get_version()
    {
        return $this->version;
    }
}