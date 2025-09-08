<?php

// Mock WordPress functions if not available for testing
if (!function_exists('add_action')) {
    function add_action($tag, $function_to_add, $priority = 10, $accepted_args = 1) { return true; }
}
if (!function_exists('add_filter')) {
    function add_filter($tag, $function_to_add, $priority = 10, $accepted_args = 1) { return true; }
}
if (!function_exists('wp_next_scheduled')) {
    function wp_next_scheduled($hook, $args = array()) { return false; }
}
if (!function_exists('wp_unschedule_event')) {
    function wp_unschedule_event($timestamp, $hook, $args = array()) { return true; }
}
if (!function_exists('wp_clear_scheduled_hook')) {
    function wp_clear_scheduled_hook($hook, $args = array()) { return true; }
}
if (!function_exists('wp_schedule_event')) {
    function wp_schedule_event($timestamp, $recurrence, $hook, $args = array()) { return true; }
}
if (!function_exists('wp_get_schedules')) {
    function wp_get_schedules() { return array('hourly' => array('interval' => 3600, 'display' => 'Once Hourly')); }
}
if (!function_exists('wp_get_scheduled_events')) {
    function wp_get_scheduled_events() { return array(); }
}
if (!function_exists('current_user_can')) {
    function current_user_can($capability, $object_id = null) { return true; }
}
if (!function_exists('get_option')) {
    function get_option($option, $default = false) { return $default; }
}
if (!function_exists('update_option')) {
    function update_option($option, $value, $autoload = null) { return true; }
}

/**
 * Kura AI Cron Fix Class
 * 
 * Handles cron reschedule errors and prevents Action Scheduler conflicts
 */
class Kura_AI_Cron_Fix {
    
    public function __construct() {
        if (function_exists('add_action')) {
            add_action('init', array($this, 'init_cron_fixes'));
            add_action('wp_loaded', array($this, 'cleanup_old_events'));
        }
        if (function_exists('add_filter')) {
            add_filter('cron_schedules', array($this, 'add_custom_schedules'));
            add_filter('pre_update_option_cron', array($this, 'prevent_cron_overload'), 10, 2);
        }
    }
    
    public function init_cron_fixes() {
        // Only run if user has proper permissions
        if (function_exists('current_user_can') && !current_user_can('manage_options')) {
            return;
        }
        
        // Prevent cron overload by limiting concurrent events
        $this->limit_cron_events();
        
        // Clean up any duplicate or conflicting events
        $this->remove_duplicate_events();
        
        // Check if we have too many cron events
        if (function_exists('get_option')) {
            $cron_array = get_option('cron', array());
            
            if (is_array($cron_array) && count($cron_array) > 100) {
                $this->cleanup_old_cron_events();
            }
        }
    }
    
    private function limit_cron_events() {
        if (!function_exists('wp_next_scheduled') || !function_exists('wp_unschedule_event')) {
            return;
        }
        
        $kura_hooks = [
            'kura_ai_daily_scan',
            'kura_ai_file_monitor_scan',
            'kura_ai_cleanup_logs'
        ];
        
        foreach ($kura_hooks as $hook) {
            $scheduled = wp_next_scheduled($hook);
            if ($scheduled && $scheduled < time()) {
                // Remove expired events that couldn't run
                wp_unschedule_event($scheduled, $hook);
                error_log("Kura AI: Removed expired cron event: {$hook}");
            }
        }
    }
    
    private function remove_duplicate_events() {
        if (!function_exists('wp_clear_scheduled_hook')) {
            return;
        }
        
        // Clear any duplicate Kura AI events
        $hooks_to_clean = [
            'kura_ai_malware_scan',
            'kura_ai_auto_scan',
            'kura_ai_performance_scan',
            'kura_ai_security_scan',
            'kura_ai_backup_scan'
        ];
        
        foreach ($hooks_to_clean as $hook) {
            wp_clear_scheduled_hook($hook);
        }
    }
    
    public function cleanup_old_events() {
        if (!function_exists('wp_next_scheduled') || !function_exists('wp_clear_scheduled_hook')) {
            return;
        }
        
        // Remove any Action Scheduler conflicts
        $action_hooks = [
            'action_scheduler_run_queue',
            'action_scheduler_cleanup_logs'
        ];
        
        foreach ($action_hooks as $hook) {
            $scheduled = wp_next_scheduled($hook);
            if ($scheduled && function_exists('wp_get_scheduled_events')) {
                // Only clear if there are multiple instances
                $all_scheduled = wp_get_scheduled_events();
                $count = 0;
                foreach ($all_scheduled as $timestamp => $events) {
                    if (isset($events[$hook])) {
                        $count += count($events[$hook]);
                    }
                }
                
                if ($count > 1) {
                    wp_clear_scheduled_hook($hook);
                    error_log("Kura AI: Cleared duplicate Action Scheduler hook: {$hook}");
                }
            }
        }
    }
    
    private function cleanup_old_cron_events() {
        if (!function_exists('get_option') || !function_exists('wp_unschedule_event') || !function_exists('update_option')) {
            return;
        }
        
        $current_time = time();
        $cron_array = get_option('cron', array());
        $cleaned = false;
        
        if (!is_array($cron_array)) {
            return;
        }
        
        foreach ($cron_array as $timestamp => $cron) {
            // Remove events older than 24 hours that haven't run
            if ($timestamp < ($current_time - 86400)) {
                unset($cron_array[$timestamp]);
                $cleaned = true;
            }
        }
        
        if ($cleaned) {
            update_option('cron', $cron_array);
            error_log('Kura AI: Cleaned up old cron events');
        }
    }
    
    public function prevent_cron_overload($value, $old_value) {
        if (!is_array($value)) {
            return $value;
        }
        
        // Limit total cron events to prevent database overload
        if (count($value) > 150) {
            error_log('Kura AI: Cron overload detected, limiting events');
            
            // Keep only the most recent 100 events
            $value = array_slice($value, -100, null, true);
        }
        
        return $value;
    }
    
    public function add_custom_schedules($schedules) {
        if (!is_array($schedules)) {
            $schedules = array();
        }
        
        // Add a less frequent schedule to reduce cron load
        $schedules['kura_ai_reduced'] = array(
            'interval' => 21600, // 6 hours
            'display' => 'Every 6 Hours (Kura AI Reduced)'
        );
        
        // Add weekly schedule for compliance scans
        $schedules['weekly'] = array(
            'interval' => 604800, // 7 days
            'display' => 'Once Weekly'
        );
        
        // Add monthly schedule for compliance scans
        $schedules['monthly'] = array(
            'interval' => 2635200, // 30.5 days
            'display' => 'Once Monthly'
        );
        
        return $schedules;
    }
}