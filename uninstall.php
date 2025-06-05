<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @package    Kura_AI
 */

// If uninstall not called from WordPress, then exit.
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Delete plugin options
delete_option('kura_ai_settings');

// Delete database tables
global $wpdb;
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}kura_ai_logs");

// Clear scheduled events
wp_clear_scheduled_hook('kura_ai_daily_scan');