<?php
// create function to handle errors
class Kura_AI_Error_Handler {
    /**
     * Handle errors by logging them to a file.
     *
     * @param int $errno The level of the error raised.
     * @param string $errstr The error message.
     * @param string $errfile The filename that the error was raised in.
     * @param int $errline The line number the error was raised at.
     */
    public static function handle_error($errno, $errstr, $errfile, $errline) {
        // Log the error to a file
        $log_file = KURA_AI_PLUGIN_DIR . 'logs/KuraAi-plugin-error.log';
        $max_size = 1024 * 1024; // 1MB
        if (file_exists($log_file) && filesize($log_file) >= $max_size) {
            $archive_name = KURA_AI_PLUGIN_DIR . 'logs/error-' . date('Ymd-His') . '.log';
            rename($log_file, $archive_name);
            file_put_contents($log_file, ''); // Create a new empty log file
        }
        $log_message = sprintf("[%s] Error: %s in %s on line %d\n", date('Y-m-d H:i:s'), $errstr, $errfile, $errline);
        error_log($log_message, 3, $log_file);
    }
}
// Register the error handler
set_error_handler(array('Kura_AI_Error_Handler', 'handle_error'));
// Ensure the error handler is set before any other code runs
if (!defined('KURA_AI_PLUGIN_DIR')) {
    define('KURA_AI_PLUGIN_DIR', plugin_dir_path(__FILE__));
}
// Ensure the error log directory exists
if (!file_exists(KURA_AI_PLUGIN_DIR . 'logs')) {
    mkdir(KURA_AI_PLUGIN_DIR . 'logs', 0755, true);
}
// Ensure the error log file exists
if (!file_exists(KURA_AI_PLUGIN_DIR . 'logs/error.log')) {
    file_put_contents(KURA_AI_PLUGIN_DIR . 'logs/error.log', '');
}
// Ensure the error handler is registered before any other code runs
if (!function_exists('set_error_handler')) {
    function set_error_handler($handler) {
        // Fallback for environments where set_error_handler is not available
        error_log("Error handler not set: " . print_r($handler, true));
    }
}
// Ensure the error handler is registered
if (!function_exists('error_log')) {
    function error_log($message, $message_type = 0, $destination = null, $extra_headers = null) {
        // Fallback for environments where error_log is not available
        echo "Error log: " . $message;
    }
}
