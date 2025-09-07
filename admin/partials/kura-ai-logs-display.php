<div class="wrap kura-ai-logs">
    <div class="kura-ai-header">
        <h1><?php echo function_exists('esc_html') && function_exists('get_admin_page_title') ? esc_html(get_admin_page_title()) : 'Activity Logs'; ?></h1>
        <div class="kura-ai-version">v<?php echo defined('KURA_AI_VERSION') ? KURA_AI_VERSION : '1.0.0'; ?></div>
    </div>

    <!-- Filters Card -->
    <div class="kura-ai-card">
        <div class="kura-ai-card-header">
            <h2><?php _e('Filter & Actions', 'kura-ai'); ?></h2>
        </div>
        <div class="kura-ai-card-body">
            <form id="kura-ai-logs-filter" method="get" class="kura-ai-logs-actions">
                <input type="hidden" name="page" value="kura-ai-logs">

                <div class="kura-ai-filter-group">
                    <label for="kura-ai-log-type"><?php _e('Log Type', 'kura-ai'); ?></label>
                    <select id="kura-ai-log-type" name="type">
                        <option value=""><?php echo function_exists('_e') ? _e('All Types', 'kura-ai') : 'All Types'; ?></option>
                        <option value="scan" <?php echo (!empty($_GET['type']) && $_GET['type'] === 'scan') ? 'selected' : ''; ?>>
                            <?php echo function_exists('_e') ? _e('Scans', 'kura-ai') : 'Scans'; ?></option>
                        <option value="fix_applied" <?php echo (!empty($_GET['type']) && $_GET['type'] === 'fix_applied') ? 'selected' : ''; ?>><?php echo function_exists('_e') ? _e('Applied Fixes', 'kura-ai') : 'Applied Fixes'; ?></option>
                        <option value="ai_suggestion" <?php echo (!empty($_GET['type']) && $_GET['type'] === 'ai_suggestion') ? 'selected' : ''; ?>><?php echo function_exists('_e') ? _e('AI Suggestions', 'kura-ai') : 'AI Suggestions'; ?></option>
                        <option value="export" <?php echo (!empty($_GET['type']) && $_GET['type'] === 'export') ? 'selected' : ''; ?>>
                            <?php echo function_exists('_e') ? _e('Exports', 'kura-ai') : 'Exports'; ?></option>
                    </select>
                </div>

                <div class="kura-ai-filter-group">
                    <label for="kura-ai-log-severity"><?php _e('Severity', 'kura-ai'); ?></label>
                    <select id="kura-ai-log-severity" name="severity">
                        <option value=""><?php echo function_exists('_e') ? _e('All Levels', 'kura-ai') : 'All Levels'; ?></option>
                        <option value="critical" <?php echo (!empty($_GET['severity']) && $_GET['severity'] === 'critical') ? 'selected' : ''; ?>><?php echo function_exists('_e') ? _e('Critical', 'kura-ai') : 'Critical'; ?></option>
                        <option value="high" <?php echo (!empty($_GET['severity']) && $_GET['severity'] === 'high') ? 'selected' : ''; ?>>
                            <?php echo function_exists('_e') ? _e('High', 'kura-ai') : 'High'; ?></option>
                        <option value="medium" <?php echo (!empty($_GET['severity']) && $_GET['severity'] === 'medium') ? 'selected' : ''; ?>><?php echo function_exists('_e') ? _e('Medium', 'kura-ai') : 'Medium'; ?></option>
                        <option value="low" <?php echo (!empty($_GET['severity']) && $_GET['severity'] === 'low') ? 'selected' : ''; ?>>
                            <?php echo function_exists('_e') ? _e('Low', 'kura-ai') : 'Low'; ?></option>
                        <option value="info" <?php echo (!empty($_GET['severity']) && $_GET['severity'] === 'info') ? 'selected' : ''; ?>>
                            <?php echo function_exists('_e') ? _e('Info', 'kura-ai') : 'Info'; ?></option>
                    </select>
                </div>

                <div class="kura-ai-filter-group">
                    <label for="kura-ai-log-search"><?php _e('Search', 'kura-ai'); ?></label>
                    <input type="text" id="kura-ai-log-search" name="search"
                        value="<?php echo function_exists('esc_attr') ? esc_attr(!empty($_GET['search']) ? $_GET['search'] : '') : htmlspecialchars(!empty($_GET['search']) ? $_GET['search'] : '', ENT_QUOTES); ?>">
                </div>

                <button type="submit" class="button button-primary"><?php echo function_exists('_e') ? _e('Filter', 'kura-ai') : 'Filter'; ?></button>
                <button type="button" id="kura-ai-export-logs" class="button">
                    <?php echo function_exists('_e') ? _e('Export to CSV', 'kura-ai') : 'Export to CSV'; ?>
                </button>
                <button type="button" id="kura-ai-clear-logs" class="button button-danger">
                    <?php echo function_exists('_e') ? _e('Clear Logs', 'kura-ai') : 'Clear Logs'; ?>
                </button>
            </form>
        </div>
    </div>

    <!-- Clearing log display  -->
    <div id="kura-ai-clear-message" class="notice" style="display: none;"></div>
    <div class="kura-ai-clear-loading" style="display: none;">
        <span class="spinner is-active"></span> Clearing logs...
    </div>
    <?php
    $logger = new \Kura_AI\Kura_AI_Logger($plugin_name, $version);

    $args = array(
        'type' => !empty($_GET['type']) ? (function_exists('sanitize_text_field') ? \sanitize_text_field($_GET['type']) : strip_tags($_GET['type'])) : '',
        'severity' => !empty($_GET['severity']) ? (function_exists('sanitize_text_field') ? \sanitize_text_field($_GET['severity']) : strip_tags($_GET['severity'])) : '',
        'search' => !empty($_GET['search']) ? (function_exists('sanitize_text_field') ? \sanitize_text_field($_GET['search']) : strip_tags($_GET['search'])) : '',
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
                    <th><?php echo function_exists('_e') ? _e('ID', 'kura-ai') : 'ID'; ?></th>
                    <th><?php echo function_exists('_e') ? _e('Date', 'kura-ai') : 'Date'; ?></th>
                    <th><?php echo function_exists('_e') ? _e('Type', 'kura-ai') : 'Type'; ?></th>
                    <th><?php echo function_exists('_e') ? _e('Severity', 'kura-ai') : 'Severity'; ?></th>
                    <th><?php echo function_exists('_e') ? _e('Message', 'kura-ai') : 'Message'; ?></th>
                    <th><?php echo function_exists('_e') ? _e('Details', 'kura-ai') : 'Details'; ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($logs['items'])): ?>
                    <tr>
                        <td colspan="6"><?php echo function_exists('_e') ? _e('No log entries found.', 'kura-ai') : 'No log entries found.'; ?></td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($logs['items'] as $log): ?>
                        <tr>
                            <td><?php echo $log['id']; ?></td>
                            <td><?php 
                                if (function_exists('date_i18n') && function_exists('get_option')) {
                                    echo date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($log['created_at']));
                                } else {
                                    echo date('Y-m-d H:i:s', strtotime($log['created_at']));
                                }
                            ?></td>
                            <td><?php echo function_exists('esc_html') ? esc_html(ucfirst($log['log_type'])) : htmlspecialchars(ucfirst($log['log_type']), ENT_QUOTES); ?></td>
                            <td>
                                <span class="kura-ai-severity-badge <?php echo function_exists('esc_attr') ? esc_attr($log['severity']) : htmlspecialchars($log['severity'], ENT_QUOTES); ?>">
                                    <?php echo function_exists('esc_html') ? esc_html(ucfirst($log['severity'])) : htmlspecialchars(ucfirst($log['severity']), ENT_QUOTES); ?>
                                </span>
                            </td>
                            <td><?php echo function_exists('esc_html') ? esc_html($log['log_message']) : htmlspecialchars($log['log_message'], ENT_QUOTES); ?></td>
                            <td>
                                <?php if (!empty($log['log_data'])): ?>
                                    <button class="button kura-ai-view-details" data-log-id="<?php echo $log['id']; ?>"
                                        data-log-data="<?php echo function_exists('esc_attr') ? esc_attr(json_encode($log['log_data'])) : htmlspecialchars(json_encode($log['log_data']), ENT_QUOTES); ?>">
                                        <?php echo function_exists('_e') ? _e('View Details', 'kura-ai') : 'View Details'; ?>
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
            if (function_exists('paginate_links') && function_exists('add_query_arg')) {
                $page_links = paginate_links(array(
                     'base' => add_query_arg('paged', '%#%'),
                     'format' => '',
                     'prev_text' => function_exists('__') ? __('&laquo;', 'kura-ai') : '&laquo;',
                     'next_text' => function_exists('__') ? __('&raquo;', 'kura-ai') : '&raquo;',
                     'total' => $logs['total_pages'],
                     'current' => $args['page']
                 ));
                
                if ($page_links) {
                    echo '<div class="tablenav"><div class="tablenav-pages">' . $page_links . '</div></div>';
                }
            } else {
                // Fallback pagination
                $current_page = $args['page'];
                $total_pages = $logs['total_pages'];
                
                if ($total_pages > 1) {
                    echo '<div class="tablenav"><div class="tablenav-pages">';
                    
                    $prev_page = max(1, $current_page - 1);
                    $next_page = min($total_pages, $current_page + 1);
                    
                    if ($current_page > 1) {
                        echo '<a href="?page=kura-ai-logs&paged=' . $prev_page . '" class="button">&laquo; Previous</a> ';
                    }
                    
                    echo '<span class="paging-input">';
                    echo '<span class="tablenav-paging-text">Page ' . $current_page . ' of ' . $total_pages . '</span>';
                    echo '</span>';
                    
                    if ($current_page < $total_pages) {
                        echo ' <a href="?page=kura-ai-logs&paged=' . $next_page . '" class="button">Next &raquo;</a>';
                    }
                    
                    echo '</div></div>';
                }
            }
            ?>
            </div>
        </div>
    </div>
</div>

    <div id="kura-ai-log-details-modal" class="kura-ai-modal" style="display: none;">
        <div class="kura-ai-modal-content">
            <div class="kura-ai-modal-header">
                <h3><?php echo function_exists('_e') ? _e('Log Details', 'kura-ai') : 'Log Details'; ?></h3>
                <span class="kura-ai-modal-close">&times;</span>
            </div>
            <div class="kura-ai-modal-body">
                <div id="kura-ai-log-details-content"></div>
            </div>
            <div class="kura-ai-modal-footer">
                <button class="button button-primary kura-ai-modal-close-btn"><?php echo function_exists('_e') ? _e('Close', 'kura-ai') : 'Close'; ?></button>
            </div>
        </div>
    </div>

    <div id="kura-ai-confirm-clear-modal" class="kura-ai-modal" style="display: none;">
        <div class="kura-ai-modal-content">
            <div class="kura-ai-modal-header">
                <h3><?php echo function_exists('_e') ? _e('Confirm Clear Logs', 'kura-ai') : 'Confirm Clear Logs'; ?></h3>
                <span class="kura-ai-modal-close">&times;</span>
            </div>
            <div class="kura-ai-modal-body">
                <p><?php echo function_exists('_e') ? _e('Are you sure you want to clear all logs? This action cannot be undone.', 'kura-ai') : 'Are you sure you want to clear all logs? This action cannot be undone.'; ?></p>
            </div>
            <div class="kura-ai-modal-footer">
                <button id="kura-ai-confirm-clear"
                    class="button button-danger"><?php echo function_exists('_e') ? _e('Clear Logs', 'kura-ai') : 'Clear Logs'; ?></button>
                <button class="button kura-ai-modal-close-btn"><?php echo function_exists('_e') ? _e('Cancel', 'kura-ai') : 'Cancel'; ?></button>
            </div>
        </div>
    </div>
</div>