<div class="wrap kura-ai-logs">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <div class="kura-ai-logs-actions">
        <form id="kura-ai-logs-filter" method="get">
            <input type="hidden" name="page" value="kura-ai-logs">

            <div class="kura-ai-filter-group">
                <label for="kura-ai-log-type"><?php _e('Log Type', 'kura-ai'); ?></label>
                <select id="kura-ai-log-type" name="type">
                    <option value=""><?php _e('All Types', 'kura-ai'); ?></option>
                    <option value="scan" <?php selected(!empty($_GET['type']) && $_GET['type'] === 'scan'); ?>>
                        <?php _e('Scans', 'kura-ai'); ?></option>
                    <option value="fix_applied" <?php selected(!empty($_GET['type']) && $_GET['type'] === 'fix_applied'); ?>><?php _e('Applied Fixes', 'kura-ai'); ?></option>
                    <option value="ai_suggestion" <?php selected(!empty($_GET['type']) && $_GET['type'] === 'ai_suggestion'); ?>><?php _e('AI Suggestions', 'kura-ai'); ?></option>
                    <option value="export" <?php selected(!empty($_GET['type']) && $_GET['type'] === 'export'); ?>>
                        <?php _e('Exports', 'kura-ai'); ?></option>
                </select>
            </div>

            <div class="kura-ai-filter-group">
                <label for="kura-ai-log-severity"><?php _e('Severity', 'kura-ai'); ?></label>
                <select id="kura-ai-log-severity" name="severity">
                    <option value=""><?php _e('All Levels', 'kura-ai'); ?></option>
                    <option value="critical" <?php selected(!empty($_GET['severity']) && $_GET['severity'] === 'critical'); ?>><?php _e('Critical', 'kura-ai'); ?></option>
                    <option value="high" <?php selected(!empty($_GET['severity']) && $_GET['severity'] === 'high'); ?>>
                        <?php _e('High', 'kura-ai'); ?></option>
                    <option value="medium" <?php selected(!empty($_GET['severity']) && $_GET['severity'] === 'medium'); ?>><?php _e('Medium', 'kura-ai'); ?></option>
                    <option value="low" <?php selected(!empty($_GET['severity']) && $_GET['severity'] === 'low'); ?>>
                        <?php _e('Low', 'kura-ai'); ?></option>
                    <option value="info" <?php selected(!empty($_GET['severity']) && $_GET['severity'] === 'info'); ?>>
                        <?php _e('Info', 'kura-ai'); ?></option>
                </select>
            </div>

            <div class="kura-ai-filter-group">
                <label for="kura-ai-log-search"><?php _e('Search', 'kura-ai'); ?></label>
                <input type="text" id="kura-ai-log-search" name="search"
                    value="<?php echo esc_attr(!empty($_GET['search']) ? $_GET['search'] : ''); ?>">
            </div>

            <button type="submit" class="button"><?php _e('Filter', 'kura-ai'); ?></button>
            <button type="button" id="kura-ai-export-logs" class="button">
                <?php _e('Export to CSV', 'kura-ai'); ?>
            </button>
            <button type="button" id="kura-ai-clear-logs" class="button button-danger">
                <?php _e('Clear Logs', 'kura-ai'); ?>
            </button>
        </form>
    </div>
        <!-- Clearing log display  -->
<div id="kura-ai-clear-message" class="notice" style="display: none;"></div>
<div class="kura-ai-clear-loading" style="display: none;">
    <span class="spinner is-active"></span> Clearing logs...
</div>
    <?php
    $logger = new Kura_AI_Logger($this->plugin_name, $this->version);

    $args = array(
        'type' => !empty($_GET['type']) ? sanitize_text_field($_GET['type']) : '',
        'severity' => !empty($_GET['severity']) ? sanitize_text_field($_GET['severity']) : '',
        'search' => !empty($_GET['search']) ? sanitize_text_field($_GET['search']) : '',
        'per_page' => 20,
        'page' => !empty($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1
    );

    $logs = $logger->get_logs($args);
    ?>

    <div class="kura-ai-logs-table">
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php _e('ID', 'kura-ai'); ?></th>
                    <th><?php _e('Date', 'kura-ai'); ?></th>
                    <th><?php _e('Type', 'kura-ai'); ?></th>
                    <th><?php _e('Severity', 'kura-ai'); ?></th>
                    <th><?php _e('Message', 'kura-ai'); ?></th>
                    <th><?php _e('Details', 'kura-ai'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($logs['items'])): ?>
                    <tr>
                        <td colspan="6"><?php _e('No log entries found.', 'kura-ai'); ?></td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($logs['items'] as $log): ?>
                        <tr>
                            <td><?php echo $log['id']; ?></td>
                            <td><?php echo date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($log['created_at'])); ?>
                            </td>
                            <td><?php echo esc_html(ucfirst($log['log_type'])); ?></td>
                            <td>
                                <span class="kura-ai-severity-badge <?php echo esc_attr($log['severity']); ?>">
                                    <?php echo esc_html(ucfirst($log['severity'])); ?>
                                </span>
                            </td>
                            <td><?php echo esc_html($log['log_message']); ?></td>
                            <td>
                                <?php if (!empty($log['log_data'])): ?>
                                    <button class="button kura-ai-view-details" data-log-id="<?php echo $log['id']; ?>"
                                        data-log-data="<?php echo esc_attr(json_encode($log['log_data'])); ?>">
                                        <?php _e('View Details', 'kura-ai'); ?>
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <div class="kura-ai-logs-pagination">
            <?php
            $page_links = paginate_links(array(
                'base' => add_query_arg('paged', '%#%'),
                'format' => '',
                'prev_text' => __('&laquo;', 'kura-ai'),
                'next_text' => __('&raquo;', 'kura-ai'),
                'total' => $logs['total_pages'],
                'current' => $args['page']
            ));

            if ($page_links) {
                echo '<div class="tablenav"><div class="tablenav-pages">' . $page_links . '</div></div>';
            }
            ?>
        </div>
    </div>

    <div id="kura-ai-log-details-modal" class="kura-ai-modal" style="display: none;">
        <div class="kura-ai-modal-content">
            <div class="kura-ai-modal-header">
                <h3><?php _e('Log Details', 'kura-ai'); ?></h3>
                <span class="kura-ai-modal-close">&times;</span>
            </div>
            <div class="kura-ai-modal-body">
                <div id="kura-ai-log-details-content"></div>
            </div>
            <div class="kura-ai-modal-footer">
                <button class="button button-primary kura-ai-modal-close-btn"><?php _e('Close', 'kura-ai'); ?></button>
            </div>
        </div>
    </div>

    <div id="kura-ai-confirm-clear-modal" class="kura-ai-modal" style="display: none;">
        <div class="kura-ai-modal-content">
            <div class="kura-ai-modal-header">
                <h3><?php _e('Confirm Clear Logs', 'kura-ai'); ?></h3>
                <span class="kura-ai-modal-close">&times;</span>
            </div>
            <div class="kura-ai-modal-body">
                <p><?php _e('Are you sure you want to clear all logs? This action cannot be undone.', 'kura-ai'); ?></p>
            </div>
            <div class="kura-ai-modal-footer">
                <button id="kura-ai-confirm-clear"
                    class="button button-danger"><?php _e('Clear Logs', 'kura-ai'); ?></button>
                <button class="button kura-ai-modal-close-btn"><?php _e('Cancel', 'kura-ai'); ?></button>
            </div>
        </div>
    </div>
</div>