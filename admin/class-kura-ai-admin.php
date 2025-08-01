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

        add_action('admin_init', array($this, 'register_settings'));
    }

    public function register_settings()
    {
        register_setting('kura_ai_settings_group', 'kura_ai_settings', array($this, 'sanitize_settings'));

        add_settings_section(
            'kura_ai_oauth_settings',
            __('OAuth Credentials', 'kura-ai'),
            null,
            'kura-ai-settings'
        );

        add_settings_field(
            'kura_ai_openai_client_id',
            __('OpenAI Client ID', 'kura-ai'),
            array($this, 'text_field_callback'),
            'kura-ai-settings',
            'kura_ai_oauth_settings',
            ['name' => 'kura_ai_openai_client_id']
        );

        add_settings_field(
            'kura_ai_openai_client_secret',
            __('OpenAI Client Secret', 'kura-ai'),
            array($this, 'password_field_callback'),
            'kura-ai-settings',
            'kura_ai_oauth_settings',
            ['name' => 'kura_ai_openai_client_secret']
        );

        add_settings_field(
            'kura_ai_gemini_client_id',
            __('Gemini Client ID', 'kura-ai'),
            array($this, 'text_field_callback'),
            'kura-ai-settings',
            'kura_ai_oauth_settings',
            ['name' => 'kura_ai_gemini_client_id']
        );

        add_settings_field(
            'kura_ai_gemini_client_secret',
            __('Gemini Client Secret', 'kura-ai'),
            array($this, 'password_field_callback'),
            'kura-ai-settings',
            'kura_ai_oauth_settings',
            ['name' => 'kura_ai_gemini_client_secret']
        );

        add_settings_section(
            'kura_ai_redirect_uri_section',
            __('Redirect URI', 'kura-ai'),
            array($this, 'redirect_uri_section_callback'),
            'kura-ai-settings'
        );

        add_settings_section(
            'kura_ai_woocommerce_settings',
            __( 'WooCommerce Settings', 'kura-ai' ),
            null,
            'kura-ai-settings'
        );

        add_settings_field(
            'kura_ai_woocommerce_schedule',
            __( 'Scheduled Checkup Frequency', 'kura-ai' ),
            array( $this, 'schedule_select_callback' ),
            'kura-ai-settings',
            'kura_ai_woocommerce_settings'
        );

        add_settings_field(
            'kura_ai_woocommerce_email_reports',
            __( 'Email Reports', 'kura-ai' ),
            array( $this, 'email_reports_callback' ),
            'kura-ai-settings',
            'kura_ai_woocommerce_settings'
        );

        add_settings_field(
            'kura_ai_woocommerce_activity_tracking',
            __( 'Product Activity Tracking', 'kura-ai' ),
            array( $this, 'activity_tracking_callback' ),
            'kura-ai-settings',
            'kura_ai_woocommerce_settings'
        );
    }

    public function redirect_uri_section_callback()
    {
        echo '<p>' . __('Copy the following redirect URI and paste it into your OAuth app settings.', 'kura-ai') . '</p>';
        echo '<input type="text" class="large-text" readonly value="' . esc_url(admin_url('admin.php?page=kura-ai-settings&action=kura_ai_oauth_callback')) . '">';
    }


    /**
     * Callback for the schedule select field.
     *
     * @since    1.0.0
     */
    public function schedule_select_callback() {
        $options = get_option( 'kura_ai_settings' );
        $schedule = isset( $options['woocommerce_schedule'] ) ? $options['woocommerce_schedule'] : 'disabled';
        ?>
        <select name="kura_ai_settings[woocommerce_schedule]">
            <option value="disabled" <?php selected( $schedule, 'disabled' ); ?>><?php esc_html_e( 'Disabled', 'kura-ai' ); ?></option>
            <option value="hourly" <?php selected( $schedule, 'hourly' ); ?>><?php esc_html_e( 'Hourly', 'kura-ai' ); ?></option>
            <option value="daily" <?php selected( $schedule, 'daily' ); ?>><?php esc_html_e( 'Daily', 'kura-ai' ); ?></option>
            <option value="weekly" <?php selected( $schedule, 'weekly' ); ?>><?php esc_html_e( 'Weekly', 'kura-ai' ); ?></option>
            <option value="monthly" <?php selected( $schedule, 'monthly' ); ?>><?php esc_html_e( 'Monthly', 'kura-ai' ); ?></option>
        </select>
        <?php
    }

    /**
     * Callback for the email reports checkbox.
     *
     * @since    1.0.0
     */
    public function email_reports_callback() {
        $options = get_option( 'kura_ai_settings' );
        $email_reports = isset( $options['woocommerce_email_reports'] ) ? $options['woocommerce_email_reports'] : 0;
        ?>
        <input type="checkbox" name="kura_ai_settings[woocommerce_email_reports]" value="1" <?php checked( $email_reports, 1 ); ?> />
        <?php
    }

    /**
     * Callback for the activity tracking checkbox.
     *
     * @since    1.0.0
     */
    public function activity_tracking_callback() {
        $options = get_option( 'kura_ai_settings' );
        $activity_tracking = isset( $options['woocommerce_activity_tracking'] ) ? $options['woocommerce_activity_tracking'] : 0;
        ?>
        <input type="checkbox" name="kura_ai_settings[woocommerce_activity_tracking]" value="1" <?php checked( $activity_tracking, 1 ); ?> />
        <?php
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
            wp_enqueue_script(
                $this->plugin_name,
                plugin_dir_url(__FILE__) . 'js/kura-ai-admin.js',
                array('jquery'),
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

        // ───────────── Documentation Links ─────────────
        // add_submenu_page(
        //     'kura-ai',
        //     __('Installation Guide', 'kura-ai'),
        //     __('Installation Guide', 'kura-ai'),
        //     'manage_options',
        //     'kura-ai-doc-installation',
        //     function () {
        //         echo '<div class="wrap"><iframe src="' . plugins_url('../documentation/Installation-Guide.md', __FILE__) . '" style="width:100%; height:80vh;"></iframe></div>';
        //     }
        // );

        // add_submenu_page(
        //     'kura-ai',
        //     __('Admin Walkthrough', 'kura-ai'),
        //     __('Admin Walkthrough', 'kura-ai'),
        //     'manage_options',
        //     'kura-ai-doc-admin',
        //     function () {
        //         echo '<div class="wrap"><iframe src="' . plugins_url('../documentation/Admin-Walkthrough.md', __FILE__) . '" style="width:100%; height:80vh;"></iframe></div>';
        //     }
        // );

        // add_submenu_page(
        //     'kura-ai',
        //     __('API Integration Guide', 'kura-ai'),
        //     __('API Guide', 'kura-ai'),
        //     'manage_options',
        //     'kura-ai-doc-api',
        //     function () {
        //         echo '<div class="wrap"><iframe src="' . plugins_url('../documentation/API-Integration-Guide.md', __FILE__) . '" style="width:100%; height:80vh;"></iframe></div>';
        //     }
        // );
    }

    /**
     * Register plugin settings.
     *
     * @since    1.0.0
     */
    /**
     * Register plugin settings with OAuth focus
     */

    /**
     * Generic text field callback
     */
    public function text_field_callback($args)
    {
        $options = get_option('kura_ai_settings');
        $value = $options[$args['name']] ?? '';
        echo '<input type="text" id="' . esc_attr($args['name']) . '" name="kura_ai_settings[' . esc_attr($args['name']) . ']" value="' . esc_attr($value) . '" class="regular-text">';
        if (!empty($args['description'])) {
            echo '<p class="description">' . esc_html($args['description']) . '</p>';
        }
    }

    /**
     * Generic password field callback
     */
    public function password_field_callback($args)
    {
        $options = get_option('kura_ai_settings');
        $value = $options[$args['name']] ?? '';
        echo '<input type="password" id="' . esc_attr($args['name']) . '" name="kura_ai_settings[' . esc_attr($args['name']) . ']" value="' . esc_attr($value) . '" class="regular-text">';
        if (!empty($args['description'])) {
            echo '<p class="description">' . esc_html($args['description']) . '</p>';
        }
    }

    /**
     * Register OAuth AJAX handlers
     */
    public function register_oauth_handlers()
    {
        add_action('wp_ajax_kura_ai_oauth_init', [$this, 'handle_oauth_init']);
        add_action('wp_ajax_kura_ai_oauth_callback', [$this, 'handle_oauth_callback']);
        add_action('wp_ajax_kura_ai_oauth_disconnect', [$this, 'handle_oauth_disconnect']);
        add_action('wp_ajax_kura_ai_check_oauth_connection', [$this, 'check_oauth_connection']);
    }
    
    public function handle_oauth_init()
    {
        try {
            // Verify nonce
            if (!check_ajax_referer('kura_ai_oauth_nonce', '_wpnonce', false)) {
                throw new Exception('Invalid nonce');
            }

            if (!current_user_can('manage_options')) {
                throw new Exception('Insufficient permissions');
            }

            $provider = sanitize_text_field($_POST['provider']);
            $state = wp_create_nonce('kura_ai_oauth_state_' . $provider);

            $oauth_handler = new Kura_AI_OAuth_Handler();
            $auth_url = $oauth_handler->get_auth_url($provider, $state);

            if (is_wp_error($auth_url)) {
                throw new Exception($auth_url->get_error_message());
            }

            wp_send_json_success(['redirect_url' => $auth_url]);
        } catch (Exception $e) {
            error_log('OAuth Init Error: ' . $e->getMessage());
            wp_send_json_error($e->getMessage(), 403);
        }
    }
    
    public function handle_oauth_callback()
    {
        try {
            $provider = sanitize_text_field($_GET['provider'] ?? '');
            $code = sanitize_text_field($_GET['code'] ?? '');
            $state = sanitize_text_field($_GET['state'] ?? '');

            if (empty($provider) || empty($code) || empty($state)) {
                throw new Exception('Invalid callback parameters');
            }

            if (!wp_verify_nonce($state, 'kura_ai_oauth_state_' . $provider)) {
                throw new Exception('Your authorization session was not initialized or has expired.');
            }

            $oauth_handler = new Kura_AI_OAuth_Handler();
            $result = $oauth_handler->handle_callback($provider, $code, $state);

            if (is_wp_error($result)) {
                throw new Exception('Route Error (400 Invalid Session): "' . $result->get_error_message() . '"');
            }

            // The tokens are already stored in the user meta by the OAuth handler

            wp_redirect(admin_url('admin.php?page=kura-ai-settings&oauth_success=1'));
            exit;
        } catch (Exception $e) {
            error_log('OAuth Callback Error: ' . $e->getMessage());
            wp_redirect(admin_url('admin.php?page=kura-ai-settings&oauth_error=' . urlencode($e->getMessage())));
            exit;
        }
    }

    /**
     * Handle OAuth disconnection
     */
    public function handle_oauth_disconnect()
    {
        check_ajax_referer('kura_ai_oauth_disconnect', '_wpnonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Unauthorized', 'kura-ai'), 403);
        }

        $provider = sanitize_text_field($_POST['provider']);
        $user_id = get_current_user_id();

        if ($user_id) {
            delete_user_meta($user_id, 'kura_ai_' . $provider . '_access_token');
            delete_user_meta($user_id, 'kura_ai_' . $provider . '_refresh_token');
            delete_user_meta($user_id, 'kura_ai_' . $provider . '_token_created');
            delete_user_meta($user_id, 'kura_ai_' . $provider . '_expires_in');
            wp_send_json_success();
        }

        wp_send_json_error(__('No active connection found', 'kura-ai'));
    }


    /**
     * Check OAuth connection status
     */
    public function check_oauth_connection()
    {
        check_ajax_referer('kura_ai_oauth_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Unauthorized', 'kura-ai'), 403);
        }

        $provider = sanitize_text_field($_POST['provider']);
        $user_id = get_current_user_id();

        wp_send_json_success([
            'connected' => !empty(get_user_meta($user_id, 'kura_ai_' . $provider . '_access_token', true))
        ]);
    }

    /**
     * Sanitize plugin settings before saving.
     *
     * @since    1.0.0
     * @param    array    $input    The settings to sanitize
     * @return   array              The sanitized settings
     */
    /**
     * Sanitize plugin settings with OAuth focus
     */
    public function sanitize_settings($input)
    {
        $output = get_option('kura_ai_settings');

        if (isset($input['kura_ai_openai_client_id'])) {
            $output['kura_ai_openai_client_id'] = sanitize_text_field($input['kura_ai_openai_client_id']);
        }

        if (isset($input['kura_ai_openai_client_secret'])) {
            $output['kura_ai_openai_client_secret'] = sanitize_text_field($input['kura_ai_openai_client_secret']);
        }

        if (isset($input['kura_ai_gemini_client_id'])) {
            $output['kura_ai_gemini_client_id'] = sanitize_text_field($input['kura_ai_gemini_client_id']);
        }

        if (isset($input['kura_ai_gemini_client_secret'])) {
            $output['kura_ai_gemini_client_secret'] = sanitize_text_field($input['kura_ai_gemini_client_secret']);
        }

        if ( isset( $input['woocommerce_schedule'] ) ) {
            $output['woocommerce_schedule'] = sanitize_text_field( $input['woocommerce_schedule'] );
        }

        if ( isset( $input['woocommerce_email_reports'] ) ) {
            $output['woocommerce_email_reports'] = 1;
        } else {
            $output['woocommerce_email_reports'] = 0;
        }

        if ( isset( $input['woocommerce_activity_tracking'] ) ) {
            $output['woocommerce_activity_tracking'] = 1;
        } else {
            $output['woocommerce_activity_tracking'] = 0;
        }

        return $output;
    }


    /**
     * Callback for general settings section.
     *
     * @since    1.0.0
     */
    public function general_settings_section_callback()
    {
        echo '<p>' . __('Configure general plugin settings.', 'kura-ai') . '</p>';
    }

    /**
     * Callback for scan frequency setting.
     *
     * @since    1.0.0
     */
    public function scan_frequency_callback()
    {
        $options = get_option('kura_ai_settings');
        $frequency = isset($options['scan_frequency']) ? $options['scan_frequency'] : 'daily';

        $schedules = array(
            'hourly' => __('Hourly', 'kura-ai'),
            'twicedaily' => __('Twice Daily', 'kura-ai'),
            'daily' => __('Daily', 'kura-ai'),
            'weekly' => __('Weekly', 'kura-ai')
        );

        echo '<select id="scan_frequency" name="kura_ai_settings[scan_frequency]">';
        foreach ($schedules as $value => $label) {
            echo '<option value="' . esc_attr($value) . '" ' . selected($frequency, $value, false) . '>' . esc_html($label) . '</option>';
        }
        echo '</select>';
        echo '<p class="description">' . __('How often automatic security scans should run.', 'kura-ai') . '</p>';
    }

    /**
     * Callback for email notifications setting.
     *
     * @since    1.0.0
     */
    public function email_notifications_callback()
    {
        $options = get_option('kura_ai_settings');
        $enabled = isset($options['email_notifications']) ? $options['email_notifications'] : 1;

        echo '<label>';
        echo '<input type="checkbox" id="email_notifications" name="kura_ai_settings[email_notifications]" value="1" ' . checked(1, $enabled, false) . '>';
        echo __('Enable email notifications', 'kura-ai');
        echo '</label>';
        echo '<p class="description">' . __('Receive email alerts when security issues are detected.', 'kura-ai') . '</p>';
    }

    /**
     * Callback for notification email setting.
     *
     * @since    1.0.0
     */
    public function notification_email_callback()
    {
        $options = get_option('kura_ai_settings');
        $email = isset($options['notification_email']) ? $options['notification_email'] : get_option('admin_email');

        echo '<input type="email" id="notification_email" name="kura_ai_settings[notification_email]" value="' . esc_attr($email) . '" class="regular-text">';
        echo '<p class="description">' . __('Email address to receive security notifications.', 'kura-ai') . '</p>';
    }

    /**
     * Callback for AI settings section.
     *
     * @since    1.0.0
     */
    public function ai_settings_section_callback()
    {
        echo '<p>' . __('Connect to your preferred AI service using OAuth authentication.', 'kura-ai') . '</p>';
        echo '<p class="description">' . __('Click "Connect" below to authenticate with your chosen provider.', 'kura-ai') . '</p>';
    }

    /**
     * Callback for enable AI setting.
     *
     * @since    1.0.0
     */
    public function enable_ai_callback()
    {
        $options = get_option('kura_ai_settings');
        $enabled = isset($options['enable_ai']) ? $options['enable_ai'] : 1; // Default to enabled

        echo '<label>';
        echo '<input type="checkbox" id="enable_ai" name="kura_ai_settings[enable_ai]" value="1" ' . checked(1, $enabled, false) . '>';
        echo __('Enable AI-powered security suggestions', 'kura-ai');
        echo '</label>';
        echo '<p class="description">' . __('Uses OAuth for authentication with your selected AI provider.', 'kura-ai') . '</p>';
    }

    /**
     * Callback for AI service setting.
     *
     * @since    1.0.0
     */
    public function ai_service_callback()
    {
        $options = get_option('kura_ai_settings');
        $service = $options['ai_service'] ?? 'openai';

        $services = [
            'openai' => __('OpenAI', 'kura-ai'),
            'gemini' => __('Google Gemini', 'kura-ai')
        ];

        echo '<select id="ai_service" name="kura_ai_settings[ai_service]">';
        foreach ($services as $value => $label) {
            echo '<option value="' . esc_attr($value) . '" ' . selected($service, $value, false) . '>' . esc_html($label) . '</option>';
        }
        echo '</select>';
        echo '<p class="description">' . __('Select which AI service to use for security suggestions.', 'kura-ai') . '</p>';
    }

    /**
     * Display the dashboard page.
     *
     * @since    1.0.0
     */
    public function display_dashboard_page()
    {
        include_once 'partials/kura-ai-admin-display.php';
    }

    /**
     * Get debug information for the plugin
     *
     * @since    1.0.0
     * @return   string    Debug information
     */
    public function get_debug_info()
    {
        global $wpdb;

        $debug_info = "=== KuraAI Debug Information ===\n\n";

        // Basic WordPress info
        $debug_info .= "WordPress Version: " . get_bloginfo('version') . "\n";
        $debug_info .= "PHP Version: " . phpversion() . "\n";
        $debug_info .= "MySQL Version: " . $wpdb->db_version() . "\n";

        // Plugin info
        $debug_info .= "\n=== Plugin Information ===\n";
        $debug_info .= "KuraAI Version: " . KURA_AI_VERSION . "\n";

        // Settings
        $settings = get_option('kura_ai_settings');
        $debug_info .= "\n=== Settings ===\n";
        $debug_info .= print_r($settings, true) . "\n";

        // Database tables
        $debug_info .= "\n=== Database ===\n";
        $table_name = $wpdb->prefix . 'kura_ai_logs';
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
        $debug_info .= "Logs table exists: " . ($table_exists ? 'Yes' : 'No') . "\n";

        if ($table_exists) {
            $log_count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
            $debug_info .= "Log entries: " . $log_count . "\n";
        }

        return $debug_info;
    }

    /**
     * Display the reports page.
     *
     * @since    1.0.0
     */
    public function display_reports_page()
    {
        include_once 'partials/kura-ai-reports-display.php';
    }

    /**
     * Display the suggestions page.
     *
     * @since    1.0.0
     */
    public function display_suggestions_page()
    {
        include_once 'partials/kura-ai-suggestions-display.php';
    }

    /**
     * Display the logs page.
     *
     * @since    1.0.0
     */
    public function display_logs_page()
    {
        include_once 'partials/kura-ai-logs-display.php';
    }

    public function display_settings_page()
    {
        include_once 'partials/kura-ai-settings-display.php';
    }

    /**
     * Display the settings page.
     *
     * @since    1.0.0
     */

    /**
     * AJAX handler for running a security scan.
     *
     * @since    1.0.0
     */
    public function ajax_run_scan()
    {
        check_ajax_referer('kura_ai_oauth_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('You do not have sufficient permissions to perform this action.', 'kura-ai'));
        }

        try {
            $scanner = new Kura_AI_Security_Scanner($this->plugin_name, $this->version);
            $results = $scanner->run_scan();

            // Log the scan
            $logger = new Kura_AI_Logger($this->plugin_name, $this->version);
            $logger->log('scan', __('Manual security scan completed', 'kura-ai'), $results);

            // Send notification if enabled
            $settings = get_option('kura_ai_settings');
            if (!empty($settings['email_notifications'])) {
                $notifier = new Kura_AI_Notifier($this->plugin_name, $this->version);
                $notifier->send_scan_results($results);
            }

            wp_send_json_success($results);
        } catch (Exception $e) {
            wp_send_json_error(sprintf(
                __('Scan failed: %s (Line %d in %s)', 'kura-ai'),
                $e->getMessage(),
                $e->getLine(),
                basename($e->getFile()),
            ));
        }
    }

    /**
     * Clear Log reports.
     *
     * @since    1.0.0
     */
    public function ajax_clear_logs()
    {
        try {
            // Use the same nonce verification as OAuth
            if (!check_ajax_referer('kura_ai_oauth_nonce', '_wpnonce', false)) {
                throw new Exception(__('Security check failed.', 'kura-ai'), 403);
            }

            if (!current_user_can('manage_options')) {
                throw new Exception(__('Permission denied.', 'kura-ai'), 403);
            }

            $args = array();
            if (!empty($_POST['type'])) {
                $args['type'] = sanitize_text_field($_POST['type']);
            }
            if (!empty($_POST['severity'])) {
                $args['severity'] = sanitize_text_field($_POST['severity']);
            }

            $logger = new Kura_AI_Logger($this->plugin_name, $this->version);
            $result = $logger->clear_logs($args);

            if ($result === false) {
                throw new Exception(__('Database error occurred.', 'kura-ai'), 500);
            }

            wp_send_json_success(
                __('Logs cleared successfully.', 'kura-ai'),
                200,
            );

        } catch (Exception $e) {
            wp_send_json_error($e->getMessage(), $e->getCode());
        }
    }


    /**
     * Callback for OAuth settings section
     */
    public function oauth_settings_section_callback()
    {
        echo '<p>' . __('Configure OAuth credentials for your selected AI provider.', 'kura-ai') . '</p>';
        echo '<p class="description">';
        echo __('You must register your application with each provider and enter the credentials below.', 'kura-ai');
        echo '</p>';
    }

    /**
     * Callback for enable OAuth setting
     */
    public function enable_oauth_callback()
    {
        $options = get_option('kura_ai_settings');
        $enabled = isset($options['enable_oauth']) ? $options['enable_oauth'] : 0;

        echo '<label>';
        echo '<input type="checkbox" id="enable_oauth" name="kura_ai_settings[enable_oauth]" value="1" ' . checked(1, $enabled, false) . '>';
        echo __('Enable OAuth authentication', 'kura-ai');
        echo '</label>';
        echo '<p class="description">' . __('Use OAuth instead of API keys for authentication', 'kura-ai') . '</p>';
    }
    /**
     * reset plugin settings.
     *
     * @since    1.0.0
     */
    public function ajax_reset_settings()
    {
        check_ajax_referer('kura_ai_oauth_nonce', '_wpnonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('You do not have permission to reset settings.', 403);
        }

        // Reset to defaults - 
        $defaults = array(
            'scan_frequency' => 'daily',
            'email_notifications' => 1,
            'notification_email' => get_option('admin_email'),
            'enable_ai' => 1, // Default to enabled
            'ai_service' => 'openai',
            // OAuth fields reset to empty
            'openai_client_id' => '',
            'openai_client_secret' => '',
            'gemini_client_id' => '',
            'gemini_client_secret' => ''
        );

        update_option('kura_ai_settings', $defaults);
        wp_send_json_success('Settings reset successfully.');
    }

    /**
     * AJAX handler for getting AI suggestions.
     *
     * @since    1.0.0
     */
    public function ajax_get_suggestions()
    {
        check_ajax_referer('kura_ai_oauth_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('You do not have sufficient permissions to perform this action.', 'kura-ai'));
        }

        if (empty($_POST['issue'])) {
            wp_send_json_error(__('No issue data provided.', 'kura-ai'));
        }

        $issue = $_POST['issue'];
        if (!is_array($issue)) {
            $issue = json_decode(stripslashes($issue), true);
        }

        $ai_handler = new Kura_AI_AI_Handler($this->plugin_name, $this->version);
        $suggestion = $ai_handler->get_suggestion($issue);

        // Log the AI interaction
        $logger = new Kura_AI_Logger($this->plugin_name, $this->version);
        $logger->log('ai_suggestion', __('AI suggestion requested', 'kura-ai'), array(
            'issue_type' => $issue['type'],
            'severity' => $issue['severity']
        ));

        wp_send_json_success(array(
            'suggestion' => $suggestion
        ));
    }

    /**
     * AJAX handler for applying a fix.
     *
     * @since    1.0.0
     */
    public function ajax_apply_fix()
    {
        check_ajax_referer('kura_ai_oauth_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('You do not have sufficient permissions to perform this action.', 'kura-ai'));
        }

        if (empty($_POST['issue_type'])) {
            wp_send_json_error(__('No issue type provided.', 'kura-ai'));
        }

        $issue_type = sanitize_text_field($_POST['issue_type']);
        $result = array('success' => false, 'message' => '');

        // Handle different fix types
        switch ($issue_type) {
            case 'outdated_plugin':
                if (!empty($_POST['plugin'])) {
                    $plugin = sanitize_text_field($_POST['plugin']);
                    $result = $this->update_plugin($plugin);
                }
                break;

            case 'outdated_theme':
                if (!empty($_POST['theme'])) {
                    $theme = sanitize_text_field($_POST['theme']);
                    $result = $this->update_theme($theme);
                }
                break;

            case 'insecure_permission':
                if (!empty($_POST['file'])) {
                    $file = sanitize_text_field($_POST['file']);
                    $result = $this->fix_file_permissions($file);
                }
                break;

            default:
                $result['message'] = __('Automatic fix not available for this issue type.', 'kura-ai');
        }

        // Log the fix attempt
        $logger = new Kura_AI_Logger($this->plugin_name, $this->version);
        $logger->log('fix_applied', __('Fix applied', 'kura-ai'), array(
            'issue_type' => $issue_type,
            'success' => $result['success'],
            'message' => $result['message']
        ));

        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }

    /**
     * Update a plugin.
     *
     * @since    1.0.0
     * @param    string    $plugin    The plugin to update
     * @return   array                Result of the update
     */
    private function update_plugin($plugin)
    {
        if (!current_user_can('update_plugins')) {
            return array(
                'success' => false,
                'message' => __('You do not have permission to update plugins.', 'kura-ai')
            );
        }

        include_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
        include_once ABSPATH . 'wp-admin/includes/plugin.php';

        $upgrader = new Plugin_Upgrader(new Automatic_Upgrader_Skin());
        $result = $upgrader->upgrade($plugin);

        if (is_wp_error($result)) {
            return array(
                'success' => false,
                'message' => $result->get_error_message()
            );
        }

        return array(
            'success' => true,
            'message' => __('Plugin updated successfully.', 'kura-ai')
        );
    }

    /**
     * Update a theme.
     *
     * @since    1.0.0
     * @param    string    $theme    The theme to update
     * @return   array               Result of the update
     */
    private function update_theme($theme)
    {
        if (!current_user_can('update_themes')) {
            return array(
                'success' => false,
                'message' => __('You do not have permission to update themes.', 'kura-ai')
            );
        }

        include_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';

        $upgrader = new Theme_Upgrader(new Automatic_Upgrader_Skin());
        $result = $upgrader->upgrade($theme);

        if (is_wp_error($result)) {
            return array(
                'success' => false,
                'message' => $result->get_error_message()
            );
        }

        return array(
            'success' => true,
            'message' => __('Theme updated successfully.', 'kura-ai')
        );
    }

    /**
     * Fix file permissions.
     *
     * @since    1.0.0
     * @param    string    $file    The file to fix permissions for
     * @return   array              Result of the fix
     */
    private function fix_file_permissions($file)
    {
        if (!current_user_can('manage_options')) {
            return array(
                'success' => false,
                'message' => __('You do not have permission to modify file permissions.', 'kura-ai')
            );
        }

        $full_path = ABSPATH . $file;
        if (!file_exists($full_path)) {
            return array(
                'success' => false,
                'message' => __('File does not exist.', 'kura-ai')
            );
        }

        $is_dir = is_dir($full_path);
        $new_perms = $is_dir ? 0755 : 0644;

        if (@chmod($full_path, $new_perms)) {
            return array(
                'success' => true,
                'message' => sprintf(__('Permissions set to %o for %s.', 'kura-ai'), $new_perms, $file)
            );
        } else {
            return array(
                'success' => false,
                'message' => __('Failed to change file permissions.', 'kura-ai')
            );
        }
    }

    /**
     * AJAX handler for exporting logs.
     *
     * @since    1.0.0
     */
    public function ajax_export_logs()
    {
        check_ajax_referer('kura_ai_oauth_nonce', '_wpnonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('You do not have sufficient permissions to perform this action.', 'kura-ai'));
        }

        $args = array();
        if (!empty($_POST['type'])) {
            $args['type'] = sanitize_text_field($_POST['type']);
        }
        if (!empty($_POST['severity'])) {
            $args['severity'] = sanitize_text_field($_POST['severity']);
        }

        $logger = new Kura_AI_Logger($this->plugin_name, $this->version);
        $csv = $logger->export_logs_to_csv($args);

        // Log the export
        $logger->log('export', __('Logs exported', 'kura-ai'), $args);

        // Send CSV file
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="kura-ai-logs-' . date('Y-m-d') . '.csv"');
        echo $csv;
        exit;
    }
}