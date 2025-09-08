<?php

namespace Kura_AI;

// Mock WordPress functions if not available for testing
if (!function_exists('wp_next_scheduled')) {
    function wp_next_scheduled($hook, $args = array()) { return false; }
}
if (!function_exists('wp_unschedule_event')) {
    function wp_unschedule_event($timestamp, $hook, $args = array()) { return true; }
}
if (!function_exists('wp_clear_scheduled_hook')) {
    function wp_clear_scheduled_hook($hook, $args = array()) { return true; }
}
if (!function_exists('delete_option')) {
    function delete_option($option) { return true; }
}
if (!function_exists('wp_cache_flush')) {
    function wp_cache_flush() { return true; }
}

/**
 * Fired during plugin deactivation
 *
 * @link       https://danovatesolutions.org
 * @since      1.0.0
 *
 * @package    Kura_AI
 * @subpackage Kura_AI/includes
 */

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since      1.0.0
 * @package    Kura_AI
 * @subpackage Kura_AI/includes
 * @author     Daniel Abughdyer <daniel@danovatesolutions.org>
 */
class Kura_AI_Deactivator {

    /**
     * Short Description. (use period)
     *
     * Long Description.
     *
     * @since    1.0.0
     */
    public static function deactivate() {
        // Clear scheduled cron jobs
        self::clear_cron_jobs();
        
        // Clean up plugin options
        self::cleanup_options();
    }

    /**
     * Clear all Kura AI cron jobs.
     *
     * @since    1.0.0
     */
    private static function clear_cron_jobs() {
        if (!function_exists('wp_next_scheduled') || !function_exists('wp_unschedule_event') || !function_exists('wp_clear_scheduled_hook')) {
            return;
        }
        
        // Clear all Kura AI cron events to prevent conflicts
        $hooks = [
            'kura_ai_daily_scan',
            'kura_ai_malware_scan',
            'kura_ai_file_monitor_scan',
            'kura_ai_auto_scan',
            'kura_ai_performance_scan',
            'kura_ai_security_scan',
            'kura_ai_backup_scan',
            'kura_ai_cleanup_logs',
            'kura_ai_update_check'
        ];
        
        foreach ($hooks as $hook) {
            $timestamp = wp_next_scheduled($hook);
            if ($timestamp) {
                wp_unschedule_event($timestamp, $hook);
            }
            // Also clear any remaining scheduled hooks
            wp_clear_scheduled_hook($hook);
        }
    }

    /**
     * Clean up plugin options.
     *
     * @since    1.0.0
     */
    private static function cleanup_options() {
        if (!function_exists('delete_option') || !function_exists('wp_cache_flush')) {
            return;
        }
        
        // Clean up plugin options
        delete_option('kura_ai_settings');
        delete_option('kura_ai_scan_results');
        delete_option('kura_ai_last_scan');
        
        // Clear any cached data
        wp_cache_flush();
    }
}