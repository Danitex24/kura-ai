<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @package    Kura_AI
 * @subpackage Kura_AI/admin
 * @author     Daniel Abughdyer <daniel@danovatesolutions.org>
 */
class Kura_AI_Admin
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

        // Register AJAX handlers
        add_action('wp_ajax_save_api_key', array($this, 'handle_save_api_key'));
    }

    /**
     * Register the stylesheets for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_styles($hook)
    {
        if (strpos($hook, 'kura-ai') !== false) {

            // Enqueue Font Awesome from CDN
            wp_enqueue_style(
                'font-awesome',
                'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css',
                array(),
                '5.15.4',
            );

            wp_enqueue_style(
                $this->plugin_name,
                plugin_dir_url(__FILE__) . 'css/kura-ai-admin.css',
                array(),
                $this->version,
                'all',
            );

            // Add WordPress dashicons
            wp_enqueue_style('dashicons');

            // Enqueue SweetAlert2
            wp_enqueue_style(
                'sweetalert2',
                'https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.min.css',
                array(),
                '11.7.32'
            );
        }
    }

    /**
     * Register the JavaScript for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts($hook)
    {
        if (strpos($hook, 'kura-ai') !== false) {
            // Enqueue SweetAlert2
            wp_enqueue_script(
                'sweetalert2',
                'https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.all.min.js',
                array(),
                '11.7.32',
                true
            );

            wp_enqueue_script(
                $this->plugin_name,
                plugin_dir_url(__FILE__) . 'js/kura-ai-admin.js',
                array('jquery', 'sweetalert2'),
                $this->version,
                false,
            );

            wp_localize_script(
                $this->plugin_name,
                'kura_ai_ajax',
                array(
                    'ajax_url' => admin_url('admin-ajax.php'),
                    'nonce' => wp_create_nonce('kura_ai_oauth_nonce'),
                    'scan_in_progress' => __('Scan in progress...', 'kura-ai'),
                    'getting_suggestions' => __('Getting AI suggestions...', 'kura-ai'),
                    'applying_fix' => __('Applying fix...', 'kura-ai'),
                    'exporting_logs' => __('Exporting logs...', 'kura-ai')
                ),
            );
        }
    }

    /**
     * Handle saving API key via AJAX.
     *
     * @since    1.0.0
     */
    public function handle_save_api_key() {
        check_ajax_referer('save_api_key_action');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('You do not have permission to perform this action.', 'kura-ai')));
        }

        $provider = sanitize_text_field($_POST['provider']);
        $api_key = sanitize_text_field($_POST['api_key']);

        if (empty($provider) || empty($api_key)) {
            wp_send_json_error(array('message' => __('Provider and API key are required.', 'kura-ai')));
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'kura_ai_api_keys';

        // Check if provider already exists
        $existing_key = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT id FROM $table_name WHERE provider = %s",
                $provider
            )
        );

        if ($existing_key) {
            // Update existing key
            $result = $wpdb->update(
                $table_name,
                array(
                    'api_key' => $api_key,
                    'active' => 1
                ),
                array('provider' => $provider),
                array('%s', '%d'),
                array('%s')
            );
        } else {
            // Insert new key
            $result = $wpdb->insert(
                $table_name,
                array(
                    'provider' => $provider,
                    'api_key' => $api_key,
                    'active' => 1
                ),
                array('%s', '%s', '%d')
            );
        }

        if ($result !== false) {
            wp_send_json_success(array('message' => sprintf(__('%s API key saved successfully!', 'kura-ai'), ucfirst($provider))));
        } else {
            wp_send_json_error(array('message' => sprintf(__('Failed to save %s API key. Database error occurred.', 'kura-ai'), ucfirst($provider))));
        }
    }

    /**
     * Display the dashboard page content.
     *
     * @since    1.0.0
     */
    public function display_dashboard_page() {
        require_once plugin_dir_path(__FILE__) . 'partials/kura-ai-admin-display.php';
    }

    /**
     * Display the reports page content.
     *
     * @since    1.0.0
     */
    public function display_reports_page() {
        require_once plugin_dir_path(__FILE__) . 'partials/kura-ai-reports-display.php';
    }

    /**
     * Display the suggestions page content.
     *
     * @since    1.0.0
     */
    public function display_suggestions_page() {
        require_once plugin_dir_path(__FILE__) . 'partials/kura-ai-suggestions-display.php';
    }

    /**
     * Display the logs page content.
     *
     * @since    1.0.0
     */
    public function display_logs_page() {
        require_once plugin_dir_path(__FILE__) . 'partials/kura-ai-logs-display.php';
    }

    /**
     * Display the settings page content.
     *
     * @since    1.0.0
     */
    public function display_settings_page() {
        require_once plugin_dir_path(__FILE__) . 'partials/kura-ai-settings-display.php';
    }

    /**
     * Register the administration menu for this plugin into the WordPress Dashboard menu.
     *
     * @since    1.0.0
     */
    public function add_admin_menu()
    {
        add_menu_page(
            __('KuraAI Security', 'kura-ai'),
            __('KuraAI Security', 'kura-ai'),
            'manage_options',
            'kura-ai',
            array($this, 'display_dashboard_page'),
            'dashicons-shield',
            80,
        );

        add_submenu_page(
            'kura-ai',
            __('Dashboard', 'kura-ai'),
            __('Dashboard', 'kura-ai'),
            'manage_options',
            'kura-ai',
            array($this, 'display_dashboard_page'),
        );

        add_submenu_page(
            'kura-ai',
            __('Vulnerability Reports', 'kura-ai'),
            __('Reports', 'kura-ai'),
            'manage_options',
            'kura-ai-reports',
            array($this, 'display_reports_page'),
        );

        add_submenu_page(
            'kura-ai',
            __('AI Fix Suggestions', 'kura-ai'),
            __('AI Suggestions', 'kura-ai'),
            'manage_options',
            'kura-ai-suggestions',
            array($this, 'display_suggestions_page'),
        );

        add_submenu_page(
            'kura-ai',
            __('Activity Logs', 'kura-ai'),
            __('Activity Logs', 'kura-ai'),
            'manage_options',
            'kura-ai-logs',
            array($this, 'display_logs_page'),
        );

        add_submenu_page(
            'kura-ai',
            __('Settings', 'kura-ai'),
            __('Settings', 'kura-ai'),
            'manage_options',
            'kura-ai-settings',
            array($this, 'display_settings_page'),
        );
    }
}