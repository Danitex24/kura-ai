<?php
/**
 * The security scanner functionality of the plugin.
 *
 * @package    Kura_AI
 * @subpackage Kura_AI/includes
 * @author     Daniel Abughdyer <daniel@danovatesolutions.org>
 */
class Kura_AI_Security_Scanner
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
     * Run a complete security scan.
     *
     * @since    1.0.0
     * @return   array    Array of scan results
     */
    public function run_scan()
    {
        $results = array();

        $results['core_integrity'] = $this->check_core_integrity();
        $results['plugin_vulnerabilities'] = $this->check_plugin_vulnerabilities();
        $results['theme_vulnerabilities'] = $this->check_theme_vulnerabilities();
        $results['file_permissions'] = $this->check_file_permissions();
        $results['sensitive_data'] = $this->check_sensitive_data_exposure();
        $results['malware'] = $this->check_for_malware();
        $results['database_security'] = $this->check_database_security();
        $results['user_security'] = $this->check_user_security();

        // Add unique IDs to each issue
        foreach ($results as $category => &$issues) {
            if (is_array($issues)) {
                foreach ($issues as &$issue) {
                    if (is_array($issue)) {
                        $issue['id'] = wp_generate_uuid4();
                    }
                }
            }
        }

        // Store scan results
        $settings = get_option('kura_ai_settings');
        $settings['last_scan'] = time();
        $settings['scan_results'] = $results;
        update_option('kura_ai_settings', $settings);

        return $results;
    }

    /**
     * Check WordPress core integrity.
     *
     * @since    1.0.0
     * @return   array    Array of core integrity issues
     */
    private function check_core_integrity()
    {
        $issues = array();

        // Check if WP_DEBUG is enabled
        if (defined('WP_DEBUG') && WP_DEBUG) {
            $issues[] = array(
                'type' => 'debug_mode',
                'severity' => 'medium',
                'message' => __('WordPress debug mode is enabled', 'kura-ai'),
                'fix' => __('Disable WP_DEBUG in wp-config.php', 'kura-ai')
            );
        }

        // Check core file integrity - UPDATED VERSION
        if (!function_exists('get_core_checksums')) {
            require_once ABSPATH . 'wp-admin/includes/update.php';
        }

        // Get current WordPress version
        $wp_version = get_bloginfo('version');

        // Get checksums for this version
        $checksums = get_core_checksums($wp_version, 'en_US');
        if (!$checksums) {
            $issues[] = array(
                'type' => 'checksum_fail',
                'severity' => 'high',
                'message' => __('Could not verify core file integrity - checksums unavailable', 'kura-ai'),
                'fix' => __('Check WordPress.org availability or try again later', 'kura-ai')
            );
            return $issues;
        }

        // Get core files list
        $core_files = get_core_files(ABSPATH);
        if (!$core_files) {
            $issues[] = array(
                'type' => 'core_files_fail',
                'severity' => 'high',
                'message' => __('Could not retrieve core files list', 'kura-ai'),
                'fix' => __('Check file permissions or WordPress installation', 'kura-ai')
            );
            return $issues;
        }

        foreach ($core_files as $file) {
            if (!isset($checksums[$file])) {
                continue;
            }

            $file_path = ABSPATH . $file;
            if (!file_exists($file_path)) {
                continue;
            }

            $file_hash = md5_file($file_path);
            if ($file_hash !== $checksums[$file]) {
                $issues[] = array(
                    'type' => 'core_modification',
                    'severity' => 'high',
                    'message' => sprintf(__('Core file modified: %s', 'kura-ai'), $file),
                    'fix' => __('Replace with original WordPress file', 'kura-ai')
                );
            }
        }

        return $issues;
    }

    /**
     * Check for vulnerable plugins.
     *
     * @since    1.0.0
     * @return   array    Array of plugin vulnerabilities
     */
    private function check_plugin_vulnerabilities()
    {
        $issues = array();
        $plugins = get_plugins();
        $update_plugins = get_site_transient('update_plugins');

        foreach ($plugins as $plugin_path => $plugin_data) {
            // Check for outdated plugins
            if (isset($update_plugins->response[$plugin_path])) {
                $issues[] = array(
                    'type' => 'outdated_plugin',
                    'severity' => 'high',
                    'message' => sprintf(__('Outdated plugin: %s (%s)', 'kura-ai'), $plugin_data['Name'], $plugin_data['Version']),
                    'fix' => __('Update to latest version', 'kura-ai'),
                    'plugin' => $plugin_path
                );
            }

            // Check for abandoned plugins (no updates in 2 years)
            if (isset($plugin_data['LastUpdated'])) {
                $last_updated = strtotime($plugin_data['LastUpdated']);
                if ($last_updated && (time() - $last_updated) > (2 * YEAR_IN_SECONDS)) {
                    $issues[] = array(
                        'type' => 'abandoned_plugin',
                        'severity' => 'medium',
                        'message' => sprintf(
                            __('Potentially abandoned plugin: %s (last updated %s)', 'kura-ai'),
                            $plugin_data['Name'],
                            date_i18n(get_option('date_format'), $last_updated),
                        ),
                        'fix' => __('Consider replacing with an actively maintained alternative', 'kura-ai'),
                        'plugin' => $plugin_path
                    );
                }
            }
        }

        return $issues;
    }

    /**
     * Check for vulnerable themes.
     *
     * @since    1.0.0
     * @return   array    Array of theme vulnerabilities
     */
    private function check_theme_vulnerabilities()
    {
        $issues = array();
        $themes = wp_get_themes();
        $update_themes = get_site_transient('update_themes');

        foreach ($themes as $theme) {
            $theme_name = $theme->get('Name');
            $theme_version = $theme->get('Version');

            // Check for outdated themes
            if (isset($update_themes->response[$theme->stylesheet])) {
                $issues[] = array(
                    'type' => 'outdated_theme',
                    'severity' => 'high',
                    'message' => sprintf(__('Outdated theme: %s (%s)', 'kura-ai'), $theme_name, $theme_version),
                    'fix' => __('Update to latest version', 'kura-ai'),
                    'theme' => $theme->stylesheet
                );
            }
        }

        return $issues;
    }

    /**
     * Check file and directory permissions.
     *
     * @since    1.0.0
     * @return   array    Array of permission issues
     */
    private function check_file_permissions()
    {
        $issues = array();
        $critical_files = array(
            ABSPATH . 'wp-config.php',
            ABSPATH . '.htaccess',
            WP_CONTENT_DIR . '/',
            WP_PLUGIN_DIR . '/'
        );

        foreach ($critical_files as $file) {
            if (!file_exists($file)) {
                continue;
            }

            $perms = fileperms($file) & 0777;

            // Check if file is writable by group or others
            if (($perms & 0022) != 0) {
                $issues[] = array(
                    'type' => 'insecure_permission',
                    'severity' => 'high',
                    'message' => sprintf(__('Insecure file permission: %s (current: %o)', 'kura-ai'), str_replace(ABSPATH, '', $file), $perms),
                    'fix' => __('Set permissions to 644 for files, 755 for directories', 'kura-ai')
                );
            }
        }

        return $issues;
    }

    /**
     * Check for sensitive data exposure.
     *
     * @since    1.0.0
     * @return   array    Array of sensitive data issues
     */
    private function check_sensitive_data_exposure()
    {
        $issues = array();

        // Check for exposed .git directories
        if (is_dir(ABSPATH . '.git')) {
            $issues[] = array(
                'type' => 'exposed_git',
                'severity' => 'high',
                'message' => __('Git repository exposed in web root', 'kura-ai'),
                'fix' => __('Remove .git directory or restrict access via .htaccess', 'kura-ai')
            );
        }

        // Check for exposed backup files
        $backup_files = array(
            ABSPATH . 'wp-config.php.bak',
            ABSPATH . 'wp-config.php.old',
            ABSPATH . 'wp-config.php~',
            ABSPATH . 'wp-config.php.backup'
        );

        foreach ($backup_files as $file) {
            if (file_exists($file)) {
                $issues[] = array(
                    'type' => 'exposed_backup',
                    'severity' => 'high',
                    'message' => sprintf(__('Backup file exposed: %s', 'kura-ai'), str_replace(ABSPATH, '', $file)),
                    'fix' => __('Remove backup file from web directory', 'kura-ai')
                );
            }
        }

        return $issues;
    }

    /**
     * Check for common malware patterns.
     *
     * @since    1.0.0
     * @return   array    Array of malware issues
     */
    private function check_for_malware()
    {
        $issues = array();
        $suspicious_patterns = array(
            '/eval\(base64_decode\(/i',
            '/system\(/i',
            '/shell_exec\(/i',
            '/passthru\(/i',
            '/exec\(/i',
            '/popen\(/i',
            '/proc_open\(/i',
            '/allow_url_include/i',
            '/phpinfo\(/i'
        );

        // Scan PHP files in wp-content
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(WP_CONTENT_DIR));

        foreach ($files as $file) {
            if ($file->isDir() || $file->getExtension() !== 'php') {
                continue;
            }

            $content = file_get_contents($file->getPathname());
            foreach ($suspicious_patterns as $pattern) {
                if (preg_match($pattern, $content)) {
                    $issues[] = array(
                        'type' => 'suspicious_code',
                        'severity' => 'critical',
                        'message' => sprintf(__('Suspicious code found in: %s', 'kura-ai'), str_replace(ABSPATH, '', $file->getPathname())),
                        'fix' => __('Inspect and clean the file', 'kura-ai')
                    );
                    break;
                }
            }
        }

        return $issues;
    }

    /**
     * Check database security issues.
     *
     * @since    1.0.0
     * @return   array    Array of database security issues
     */
    private function check_database_security()
    {
        $issues = array();
        global $wpdb;

        // Check for default table prefix
        if ($wpdb->prefix === 'wp_') {
            $issues[] = array(
                'type' => 'default_table_prefix',
                'severity' => 'medium',
                'message' => __('Using default database table prefix (wp_)', 'kura-ai'),
                'fix' => __('Change database table prefix', 'kura-ai')
            );
        }

        // Check for users with admin privileges
        $admin_users = $wpdb->get_results(
            "SELECT user_login FROM {$wpdb->users} 
             INNER JOIN {$wpdb->usermeta} ON {$wpdb->users}.ID = {$wpdb->usermeta}.user_id 
             WHERE {$wpdb->usermeta}.meta_key = '{$wpdb->prefix}capabilities' 
             AND {$wpdb->usermeta}.meta_value LIKE '%administrator%'",
        );

        if (count($admin_users) > 1) {
            $issues[] = array(
                'type' => 'multiple_admins',
                'severity' => 'low',
                'message' => __('Multiple administrator accounts detected', 'kura-ai'),
                'fix' => __('Review administrator accounts and remove unnecessary ones', 'kura-ai')
            );
        }

        return $issues;
    }

    /**
     * Check user security issues.
     *
     * @since    1.0.0
     * @return   array    Array of user security issues
     */
    private function check_user_security()
    {
        $issues = array();

        // Check for users with username 'admin'
        if (username_exists('admin')) {
            $issues[] = array(
                'type' => 'default_admin_user',
                'severity' => 'high',
                'message' => __('Default "admin" user account exists', 'kura-ai'),
                'fix' => __('Create new admin account and delete the "admin" account', 'kura-ai')
            );
        }

        return $issues;
    }
    /**
     * Apply a security fix.
     *
     * @since    1.0.0
     * @param    string    $issue_id    The ID of the issue to fix.
     * @param    string    $fix        The fix to apply.
     * @return   mixed     Array with success message on success, WP_Error on failure
     */
    public function apply_fix($issue_id, $fix) {
        // Get stored scan results
        $settings = get_option('kura_ai_settings');
        $results = isset($settings['scan_results']) ? $settings['scan_results'] : array();

        // Find the issue by ID
        $found_issue = null;
        $found_category = null;
        foreach ($results as $category => $issues) {
            if (is_array($issues)) {
                foreach ($issues as $index => $issue) {
                    if (isset($issue['id']) && $issue['id'] === $issue_id) {
                        $found_issue = $issue;
                        $found_category = $category;
                        break 2;
                    }
                }
            }
        }

        if (!$found_issue) {
            return new WP_Error('issue_not_found', __('Issue not found in scan results.', 'kura-ai'));
        }

        // Apply the fix based on issue type
        $result = false;
        $message = '';

        switch ($found_issue['type']) {
            case 'debug_mode':
                $result = $this->fix_debug_mode();
                $message = __('Debug mode has been disabled successfully.', 'kura-ai');
                break;

            case 'outdated_plugin':
                if (!empty($found_issue['plugin'])) {
                    $result = $this->fix_outdated_plugin($found_issue['plugin']);
                    $message = sprintf(__('Plugin %s has been updated successfully.', 'kura-ai'), basename($found_issue['plugin']));
                }
                break;

            case 'outdated_theme':
                if (!empty($found_issue['theme'])) {
                    $result = $this->fix_outdated_theme($found_issue['theme']);
                    $message = sprintf(__('Theme %s has been updated successfully.', 'kura-ai'), $found_issue['theme']);
                }
                break;

            case 'file_permissions':
                if (!empty($found_issue['file'])) {
                    $result = $this->fix_file_permissions($found_issue['file']);
                    $message = sprintf(__('File permissions for %s have been updated successfully.', 'kura-ai'), basename($found_issue['file']));
                }
                break;

            default:
                return new WP_Error('unsupported_fix', __('Automatic fix not supported for this issue type.', 'kura-ai'));
        }

        if (is_wp_error($result)) {
            return $result;
        } elseif ($result === false) {
            return new WP_Error('fix_failed', __('Could not apply the fix.', 'kura-ai'));
        }

        // Remove the fixed issue from scan results
        if (isset($results[$found_category])) {
            foreach ($results[$found_category] as $key => $issue) {
                if (isset($issue['id']) && $issue['id'] === $issue_id) {
                    unset($results[$found_category][$key]);
                    break;
                }
            }
            // Reindex the array
            $results[$found_category] = array_values($results[$found_category]);
            // Update the stored results
            $settings['scan_results'] = $results;
            update_option('kura_ai_settings', $settings);
        }

        return array(
            'message' => $message,
            'result' => true
        );
    }

    /**
     * Fix WordPress debug mode.
     *
     * @since    1.0.0
     * @return   mixed     True on success, WP_Error on failure
     */
    private function fix_debug_mode() {
        $config_path = ABSPATH . 'wp-config.php';
        if (!file_exists($config_path)) {
            return new WP_Error('config_not_found', __('wp-config.php not found.', 'kura-ai'));
        }

        $config_content = file_get_contents($config_path);
        if ($config_content === false) {
            return new WP_Error('read_failed', __('Could not read wp-config.php.', 'kura-ai'));
        }

        // Replace debug settings
        $config_content = preg_replace(
            "/(define\s*\(\s*'WP_DEBUG'\s*,\s*)true\s*\);/",
            "define('WP_DEBUG', false);",
            $config_content
        );

        if (file_put_contents($config_path, $config_content) === false) {
            return new WP_Error('write_failed', __('Could not update wp-config.php.', 'kura-ai'));
        }

        return true;
    }

    /**
     * Fix outdated plugin by updating it.
     *
     * @since    1.0.0
     * @param    string    $plugin    The plugin path.
     * @return   mixed     True on success, WP_Error on failure
     */
    private function fix_outdated_plugin($plugin) {
        require_once(ABSPATH . 'wp-admin/includes/plugin.php');
        require_once(ABSPATH . 'wp-admin/includes/class-wp-upgrader.php');
        require_once(ABSPATH . 'wp-admin/includes/class-plugin-upgrader.php');

        $upgrader = new Plugin_Upgrader(new Automatic_Upgrader_Skin());
        $result = $upgrader->upgrade($plugin);

        if (is_wp_error($result)) {
            return $result;
        } elseif ($result === false) {
            return new WP_Error('update_failed', __('Plugin update failed.', 'kura-ai'));
        }

        return true;
    }

    /**
     * Fix outdated theme by updating it.
     *
     * @since    1.0.0
     * @param    string    $theme    The theme stylesheet.
     * @return   mixed     True on success, WP_Error on failure
     */
    private function fix_outdated_theme($theme) {
        require_once(ABSPATH . 'wp-admin/includes/class-wp-upgrader.php');
        require_once(ABSPATH . 'wp-admin/includes/class-theme-upgrader.php');

        $upgrader = new Theme_Upgrader(new Automatic_Upgrader_Skin());
        $result = $upgrader->upgrade($theme);

        if (is_wp_error($result)) {
            return $result;
        } elseif ($result === false) {
            return new WP_Error('update_failed', __('Theme update failed.', 'kura-ai'));
        }

        return true;
    }

    /**
     * Fix file permissions.
     *
     * @since    1.0.0
     * @param    string    $file    The file path.
     * @return   mixed     True on success, WP_Error on failure
     */
    private function fix_file_permissions($file) {
        if (!file_exists($file)) {
            return new WP_Error('file_not_found', __('File not found.', 'kura-ai'));
        }

        $mode = is_dir($file) ? 0755 : 0644;
        if (!@chmod($file, $mode)) {
            return new WP_Error('chmod_failed', __('Could not change file permissions.', 'kura-ai'));
        }

        return true;
    }
}