<?php
/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @package    Kura_AI
 * @subpackage Kura_AI/includes
 * @author     Daniel Abughdyer <daniel@danovatesolutions.org>
 */
class Kura_AI_Deactivator
{

    /**
     * Short Description. (use period)
     *
     * Long Description.
     *
     * @since    1.0.0
     */
    public static function deactivate()
    {
        // Clear scheduled cron jobs
        self::clear_cron_jobs();
    }

    /**
     * Clear all scheduled cron jobs for the plugin.
     *
     * @since    1.0.0
     */
    private static function clear_cron_jobs()
    {
        $timestamp = wp_next_scheduled('kura_ai_daily_scan');
        if ($timestamp) {
            wp_unschedule_event($timestamp, 'kura_ai_daily_scan');
        }
    }
}