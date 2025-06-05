<div class="wrap kura-ai-reports">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <?php
    $settings = get_option('kura_ai_settings');
    $scan_results = !empty($settings['scan_results']) ? $settings['scan_results'] : array();
    ?>
    
    <div class="kura-ai-scan-actions">
        <button id="kura-ai-run-scan" class="button button-primary">
            <?php _e('Run New Scan', 'kura-ai'); ?>
        </button>
    </div>
    
    <?php if (empty($scan_results)) : ?>
        <div class="kura-ai-no-results">
            <p><?php _e('No scan results available. Run a scan to check for vulnerabilities.', 'kura-ai'); ?></p>
        </div>
    <?php else : ?>
        <div class="kura-ai-results-summary">
            <h2><?php _e('Scan Summary', 'kura-ai'); ?></h2>
            
            <?php
            $issue_counts = array(
                'critical' => 0,
                'high' => 0,
                'medium' => 0,
                'low' => 0
            );
            
            foreach ($scan_results as $category => $issues) {
                foreach ($issues as $issue) {
                    $severity = $issue['severity'] ?? 'medium';
                    $issue_counts[$severity]++;
                }
            }
            
            $total_issues = array_sum($issue_counts);
            ?>
            
            <div class="kura-ai-issue-counts">
                <div class="kura-ai-issue-count critical">
                    <span class="count"><?php echo $issue_counts['critical']; ?></span>
                    <span class="label"><?php _e('Critical', 'kura-ai'); ?></span>
                </div>
                <div class="kura-ai-issue-count high">
                    <span class="count"><?php echo $issue_counts['high']; ?></span>
                    <span class="label"><?php _e('High', 'kura-ai'); ?></span>
                </div>
                <div class="kura-ai-issue-count medium">
                    <span class="count"><?php echo $issue_counts['medium']; ?></span>
                    <span class="label"><?php _e('Medium', 'kura-ai'); ?></span>
                </div>
                <div class="kura-ai-issue-count low">
                    <span class="count"><?php echo $issue_counts['low']; ?></span>
                    <span class="label"><?php _e('Low', 'kura-ai'); ?></span>
                </div>
            </div>
            
            <p class="kura-ai-scan-timestamp">
                <?php printf(__('Last scanned: %s', 'kura-ai'), date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $settings['last_scan'])); ?>
            </p>
        </div>
        
        <div class="kura-ai-results-details">
            <h2><?php _e('Detailed Results', 'kura-ai'); ?></h2>
            
            <?php foreach ($scan_results as $category => $issues) : ?>
                <?php if (!empty($issues)) : ?>
                    <div class="kura-ai-result-category">
                        <h3>
                            <?php 
                            switch ($category) {
                                case 'core_integrity':
                                    _e('Core Integrity', 'kura-ai');
                                    break;
                                case 'plugin_vulnerabilities':
                                    _e('Plugin Vulnerabilities', 'kura-ai');
                                    break;
                                case 'theme_vulnerabilities':
                                    _e('Theme Vulnerabilities', 'kura-ai');
                                    break;
                                case 'file_permissions':
                                    _e('File Permissions', 'kura-ai');
                                    break;
                                case 'sensitive_data':
                                    _e('Sensitive Data Exposure', 'kura-ai');
                                    break;
                                case 'malware':
                                    _e('Malware Detection', 'kura-ai');
                                    break;
                                case 'database_security':
                                    _e('Database Security', 'kura-ai');
                                    break;
                                case 'user_security':
                                    _e('User Security', 'kura-ai');
                                    break;
                                default:
                                    echo ucwords(str_replace('_', ' ', $category));
                            }
                            ?>
                            <span class="count">(<?php echo count($issues); ?>)</span>
                        </h3>
                        
                        <table class="wp-list-table widefat fixed striped">
                            <thead>
                                <tr>
                                    <th><?php _e('Issue', 'kura-ai'); ?></th>
                                    <th><?php _e('Severity', 'kura-ai'); ?></th>
                                    <th><?php _e('Suggested Fix', 'kura-ai'); ?></th>
                                    <th><?php _e('Actions', 'kura-ai'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($issues as $issue) : ?>
                                    <tr>
                                        <td><?php echo esc_html($issue['message']); ?></td>
                                        <td>
                                            <span class="kura-ai-severity-badge <?php echo esc_attr($issue['severity']); ?>">
                                                <?php echo esc_html(ucfirst($issue['severity'])); ?>
                                            </span>
                                        </td>
                                        <td><?php echo esc_html($issue['fix'] ?? __('No automatic fix available', 'kura-ai')); ?></td>
                                        <td>
                                            <?php if (!empty($issue['fix']) && !empty($issue['type'])) : ?>
                                                <button class="button kura-ai-apply-fix" 
                                                        data-issue-type="<?php echo esc_attr($issue['type']); ?>"
                                                        <?php if (!empty($issue['plugin'])) echo 'data-plugin="' . esc_attr($issue['plugin']) . '"'; ?>
                                                        <?php if (!empty($issue['theme'])) echo 'data-theme="' . esc_attr($issue['theme']) . '"'; ?>
                                                        <?php if (!empty($issue['file'])) echo 'data-file="' . esc_attr($issue['file']) . '"'; ?>>
                                                    <?php _e('Apply Fix', 'kura-ai'); ?>
                                                </button>
                                            <?php endif; ?>
                                            <button class="button kura-ai-get-suggestion" 
                                                    data-issue="<?php echo esc_attr(json_encode($issue)); ?>">
                                                <?php _e('AI Suggestion', 'kura-ai'); ?>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    
    <div class="kura-ai-scan-progress" style="display: none;">
        <div class="kura-ai-progress-bar">
            <div class="kura-ai-progress-bar-fill"></div>
        </div>
        <p class="kura-ai-progress-message"><?php _e('Preparing scan...', 'kura-ai'); ?></p>
    </div>
    
    <div id="kura-ai-suggestion-modal" class="kura-ai-modal" style="display: none;">
        <div class="kura-ai-modal-content">
            <div class="kura-ai-modal-header">
                <h3><?php _e('AI Security Suggestion', 'kura-ai'); ?></h3>
                <span class="kura-ai-modal-close">&times;</span>
            </div>
            <div class="kura-ai-modal-body">
                <div id="kura-ai-suggestion-content"></div>
            </div>
            <div class="kura-ai-modal-footer">
                <button class="button button-primary kura-ai-modal-close-btn"><?php _e('Close', 'kura-ai'); ?></button>
            </div>
        </div>
    </div>
</div>