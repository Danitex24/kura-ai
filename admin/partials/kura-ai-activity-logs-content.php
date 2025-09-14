<?php
/**
 * Provide a admin area view for the activity logs
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @package    Kura_AI
 * @subpackage Kura_AI/admin/partials
 */

// If this file is called directly, abort.
if (!defined('ABSPATH')) {
    exit;
}

// WordPress function stubs for static analysis
if (!function_exists('_e')) {
    function _e($text, $domain = 'default') {}
}
if (!function_exists('selected')) {
    function selected($selected, $current = true, $echo = true) {}
}
if (!function_exists('esc_attr')) {
    function esc_attr($text) {}
}
if (!function_exists('esc_html')) {
    function esc_html($text) {}
}
if (!function_exists('sanitize_text_field')) {
    function sanitize_text_field($str) {}
}
if (!function_exists('get_date_from_gmt')) {
    function get_date_from_gmt($string, $format = '') {}
}
if (!function_exists('get_option')) {
    function get_option($option, $default = false) {}
}
if (!function_exists('maybe_unserialize')) {
    function maybe_unserialize($data) {}
}
if (!function_exists('paginate_links')) {
    function paginate_links($args = '') {}
}
if (!function_exists('add_query_arg')) {
    function add_query_arg() {}
}
if (!function_exists('ucfirst')) {
    function ucfirst($string) {}
}
// json_encode is already defined in PHP core, no need to redefine
?>

<!-- Activity Logs Section -->
<div class="kura-ai-card">
    <div class="kura-ai-card-header">
        <h2><?php _e('Filter Logs', 'kura-ai'); ?></h2>
    </div>
    <div class="kura-ai-card-body">
        <form method="get" action="">
            <input type="hidden" name="page" value="kura-ai-logs">
            <input type="hidden" name="tab" value="activity">
            <div class="kura-ai-filter-row">
                <div class="kura-ai-filter-col">
                    <label for="type"><?php _e('Log Type', 'kura-ai'); ?></label>
                    <select name="type" id="type">
                        <option value=""><?php _e('All Types', 'kura-ai'); ?></option>
                        <option value="info" <?php selected(isset($_GET['type']) ? $_GET['type'] : '', 'info'); ?>><?php _e('Info', 'kura-ai'); ?></option>
                        <option value="warning" <?php selected(isset($_GET['type']) ? $_GET['type'] : '', 'warning'); ?>><?php _e('Warning', 'kura-ai'); ?></option>
                        <option value="error" <?php selected(isset($_GET['type']) ? $_GET['type'] : '', 'error'); ?>><?php _e('Error', 'kura-ai'); ?></option>
                    </select>
                </div>
                <div class="kura-ai-filter-col">
                    <label for="severity"><?php _e('Severity', 'kura-ai'); ?></label>
                    <select name="severity" id="severity">
                        <option value=""><?php _e('All Severities', 'kura-ai'); ?></option>
                        <option value="low" <?php selected(isset($_GET['severity']) ? $_GET['severity'] : '', 'low'); ?>><?php _e('Low', 'kura-ai'); ?></option>
                        <option value="medium" <?php selected(isset($_GET['severity']) ? $_GET['severity'] : '', 'medium'); ?>><?php _e('Medium', 'kura-ai'); ?></option>
                        <option value="high" <?php selected(isset($_GET['severity']) ? $_GET['severity'] : '', 'high'); ?>><?php _e('High', 'kura-ai'); ?></option>
                    </select>
                </div>
                <div class="kura-ai-filter-col">
                    <label for="search"><?php _e('Search', 'kura-ai'); ?></label>
                    <input type="text" name="search" id="search" value="<?php echo isset($_GET['search']) ? esc_attr($_GET['search']) : ''; ?>" placeholder="<?php _e('Search logs...', 'kura-ai'); ?>">
                </div>
                <div class="kura-ai-filter-col kura-ai-filter-actions">
                    <button type="submit" class="button button-primary"><?php _e('Apply Filters', 'kura-ai'); ?></button>
                    <a href="?page=kura-ai-logs&tab=activity" class="button"><?php _e('Reset', 'kura-ai'); ?></a>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="kura-ai-card">
    <div class="kura-ai-card-header">
        <h2><?php _e('Log Actions', 'kura-ai'); ?></h2>
    </div>
    <div class="kura-ai-card-body">
        <div class="kura-ai-log-actions">
            <button class="button kura-ai-export-logs" data-type="activity" data-format="csv">
                <span class="dashicons dashicons-media-spreadsheet"></span> 
                <?php _e('Export CSV', 'kura-ai'); ?>
            </button>
            <button class="button kura-ai-export-logs" data-type="activity" data-format="json">
                <span class="dashicons dashicons-media-code"></span> 
                <?php _e('Export JSON', 'kura-ai'); ?>
            </button>
            <button class="button kura-ai-clear-logs" data-type="activity">
                <span class="dashicons dashicons-trash"></span> 
                <?php _e('Clear Logs', 'kura-ai'); ?>
            </button>
        </div>
    </div>
</div>

<!-- Clearing log display  -->
<div id="kura-ai-clear-message" class="notice" style="display: none;"></div>
<div class="kura-ai-clear-loading" style="display: none;">
    <span class="spinner is-active"></span> <?php _e('Clearing logs...', 'kura-ai'); ?>
</div>
<?php
// Define plugin name and version to avoid undefined variable warnings
$plugin_name = 'kura-ai';
$version = '1.0.0';
$logger = new \Kura_AI\Kura_AI_Logger($plugin_name, $version);

$args = array(
    'type' => !empty($_GET['type']) ? sanitize_text_field($_GET['type']) : '',
    'severity' => !empty($_GET['severity']) ? sanitize_text_field($_GET['severity']) : '',
    'search' => !empty($_GET['search']) ? sanitize_text_field($_GET['search']) : '',
    'per_page' => 20,
    'page' => !empty($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1
);

$logs = $logger->get_logs($args);
?>

<!-- Logs Table Card -->
<div class="kura-ai-card">
    <div class="kura-ai-card-header">
        <h2><?php _e('Activity Logs', 'kura-ai'); ?></h2>
    </div>
    <div class="kura-ai-card-body">
        <div class="kura-ai-logs-table">
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th><?php _e('ID', 'kura-ai'); ?></th>
                <th><?php _e('Date', 'kura-ai'); ?></th>
                <th><?php _e('Type', 'kura-ai'); ?></th>
                <th><?php _e('Message', 'kura-ai'); ?></th>
                <th><?php _e('Severity', 'kura-ai'); ?></th>
                <th><?php _e('Details', 'kura-ai'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($logs['items'])) : ?>
                <tr>
                    <td colspan="6"><?php _e('No logs found.', 'kura-ai'); ?></td>
                </tr>
            <?php else : ?>
                <?php foreach ($logs['items'] as $log) : ?>
                    <tr>
                        <td><?php echo esc_html($log->id); ?></td>
                        <td><?php echo esc_html(get_date_from_gmt($log->created_at, get_option('date_format') . ' ' . get_option('time_format'))); ?></td>
                        <td>
                            <span class="kura-ai-log-type kura-ai-log-type-<?php echo esc_attr($log->type); ?>">
                                <?php echo esc_html(ucfirst($log->type)); ?>
                            </span>
                        </td>
                        <td><?php echo esc_html($log->message); ?></td>
                        <td>
                            <span class="kura-ai-severity kura-ai-severity-<?php echo esc_attr($log->severity); ?>">
                                <?php echo esc_html(ucfirst($log->severity)); ?>
                            </span>
                        </td>
                        <td>
                            <button class="button button-small kura-ai-view-details" data-log-id="<?php echo esc_attr($log->id); ?>">
                                <span class="dashicons dashicons-visibility"></span>
                            </button>
                            <div class="kura-ai-log-details" id="kura-ai-log-details-<?php echo esc_attr($log->id); ?>" style="display: none;">
                                <?php if (!empty($log->data)) : ?>
                                    <pre><?php echo esc_html(json_encode(maybe_unserialize($log->data), JSON_PRETTY_PRINT)); ?></pre>
                                <?php else : ?>
                                    <p><?php _e('No additional details available.', 'kura-ai'); ?></p>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php if (!empty($logs['total_pages']) && $logs['total_pages'] > 1) : ?>
    <div class="kura-ai-pagination">
        <?php
        echo paginate_links(array(
            'base' => add_query_arg('paged', '%#%'),
            'format' => '',
            'prev_text' => '&laquo;',
            'next_text' => '&raquo;',
            'total' => $logs['total_pages'],
            'current' => $args['page'],
        ));
        ?>
    </div>
<?php endif; ?>
    </div>
</div>

<!-- Log Details Modal -->
<div id="kura-ai-log-details-modal" class="kura-ai-modal">
    <div class="kura-ai-modal-content">
        <span class="kura-ai-modal-close">&times;</span>
        <h3><?php _e('Log Details', 'kura-ai'); ?></h3>
        <div id="kura-ai-log-details-content"></div>
    </div>
</div>