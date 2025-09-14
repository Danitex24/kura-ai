<?php
/**
 * Provide a admin area view for the audit log
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @package    Kura_AI
 * @subpackage Kura_AI/admin/partials
 */

// If this file is called directly, abort.
if (!defined('ABSPATH')) {
    die('Direct access not permitted.');
}

// Ensure WordPress core is loaded
require_once(ABSPATH . 'wp-load.php');

// Include WordPress core files needed for this page
require_once(ABSPATH . 'wp-admin/includes/user.php');
require_once(ABSPATH . 'wp-includes/pluggable.php');
require_once(ABSPATH . 'wp-includes/formatting.php');
require_once(ABSPATH . 'wp-includes/capabilities.php');
require_once(ABSPATH . 'wp-includes/functions.php');
?>

<div class="wrap kura-ai-audit-log-container">
    <h2><?php esc_html_e('Security Audit Log', 'kura-ai'); ?></h2>
    
    <div class="kura-ai-audit-settings-toggle">
        <button class="button" id="kura-ai-toggle-audit-settings">
            <span class="dashicons dashicons-filter"></span> 
            <?php esc_html_e('Filters & Settings', 'kura-ai'); ?>
        </button>
        <div class="kura-ai-tooltip">
            <span class="dashicons dashicons-info"></span>
            <span class="kura-ai-tooltiptext">
                <?php esc_html_e('Filter logs by type, severity, date range, and more.', 'kura-ai'); ?>
            </span>
        </div>
    </div>
    
    <div class="kura-ai-audit-settings" style="display: none;">
        <form method="get" action="">
            <input type="hidden" name="page" value="kura-ai-logs">
            <input type="hidden" name="tab" value="audit">
            
            <div class="kura-ai-audit-filters">
                <div class="kura-ai-filter-row">
                    <div class="kura-ai-filter-group">
                        <label for="event_type"><?php esc_html_e('Event Type', 'kura-ai'); ?>:</label>
                        <select name="event_type" id="event_type">
                            <option value=""><?php esc_html_e('All Types', 'kura-ai'); ?></option>
                            <?php foreach ($event_types as $type) : ?>
                                <option value="<?php echo esc_attr($type); ?>" <?php selected($event_type, $type); ?>>
                                    <?php echo esc_html(ucfirst(str_replace('_', ' ', $type))); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="kura-ai-filter-group">
                        <label for="action"><?php esc_html_e('Action', 'kura-ai'); ?>:</label>
                        <select name="action" id="action">
                            <option value=""><?php esc_html_e('All Actions', 'kura-ai'); ?></option>
                            <?php foreach ($actions as $act) : ?>
                                <option value="<?php echo esc_attr($act); ?>" <?php selected($action, $act); ?>>
                                    <?php echo esc_html(ucfirst(str_replace('_', ' ', $act))); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="kura-ai-filter-group">
                        <label for="severity"><?php esc_html_e('Severity', 'kura-ai'); ?>:</label>
                        <select name="severity" id="severity">
                            <option value=""><?php esc_html_e('All Severities', 'kura-ai'); ?></option>
                            <?php foreach ($severities as $sev) : ?>
                                <option value="<?php echo esc_attr($sev); ?>" <?php selected($severity, $sev); ?>>
                                    <?php echo esc_html(ucfirst($sev)); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="kura-ai-filter-row">
                    <div class="kura-ai-filter-group">
                        <label for="user_id"><?php esc_html_e('User', 'kura-ai'); ?>:</label>
                        <?php wp_dropdown_users(array(
                            'name' => 'user_id',
                            'id' => 'user_id',
                            'selected' => $user_id,
                            'show_option_all' => __('All Users', 'kura-ai'),
                            'class' => 'kura-ai-user-select',
                        )); ?>
                    </div>
                    
                    <div class="kura-ai-filter-group">
                        <label for="date_from"><?php esc_html_e('From Date', 'kura-ai'); ?>:</label>
                        <input type="date" name="date_from" id="date_from" value="<?php echo esc_attr($date_from); ?>">
                    </div>
                    
                    <div class="kura-ai-filter-group">
                        <label for="date_to"><?php esc_html_e('To Date', 'kura-ai'); ?>:</label>
                        <input type="date" name="date_to" id="date_to" value="<?php echo esc_attr($date_to); ?>">
                    </div>
                </div>
                
                <div class="kura-ai-filter-row">
                    <div class="kura-ai-filter-group kura-ai-search-group">
                        <label for="search"><?php esc_html_e('Search', 'kura-ai'); ?>:</label>
                        <input type="text" name="search" id="search" value="<?php echo esc_attr($search); ?>" placeholder="<?php esc_attr_e('Search logs...', 'kura-ai'); ?>">
                    </div>
                    
                    <div class="kura-ai-filter-actions">
                        <button type="submit" class="button button-primary">
                            <span class="dashicons dashicons-search"></span> 
                            <?php esc_html_e('Apply Filters', 'kura-ai'); ?>
                        </button>
                        <a href="?page=kura-ai-logs&tab=audit" class="button">
                            <span class="dashicons dashicons-dismiss"></span> 
                            <?php esc_html_e('Reset', 'kura-ai'); ?>
                        </a>
                    </div>
                </div>
            </div>
        </form>
    </div>
    
    <div class="kura-ai-audit-actions">
        <div class="kura-ai-export-actions">
            <button class="button kura-ai-export-logs" data-format="csv">
                <span class="dashicons dashicons-media-spreadsheet"></span> 
                <?php esc_html_e('Export CSV', 'kura-ai'); ?>
            </button>
            <button class="button kura-ai-export-logs" data-format="json">
                <span class="dashicons dashicons-media-code"></span> 
                <?php esc_html_e('Export JSON', 'kura-ai'); ?>
            </button>
            <div class="kura-ai-tooltip">
                <span class="dashicons dashicons-info"></span>
                <span class="kura-ai-tooltiptext">
                    <?php esc_html_e('Export logs in CSV or JSON format. Current filters will be applied.', 'kura-ai'); ?>
                </span>
            </div>
        </div>
        
        <div class="kura-ai-clear-actions">
            <button class="button kura-ai-clear-logs">
                <span class="dashicons dashicons-trash"></span> 
                <?php esc_html_e('Clear Logs', 'kura-ai'); ?>
            </button>
            <div class="kura-ai-tooltip">
                <span class="dashicons dashicons-info"></span>
                <span class="kura-ai-tooltiptext">
                    <?php esc_html_e('Clear all logs matching the current filters. This action cannot be undone.', 'kura-ai'); ?>
                </span>
            </div>
        </div>
    </div>
    
    <div class="kura-ai-audit-log-table-container">
        <table class="widefat striped kura-ai-audit-log-table">
            <thead>
                <tr>
                    <th><?php esc_html_e('ID', 'kura-ai'); ?></th>
                    <th><?php esc_html_e('Date & Time', 'kura-ai'); ?></th>
                    <th><?php esc_html_e('Event Type', 'kura-ai'); ?></th>
                    <th><?php esc_html_e('Action', 'kura-ai'); ?></th>
                    <th><?php esc_html_e('User', 'kura-ai'); ?></th>
                    <th><?php esc_html_e('IP Address', 'kura-ai'); ?></th>
                    <th><?php esc_html_e('Severity', 'kura-ai'); ?></th>
                    <th><?php esc_html_e('Details', 'kura-ai'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($logs['items'])) : ?>
                    <tr>
                        <td colspan="8"><?php esc_html_e('No audit logs found.', 'kura-ai'); ?></td>
                    </tr>
                <?php else : ?>
                    <?php foreach ($logs['items'] as $log) : ?>
                        <tr>
                            <td><?php echo esc_html(!empty($log->id) ? $log->id : ''); ?></td>
                            <td><?php echo esc_html(get_date_from_gmt(!empty($log->created_at) ? $log->created_at : '', get_option('date_format') . ' ' . get_option('time_format'))); ?></td>
                            <td><?php echo esc_html(ucfirst(str_replace('_', ' ', !empty($log->event_type) ? $log->event_type : ''))); ?></td>
                            <td><?php echo esc_html(ucfirst(str_replace('_', ' ', !empty($log->action) ? $log->action : ''))); ?></td>
                            <td>
                                <?php if (!empty($log->user_id)) : ?>
                                    <?php $user = get_userdata($log->user_id); ?>
                                    <?php if ($user) : ?>
                                        <a href="<?php echo esc_url(get_edit_user_link($log->user_id)); ?>">
                                            <?php echo esc_html($user->display_name); ?>
                                        </a>
                                    <?php else : ?>
                                        <?php esc_html_e('Deleted User', 'kura-ai'); ?> (<?php echo esc_html($log->user_id); ?>)
                                    <?php endif; ?>
                                <?php else : ?>
                                    <?php esc_html_e('System', 'kura-ai'); ?>
                                <?php endif; ?>
                            </td>
                            <td><?php echo esc_html(!empty($log->ip_address) ? $log->ip_address : ''); ?></td>
                            <td>
                                <span class="kura-ai-severity kura-ai-severity-<?php echo esc_attr(!empty($log->severity) ? $log->severity : 'info'); ?>">
                                    <?php echo esc_html(ucfirst(!empty($log->severity) ? $log->severity : 'info')); ?>
                                </span>
                            </td>
                            <td>
                                <button class="button button-small kura-ai-view-details" data-log-id="<?php echo esc_attr(!empty($log->id) ? $log->id : '0'); ?>">
                                    <span class="dashicons dashicons-visibility"></span>
                                </button>
                                <div class="kura-ai-log-details" id="kura-ai-log-details-<?php echo esc_attr(!empty($log->id) ? $log->id : '0'); ?>" style="display: none;">
                                    <?php if (!empty($log->object_type)) : ?>
                                        <p><strong><?php esc_html_e('Object Type', 'kura-ai'); ?>:</strong> <?php echo esc_html($log->object_type); ?></p>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($log->object_id)) : ?>
                                        <p><strong><?php esc_html_e('Object ID', 'kura-ai'); ?>:</strong> <?php echo esc_html($log->object_id); ?></p>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($log->object_name)) : ?>
                                        <p><strong><?php esc_html_e('Object Name', 'kura-ai'); ?>:</strong> <?php echo esc_html($log->object_name); ?></p>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($log->data)) : ?>
                                        <p><strong><?php esc_html_e('Additional Data', 'kura-ai'); ?>:</strong></p>
                                        <pre><?php 
                                            $data = !empty($log->data) && is_string($log->data) ? maybe_unserialize($log->data) : $log->data;
                                            echo esc_html(json_encode($data, JSON_PRETTY_PRINT)); 
                                        ?></pre>
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
                'current' => $page,
            ));
            ?>
        </div>
    <?php endif; ?>
    
    <!-- Log Details Modal -->
    <div id="kura-ai-log-details-modal" class="kura-ai-modal" style="display: none;">
        <div class="kura-ai-modal-content">
            <span class="kura-ai-modal-close">&times;</span>
            <h3><?php esc_html_e('Log Entry Details', 'kura-ai'); ?></h3>
            <div id="kura-ai-modal-content"></div>
        </div>
    </div>
</div>