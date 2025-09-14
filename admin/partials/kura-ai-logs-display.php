<div class="wrap kura-ai-logs">
    <div class="kura-ai-header">
        <h1><?php echo function_exists('esc_html') && function_exists('get_admin_page_title') ? esc_html(get_admin_page_title()) : 'Activity Logs'; ?></h1>
        <div class="kura-ai-version">v<?php echo defined('KURA_AI_VERSION') ? KURA_AI_VERSION : '1.0.0'; ?></div>
    </div>
    
    <!-- Tabs Navigation -->
    <div class="kura-ai-tabs">
        <nav class="nav-tab-wrapper">
            <?php 
            $active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'activity';
            
            // Allow other plugins to add tabs
            do_action('kura_ai_logs_tabs', $active_tab);
            ?>
        </nav>
    </div>

    <?php if ($active_tab == 'activity'): ?>
    <!-- Activity Logs Section -->
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

<?php endif; ?>

<?php if ($active_tab == 'audit'): ?>
<!-- Security Audit Logs Section -->
<div class="kura-ai-card">
    <div class="kura-ai-card-header">
        <h2><?php _e('Security Audit Log Filters', 'kura-ai'); ?></h2>
    </div>
    <div class="kura-ai-card-body">
        <form method="get" action="">
            <input type="hidden" name="page" value="kura-ai-logs">
            <input type="hidden" name="tab" value="audit">
            
            <div class="kura-ai-filter-row">
                <div class="kura-ai-filter-item">
                    <label for="event_type"><?php _e('Event Type', 'kura-ai'); ?></label>
                    <select name="event_type" id="event_type">
                        <option value=""><?php _e('All Types', 'kura-ai'); ?></option>
                        <option value="user" <?php echo isset($_GET['event_type']) && $_GET['event_type'] === 'user' ? 'selected' : ''; ?>>
                            <?php _e('User Events', 'kura-ai'); ?>
                        </option>
                        <option value="authentication" <?php echo isset($_GET['event_type']) && $_GET['event_type'] === 'authentication' ? 'selected' : ''; ?>>
                            <?php _e('Authentication', 'kura-ai'); ?>
                        </option>
                        <option value="plugin_theme" <?php echo isset($_GET['event_type']) && $_GET['event_type'] === 'plugin_theme' ? 'selected' : ''; ?>>
                            <?php _e('Plugin/Theme', 'kura-ai'); ?>
                        </option>
                        <option value="content" <?php echo isset($_GET['event_type']) && $_GET['event_type'] === 'content' ? 'selected' : ''; ?>>
                            <?php _e('Content', 'kura-ai'); ?>
                        </option>
                        <option value="settings" <?php echo isset($_GET['event_type']) && $_GET['event_type'] === 'settings' ? 'selected' : ''; ?>>
                            <?php _e('Settings', 'kura-ai'); ?>
                        </option>
                    </select>
                </div>
                
                <div class="kura-ai-filter-item">
                    <label for="severity"><?php _e('Severity', 'kura-ai'); ?></label>
                    <select name="severity" id="severity">
                        <option value=""><?php _e('All Severities', 'kura-ai'); ?></option>
                        <option value="critical" <?php echo isset($_GET['severity']) && $_GET['severity'] === 'critical' ? 'selected' : ''; ?>>
                            <?php _e('Critical', 'kura-ai'); ?>
                        </option>
                        <option value="high" <?php echo isset($_GET['severity']) && $_GET['severity'] === 'high' ? 'selected' : ''; ?>>
                            <?php _e('High', 'kura-ai'); ?>
                        </option>
                        <option value="medium" <?php echo isset($_GET['severity']) && $_GET['severity'] === 'medium' ? 'selected' : ''; ?>>
                            <?php _e('Medium', 'kura-ai'); ?>
                        </option>
                        <option value="low" <?php echo isset($_GET['severity']) && $_GET['severity'] === 'low' ? 'selected' : ''; ?>>
                            <?php _e('Low', 'kura-ai'); ?>
                        </option>
                        <option value="info" <?php echo isset($_GET['severity']) && $_GET['severity'] === 'info' ? 'selected' : ''; ?>>
                            <?php _e('Info', 'kura-ai'); ?>
                        </option>
                    </select>
                </div>
                
                <div class="kura-ai-filter-item">
                    <label for="user_id"><?php _e('User', 'kura-ai'); ?></label>
                    <select name="user_id" id="user_id">
                        <option value=""><?php _e('All Users', 'kura-ai'); ?></option>
                        <?php 
                        // Get all users with edit_posts capability
                        $users = get_users(array('capability' => 'edit_posts'));
                        foreach ($users as $user) {
                            $selected = isset($_GET['user_id']) && $_GET['user_id'] == $user->ID ? 'selected' : '';
                            echo '<option value="' . esc_attr($user->ID) . '" ' . $selected . '>' . esc_html($user->display_name) . '</option>';
                        }
                        ?>
                        <option value="system" <?php echo isset($_GET['user_id']) && $_GET['user_id'] === 'system' ? 'selected' : ''; ?>>
                            <?php _e('System', 'kura-ai'); ?>
                        </option>
                    </select>
                </div>
                
                <div class="kura-ai-filter-item">
                    <label for="date_range"><?php _e('Date Range', 'kura-ai'); ?></label>
                    <select name="date_range" id="date_range">
                        <option value=""><?php _e('All Time', 'kura-ai'); ?></option>
                        <option value="today" <?php echo isset($_GET['date_range']) && $_GET['date_range'] === 'today' ? 'selected' : ''; ?>>
                            <?php _e('Today', 'kura-ai'); ?>
                        </option>
                        <option value="yesterday" <?php echo isset($_GET['date_range']) && $_GET['date_range'] === 'yesterday' ? 'selected' : ''; ?>>
                            <?php _e('Yesterday', 'kura-ai'); ?>
                        </option>
                        <option value="week" <?php echo isset($_GET['date_range']) && $_GET['date_range'] === 'week' ? 'selected' : ''; ?>>
                            <?php _e('Last 7 Days', 'kura-ai'); ?>
                        </option>
                        <option value="month" <?php echo isset($_GET['date_range']) && $_GET['date_range'] === 'month' ? 'selected' : ''; ?>>
                            <?php _e('Last 30 Days', 'kura-ai'); ?>
                        </option>
                    </select>
                </div>
                
                <div class="kura-ai-filter-item">
                    <label for="search"><?php _e('Search', 'kura-ai'); ?></label>
                    <input type="text" name="search" id="search" value="<?php echo isset($_GET['search']) ? esc_attr($_GET['search']) : ''; ?>" placeholder="<?php _e('Search logs...', 'kura-ai'); ?>">
                </div>
            </div>
            
            <div class="kura-ai-filter-actions">
                <button type="submit" class="button button-primary"><?php _e('Apply Filters', 'kura-ai'); ?></button>
                <a href="?page=kura-ai-logs&tab=audit" class="button"><?php _e('Reset Filters', 'kura-ai'); ?></a>
                
                <div class="kura-ai-export-buttons">
                    <a href="<?php echo esc_url(add_query_arg('export', 'csv')); ?>" class="button">
                        <span class="dashicons dashicons-media-spreadsheet"></span> <?php _e('Export CSV', 'kura-ai'); ?>
                    </a>
                    <a href="<?php echo esc_url(add_query_arg('export', 'json')); ?>" class="button">
                        <span class="dashicons dashicons-media-code"></span> <?php _e('Export JSON', 'kura-ai'); ?>
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Audit Logs Table Card -->
<div class="kura-ai-card">
    <div class="kura-ai-card-header">
        <h2><?php _e('Security Audit Logs', 'kura-ai'); ?></h2>
        <div class="kura-ai-card-actions">
            <?php if (current_user_can('manage_options')): ?>
            <button type="button" class="button kura-ai-clear-logs-btn" data-log-type="audit">
                <span class="dashicons dashicons-trash"></span> <?php _e('Clear Logs', 'kura-ai'); ?>
            </button>
            <?php endif; ?>
        </div>
    </div>
    <div class="kura-ai-card-body">
        <?php
        // Get audit logs from the database
        if (class_exists('Kura_AI_Audit_Log')) {
            $audit_log = new Kura_AI_Audit_Log(KURA_AI_PLUGIN_NAME, KURA_AI_VERSION);
            
            // Prepare filter arguments
            $args = array(
                'per_page' => 20,
                'page' => isset($_GET['paged']) ? intval($_GET['paged']) : 1,
            );
            
            // Add filters if set
            if (!empty($_GET['event_type'])) {
                $args['event_type'] = sanitize_text_field($_GET['event_type']);
            }
            
            if (!empty($_GET['severity'])) {
                $args['severity'] = sanitize_text_field($_GET['severity']);
            }
            
            if (!empty($_GET['user_id'])) {
                $args['user_id'] = sanitize_text_field($_GET['user_id']);
            }
            
            if (!empty($_GET['search'])) {
                $args['search'] = sanitize_text_field($_GET['search']);
            }
            
            if (!empty($_GET['date_range'])) {
                $args['date_range'] = sanitize_text_field($_GET['date_range']);
            }
            
            // Get logs
            $logs = $audit_log->get_logs($args);
            $total_logs = $audit_log->get_logs_count($args);
            $total_pages = ceil($total_logs / $args['per_page']);
            
            if (!empty($logs)) :
        ?>
        <table class="kura-ai-logs-table">
            <thead>
                <tr>
                    <th><?php _e('ID', 'kura-ai'); ?></th>
                    <th><?php _e('Date', 'kura-ai'); ?></th>
                    <th><?php _e('User', 'kura-ai'); ?></th>
                    <th><?php _e('Event Type', 'kura-ai'); ?></th>
                    <th><?php _e('Severity', 'kura-ai'); ?></th>
                    <th><?php _e('Summary', 'kura-ai'); ?></th>
                    <th><?php _e('Actions', 'kura-ai'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($logs as $log): ?>
                <tr>
                    <td><?php echo esc_html($log->id); ?></td>
                    <td><?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($log->created_at))); ?></td>
                    <td>
                        <?php 
                        if ($log->user_id === 'system') {
                            echo '<span class="kura-ai-system-user">System</span>';
                        } else {
                            $user = get_userdata($log->user_id);
                            echo $user ? esc_html($user->display_name) : __('Unknown User', 'kura-ai');
                        }
                        ?>
                    </td>
                    <td>
                        <?php 
                        $event_type_labels = array(
                            'user' => __('User', 'kura-ai'),
                            'authentication' => __('Authentication', 'kura-ai'),
                            'plugin_theme' => __('Plugin/Theme', 'kura-ai'),
                            'content' => __('Content', 'kura-ai'),
                            'settings' => __('Settings', 'kura-ai'),
                        );
                        echo isset($event_type_labels[$log->event_type]) ? esc_html($event_type_labels[$log->event_type]) : esc_html($log->event_type);
                        ?>
                    </td>
                    <td>
                        <span class="kura-ai-severity kura-ai-severity-<?php echo esc_attr($log->severity); ?>">
                            <?php echo esc_html(ucfirst($log->severity)); ?>
                        </span>
                    </td>
                    <td><?php echo esc_html($log->summary); ?></td>
                    <td>
                        <button type="button" class="button button-small kura-ai-view-log-details" data-log-id="<?php echo esc_attr($log->id); ?>" data-log-type="audit">
                            <?php _e('View Details', 'kura-ai'); ?>
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <?php if ($total_pages > 1): ?>
        <div class="kura-ai-pagination">
            <?php
            $current_page = isset($_GET['paged']) ? intval($_GET['paged']) : 1;
            
            // Remove paged from current URL if it exists
            $current_url = remove_query_arg('paged', $_SERVER['REQUEST_URI']);
            
            // First page
            if ($current_page > 1) {
                echo '<a href="' . esc_url(add_query_arg('paged', 1, $current_url)) . '" class="button">&laquo;</a>';
            }
            
            // Previous page
            if ($current_page > 1) {
                echo '<a href="' . esc_url(add_query_arg('paged', $current_page - 1, $current_url)) . '" class="button">&lsaquo;</a>';
            }
            
            // Page numbers
            $start_page = max(1, $current_page - 2);
            $end_page = min($total_pages, $current_page + 2);
            
            for ($i = $start_page; $i <= $end_page; $i++) {
                $active_class = $i === $current_page ? 'button-primary' : '';
                echo '<a href="' . esc_url(add_query_arg('paged', $i, $current_url)) . '" class="button ' . $active_class . '">' . $i . '</a>';
            }
            
            // Next page
            if ($current_page < $total_pages) {
                echo '<a href="' . esc_url(add_query_arg('paged', $current_page + 1, $current_url)) . '" class="button">&rsaquo;</a>';
            }
            
            // Last page
            if ($current_page < $total_pages) {
                echo '<a href="' . esc_url(add_query_arg('paged', $total_pages, $current_url)) . '" class="button">&raquo;</a>';
            }
            ?>
        </div>
        <?php endif; ?>
        
        <?php else: ?>
        <div class="kura-ai-no-logs">
            <p><?php _e('No audit logs found.', 'kura-ai'); ?></p>
        </div>
        <?php 
            endif;
        } else {
            echo '<div class="kura-ai-no-logs"><p>' . __('Audit Log class not found.', 'kura-ai') . '</p></div>';
        }
        ?>
    </div>
</div>
<?php endif; ?>

<?php if ($active_tab == 'audit-settings'): ?>
<!-- Audit Settings Section -->
<div class="kura-ai-card">
    <div class="kura-ai-card-header">
        <h2><?php _e('Security Audit Log Settings', 'kura-ai'); ?></h2>
    </div>
    <div class="kura-ai-card-body">
        <form method="post" action="options.php" class="kura-ai-settings-form">
            <?php 
            settings_fields('kura_ai_audit_log_settings');
            do_settings_sections('kura_ai_audit_log_settings');
            
            // Get saved options with defaults
            $options = get_option('kura_ai_audit_log_settings', array(
                'log_scope' => 'all',
                'email_frequency' => 'weekly',
                'email_recipients' => get_option('admin_email'),
                'retention_days' => 90,
                'append_only' => 'yes'
            ));
            ?>
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="log_scope"><?php _e('Logging Scope', 'kura-ai'); ?></label>
                    </th>
                    <td>
                        <select name="kura_ai_audit_log_settings[log_scope]" id="log_scope">
                            <option value="all" <?php selected($options['log_scope'], 'all'); ?>>
                                <?php _e('All Events', 'kura-ai'); ?>
                            </option>
                            <option value="significant" <?php selected($options['log_scope'], 'significant'); ?>>
                                <?php _e('Significant Events Only', 'kura-ai'); ?>
                            </option>
                        </select>
                        <p class="description">
                            <?php _e('Choose which events to log. Significant events include authentication, configuration changes, and plugin/theme modifications.', 'kura-ai'); ?>
                        </p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="email_frequency"><?php _e('Email Report Frequency', 'kura-ai'); ?></label>
                    </th>
                    <td>
                        <select name="kura_ai_audit_log_settings[email_frequency]" id="email_frequency">
                            <option value="daily" <?php selected($options['email_frequency'], 'daily'); ?>>
                                <?php _e('Daily', 'kura-ai'); ?>
                            </option>
                            <option value="weekly" <?php selected($options['email_frequency'], 'weekly'); ?>>
                                <?php _e('Weekly', 'kura-ai'); ?>
                            </option>
                            <option value="monthly" <?php selected($options['email_frequency'], 'monthly'); ?>>
                                <?php _e('Monthly', 'kura-ai'); ?>
                            </option>
                            <option value="off" <?php selected($options['email_frequency'], 'off'); ?>>
                                <?php _e('Off', 'kura-ai'); ?>
                            </option>
                        </select>
                        <p class="description">
                            <?php _e('How often to send audit log reports via email.', 'kura-ai'); ?>
                        </p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="email_recipients"><?php _e('Email Recipients', 'kura-ai'); ?></label>
                    </th>
                    <td>
                        <input type="text" name="kura_ai_audit_log_settings[email_recipients]" id="email_recipients" 
                               value="<?php echo esc_attr($options['email_recipients']); ?>" class="regular-text">
                        <p class="description">
                            <?php _e('Comma-separated list of email addresses to receive audit log reports.', 'kura-ai'); ?>
                        </p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="retention_days"><?php _e('Retention Period (Days)', 'kura-ai'); ?></label>
                    </th>
                    <td>
                        <input type="number" name="kura_ai_audit_log_settings[retention_days]" id="retention_days" 
                               value="<?php echo esc_attr($options['retention_days']); ?>" min="1" max="365" class="small-text">
                        <p class="description">
                            <?php _e('Number of days to keep audit logs before automatic deletion. Set to 0 for indefinite retention.', 'kura-ai'); ?>
                        </p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="append_only"><?php _e('Append-Only Mode', 'kura-ai'); ?></label>
                    </th>
                    <td>
                        <select name="kura_ai_audit_log_settings[append_only]" id="append_only">
                            <option value="yes" <?php selected($options['append_only'], 'yes'); ?>>
                                <?php _e('Enabled (Recommended)', 'kura-ai'); ?>
                            </option>
                            <option value="no" <?php selected($options['append_only'], 'no'); ?>>
                                <?php _e('Disabled', 'kura-ai'); ?>
                            </option>
                        </select>
                        <p class="description">
                            <?php _e('<strong>Warning:</strong> Disabling append-only mode allows silent deletion of logs. Only disable for compatibility issues.', 'kura-ai'); ?>
                        </p>
                    </td>
                </tr>
            </table>
            
            <?php submit_button(__('Save Settings', 'kura-ai')); ?>
        </form>
    </div>
</div>
<?php endif; ?>

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