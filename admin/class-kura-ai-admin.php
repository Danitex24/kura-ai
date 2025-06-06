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
    }

    /**
     * Register the stylesheets for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_styles($hook)
    {
        if (strpos($hook, 'kura-ai') !== false) {
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
                    'nonce' => wp_create_nonce('kura_ai_nonce'),
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
    }

    /**
     * Register plugin settings.
     *
     * @since    1.0.0
     */
    public function register_settings()
    {
        register_setting('kura_ai_settings_group', 'kura_ai_settings', array($this, 'sanitize_settings'));

        // General Settings Section
        add_settings_section(
            'kura_ai_general_settings',
            __('General Settings', 'kura-ai'),
            array($this, 'general_settings_section_callback'),
            'kura-ai-settings',
        );

        add_settings_field(
            'scan_frequency',
            __('Scan Frequency', 'kura-ai'),
            array($this, 'scan_frequency_callback'),
            'kura-ai-settings',
            'kura_ai_general_settings',
        );

        add_settings_field(
            'email_notifications',
            __('Email Notifications', 'kura-ai'),
            array($this, 'email_notifications_callback'),
            'kura-ai-settings',
            'kura_ai_general_settings',
        );

        add_settings_field(
            'notification_email',
            __('Notification Email', 'kura-ai'),
            array($this, 'notification_email_callback'),
            'kura-ai-settings',
            'kura_ai_general_settings',
        );

        // AI Settings Section
        add_settings_section(
            'kura_ai_ai_settings',
            __('AI Integration Settings', 'kura-ai'),
            array($this, 'ai_settings_section_callback'),
            'kura-ai-settings',
        );

        add_settings_field(
            'enable_ai',
            __('Enable AI Suggestions', 'kura-ai'),
            array($this, 'enable_ai_callback'),
            'kura-ai-settings',
            'kura_ai_ai_settings',
        );

        add_settings_field(
            'ai_service',
            __('AI Service', 'kura-ai'),
            array($this, 'ai_service_callback'),
            'kura-ai-settings',
            'kura_ai_ai_settings',
        );

        add_settings_field(
            'api_key',
            __('API Key', 'kura-ai'),
            array($this, 'api_key_callback'),
            'kura-ai-settings',
            'kura_ai_ai_settings',
        );
    }

    /**
     * Sanitize plugin settings before saving.
     *
     * @since    1.0.0
     * @param    array    $input    The settings to sanitize
     * @return   array              The sanitized settings
     */
    public function sanitize_settings($input)
    {
        $output = get_option('kura_ai_settings');

        // General settings
        if (isset($input['scan_frequency'])) {
            $output['scan_frequency'] = sanitize_text_field($input['scan_frequency']);
        }

        if (isset($input['email_notifications'])) {
            $output['email_notifications'] = (int) $input['email_notifications'];
        }

        if (isset($input['notification_email'])) {
            $output['notification_email'] = sanitize_email($input['notification_email']);
            if (!is_email($output['notification_email'])) {
                $output['notification_email'] = get_option('admin_email');
            }
        }

        // AI settings
        if (isset($input['enable_ai'])) {
            $output['enable_ai'] = (int) $input['enable_ai'];
        }

        if (isset($input['ai_service'])) {
            $output['ai_service'] = sanitize_text_field($input['ai_service']);
        }

        if (isset($input['api_key'])) {
            $output['api_key'] = sanitize_text_field($input['api_key']);
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
        echo '<p>' . __('Configure AI integration settings for intelligent security suggestions.', 'kura-ai') . '</p>';
    }

    /**
     * Callback for enable AI setting.
     *
     * @since    1.0.0
     */
    public function enable_ai_callback()
    {
        $options = get_option('kura_ai_settings');
        $enabled = isset($options['enable_ai']) ? $options['enable_ai'] : 0;

        echo '<label>';
        echo '<input type="checkbox" id="enable_ai" name="kura_ai_settings[enable_ai]" value="1" ' . checked(1, $enabled, false) . '>';
        echo __('Enable AI-powered security suggestions', 'kura-ai');
        echo '</label>';
        echo '<p class="description">' . __('Requires API key for your chosen AI service.', 'kura-ai') . '</p>';
    }

    /**
     * Callback for AI service setting.
     *
     * @since    1.0.0
     */
    public function ai_service_callback()
    {
        $options = get_option('kura_ai_settings');
        $service = isset($options['ai_service']) ? $options['ai_service'] : 'openai';

        $services = array(
            'openai' => __('OpenAI', 'kura-ai'),
            'claude' => __('Claude (Coming Soon)', 'kura-ai'),
            'gemini' => __('Gemini (Coming Soon)', 'kura-ai')
        );

        echo '<select id="ai_service" name="kura_ai_settings[ai_service]">';
        foreach ($services as $value => $label) {
            echo '<option value="' . esc_attr($value) . '" ' . selected($service, $value, false) . '>' . esc_html($label) . '</option>';
        }
        echo '</select>';
        echo '<p class="description">' . __('Select which AI service to use for security suggestions.', 'kura-ai') . '</p>';
    }

    /**
     * Callback for API key setting.
     *
     * @since    1.0.0
     */
    public function api_key_callback()
    {
        $options = get_option('kura_ai_settings');
        $api_key = isset($options['api_key']) ? $options['api_key'] : '';

        echo '<input type="password" id="api_key" name="kura_ai_settings[api_key]" value="' . esc_attr($api_key) . '" class="regular-text">';
        echo '<p class="description">' . __('Enter your API key for the selected AI service.', 'kura-ai') . '</p>';
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

    /**
     * Display the settings page.
     *
     * @since    1.0.0
     */
    public function display_settings_page()
    {
        include_once 'partials/kura-ai-settings-display.php';
    }

    /**
     * AJAX handler for running a security scan.
     *
     * @since    1.0.0
     */
    public function ajax_run_scan()
    {
        check_ajax_referer('kura_ai_nonce', 'nonce');

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
    // Add this to your ajax_clear_logs() method:
    public function ajax_clear_logs()
    {
        check_ajax_referer('kura_ai_nonce', 'nonce'); // Verify nonce

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('You do not have permission to clear logs.', 'kura-ai'), 403);
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

        if ($result !== false) {
            wp_send_json_success(__('Logs cleared successfully.', 'kura-ai'));
        } else {
            wp_send_json_error(__('Failed to clear logs.', 'kura-ai'), 500);
        }
    }

    /**
     * AJAX handler for getting AI suggestions.
     *
     * @since    1.0.0
     */
    public function ajax_get_suggestions()
    {
        check_ajax_referer('kura_ai_nonce', 'nonce');

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
        check_ajax_referer('kura_ai_nonce', 'nonce');

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
        check_ajax_referer('kura_ai_nonce', 'nonce');

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