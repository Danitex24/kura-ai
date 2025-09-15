<?php
/**
 * Kura AI Plugin Uninstall
 *
 * This file runs when the plugin is uninstalled to clean up plugin data.
 */

// If uninstall is not called from WordPress, exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Ensure WordPress environment is loaded
if (!defined('ABSPATH')) {
    /** Set up WordPress environment */
    require_once(dirname(dirname(dirname(dirname(__FILE__)))) . '/wp-load.php');
}

// Access WordPress globals
global $wpdb;

// Ensure $wpdb is available
if (!isset($wpdb) || !is_object($wpdb)) {
    error_log('Kura AI Plugin: $wpdb not available during uninstall');
    return;
}

// Define plugin options to be deleted
$options = [
    'kura_ai_settings',
    'kura_ai_api_key',
    'kura_ai_model',
    'kura_ai_temperature',
    'kura_ai_max_tokens',
    'kura_ai_presence_penalty',
    'kura_ai_frequency_penalty'
];

// Delete plugin options using raw SQL
if (isset($wpdb->options)) {
    foreach ($options as $option) {
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options} WHERE option_name = %s",
                $option
            )
        );
    }
}

// Define plugin tables to be dropped
$tables = [
    'kura_ai_logs',
    'kura_ai_api_keys',
    'kura_ai_malware_patterns',
    'kura_ai_file_integrity',
    'kura_ai_user_activity',
    'kura_ai_api_security',
    'kura_ai_security_reports',
    'kura_ai_vulnerabilities',
    'kura_ai_hardening_rules',
    'kura_ai_ai_analysis',
    'kura_ai_file_versions',
    'kura_ai_feedback',
    'kura_ai_analytics',
    'kura_ai_brute_force',
    'kura_ai_audit_logs',
    'kura_ai_activity_logs',
    'kura_ai_file_scan_results',
    'kura_ai_ip_list',
    'kura_ai_login_attempts',
    'kura_ai_audit_history',
    'kura_ai_conversations',
    'kura_ai_messages'
];

// Drop plugin tables using raw SQL with maximum force
if (isset($wpdb) && is_object($wpdb)) {
    // Store original settings
    $old_mode = $wpdb->get_var("SELECT @@sql_mode");
    
    // Disable all restrictions
    $wpdb->query("SET FOREIGN_KEY_CHECKS = 0");
    $wpdb->query("SET SESSION sql_mode=''");
    $wpdb->query("SET UNIQUE_CHECKS = 0");
    
    foreach ($tables as $table) {
        $table_name = $wpdb->prefix . $table;
        
        // Get table status
        $table_exists = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM information_schema.tables 
                WHERE table_schema = DATABASE() 
                AND table_name = %s",
                $table_name
            )
        );
        
        if ($table_exists) {
            // Drop all foreign key constraints
            $constraints = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT CONSTRAINT_NAME 
                    FROM information_schema.TABLE_CONSTRAINTS 
                    WHERE CONSTRAINT_TYPE = 'FOREIGN KEY' 
                    AND TABLE_SCHEMA = DATABASE() 
                    AND TABLE_NAME = %s",
                    $table_name
                )
            );
            
            foreach ($constraints as $constraint) {
                $wpdb->query(
                    "ALTER TABLE `{$table_name}` 
                    DROP FOREIGN KEY `{$constraint->CONSTRAINT_NAME}`"
                );
            }
            
            // Get database name safely
            $db_name = defined('DB_NAME') ? DB_NAME : $wpdb->dbname;
            
            // Force drop using multiple aggressive methods
            $wpdb->query("DROP TABLE IF EXISTS `{$table_name}`");
            $wpdb->query("DROP TABLE `{$table_name}`");
            $wpdb->query("DROP TABLE IF EXISTS `{$db_name}`.`{$table_name}`");
            $wpdb->query("DROP TABLE `{$db_name}`.`{$table_name}`");
            $wpdb->query("DROP TABLE {$table_name} CASCADE");
            
            // Direct MySQL force drop
            $wpdb->query(
                "DROP TABLE IF EXISTS `{$table_name}` FORCE"
            );
        }
    }
    
    // Restore original settings
    $wpdb->query("SET FOREIGN_KEY_CHECKS = 1");
    $wpdb->query("SET UNIQUE_CHECKS = 1");
    $wpdb->query("SET SESSION sql_mode = '{$old_mode}'");
}

// Clear scheduled hooks
$hooks = [
    'kura_ai_daily_scan',
    'kura_ai_malware_scan',
    'kura_ai_file_monitor_scan',
    'kura_ai_auto_scan',
    'kura_ai_performance_scan',
    'kura_ai_security_scan',
    'kura_ai_backup_scan',
    'kura_ai_cleanup_logs',
    'kura_ai_update_check',
    'kura_ai_weekly_report',
    'kura_ai_compliance_scan',
    'kura_ai_vulnerability_check'
];

foreach ($hooks as $hook) {
    $timestamp = wp_next_scheduled($hook);
    if ($timestamp) {
        wp_clear_scheduled_hook($hook);
        error_log("Cleared scheduled hook: {$hook}");
    } else {
        error_log("No scheduled hook found for: {$hook}");
    }
}

// Final cleanup verification
$cleanup_successful = true;

// Verify options deletion
foreach ($options as $option) {
    if (get_option($option) !== false) {
        error_log("Option still exists after deletion: {$option}");
        $cleanup_successful = false;
    }
}

// Verify table deletion
foreach ($tables as $table) {
    $table_name = $wpdb->prefix . $table;
    $exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_name));
    if ($exists) {
        error_log("Table still exists after deletion: {$table_name}");
        $cleanup_successful = false;
    }
}

// Verify scheduled hooks
foreach ($hooks as $hook) {
    if (wp_next_scheduled($hook)) {
        error_log("Scheduled hook still exists: {$hook}");
        $cleanup_successful = false;
    }
}

if ($cleanup_successful) {
    error_log('Kura AI plugin has been successfully uninstalled - all data cleaned up.');
} else {
    error_log('Kura AI plugin uninstallation completed with some remaining data.');
}