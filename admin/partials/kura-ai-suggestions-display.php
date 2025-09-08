<?php
$settings = get_option('kura_ai_settings');
$current_provider = !empty($settings['ai_service']) ? $settings['ai_service'] : '';

// Check if API keys are set for the current provider
global $wpdb;
$table_name = $wpdb->prefix . 'kura_ai_api_keys';
$api_key = $wpdb->get_var($wpdb->prepare(
    "SELECT api_key FROM $table_name WHERE provider = %s AND status = 'active' LIMIT 1",
    $current_provider
));
$has_api_key = !empty($api_key);
?>

<div class="wrap kura-ai-suggestions">
    <div class="kura-ai-header">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        <div class="kura-ai-version">v<?php echo KURA_AI_VERSION; ?></div>
    </div>

    <?php if (!$has_api_key): ?>
        <div class="notice notice-warning">
            <p>
                <?php _e('Please set up your API key for', 'kura-ai'); ?>
                <?php echo esc_html(ucfirst($current_provider)); ?>
                <?php _e('in the', 'kura-ai'); ?>
                <a href="<?php echo admin_url('admin.php?page=kura-ai-settings'); ?>"><?php _e('plugin settings', 'kura-ai'); ?></a>.
            </p>
        </div>
    <?php endif; ?>

    <div class="kura-ai-suggestions-grid">
        <!-- AI Request Card -->
        <div class="kura-ai-card kura-ai-request-card">
            <div class="kura-ai-card-header">
                <h2><?php _e('Request AI Security Assistance', 'kura-ai'); ?></h2>
            </div>
            <div class="kura-ai-card-body">
                <form id="kura-ai-suggestion-request">
                    <div class="kura-ai-form-group">
                        <label for="kura-ai-issue-type"><?php _e('Issue Type', 'kura-ai'); ?></label>
                        <select id="kura-ai-issue-type" name="issue_type" required>
                            <option value=""><?php _e('Select an issue type', 'kura-ai'); ?></option>
                            <option value="general"><?php _e('General Security Advice', 'kura-ai'); ?></option>
                            <option value="vulnerability"><?php _e('Specific Vulnerability', 'kura-ai'); ?></option>
                            <option value="performance"><?php _e('Performance Optimization', 'kura-ai'); ?></option>
                            <option value="hardening"><?php _e('Security Hardening', 'kura-ai'); ?></option>
                            <option value="other"><?php _e('Other', 'kura-ai'); ?></option>
                        </select>
                    </div>

                    <div class="kura-ai-form-group">
                        <label for="kura-ai-issue-description"><?php _e('Describe your issue or question', 'kura-ai'); ?></label>
                        <textarea id="kura-ai-issue-description" name="issue_description" rows="5" required></textarea>
                    </div>

                    <button type="submit" class="button button-primary" <?php echo $has_api_key ? '' : 'disabled'; ?>>
                        <?php _e('Get AI Suggestion', 'kura-ai'); ?>
                    </button>
                    
                    <?php if ($has_api_key): ?>
                        <div class="kura-ai-auth-status">
                            <span class="dashicons dashicons-yes-alt" style="color: #46b450;"></span>
                            <?php
                            printf(
                                __('Connected to %s via API', 'kura-ai'),
                                esc_html(ucfirst($current_provider))
                            );
                            ?>
                        </div>
                    <?php endif; ?>
                </form>
            </div>
        </div>

        <!-- AI Response Card -->
        <div class="kura-ai-card kura-ai-response-card" style="display: none;">
            <div class="kura-ai-card-header">
                <h2><?php _e('AI Response', 'kura-ai'); ?></h2>
            </div>
            <div class="kura-ai-card-body">
                <div id="kura-ai-suggestion-result"></div>
                <button id="kura-ai-new-request" class="button">
                    <?php _e('Ask Another Question', 'kura-ai'); ?>
                </button>
            </div>
        </div>
    </div>

    <?php if (!empty($settings['scan_results'])): ?>
        <!-- Scan Suggestions Card -->
        <div class="kura-ai-card kura-ai-scan-suggestions-card">
            <div class="kura-ai-card-header">
                <h2><?php _e('Suggestions for Recent Scan Issues', 'kura-ai'); ?></h2>
            </div>
            <div class="kura-ai-card-body">
                <div class="kura-ai-issue-list">
                    <?php
                    $issues_with_suggestions = 0;
                    foreach ($settings['scan_results'] as $category => $issues) {
                        foreach ($issues as $issue) {
                            if (!empty($issue['ai_suggestion'])) {
                                $issues_with_suggestions++;
                                ?>
                                <div class="kura-ai-issue-suggestion">
                                    <h4><?php echo esc_html($issue['message']); ?></h4>
                                    <p class="kura-ai-issue-severity">
                                        <strong><?php _e('Severity:', 'kura-ai'); ?></strong>
                                        <span class="kura-ai-severity-badge <?php echo esc_attr($issue['severity']); ?>">
                                            <?php echo esc_html(ucfirst($issue['severity'])); ?>
                                        </span>
                                    </p>
                                    <div class="kura-ai-suggestion-content">
                                         <?php echo esc_html($issue['ai_suggestion']); ?>
                                     </div>
                                </div>
                                <?php
                            }
                        }
                    }

                    if ($issues_with_suggestions === 0) {
                        echo '<p>' . __('No AI suggestions have been generated for recent scan issues.', 'kura-ai') . '</p>';
                    }
                    ?>
                </div>
            </div>
        </div>
    <?php else: ?>
        <!-- No Scan Results Card -->
        <div class="kura-ai-card kura-ai-no-scan-card">
            <div class="kura-ai-card-header">
                <h2><?php _e('Suggestions for Recent Scan Issues', 'kura-ai'); ?></h2>
            </div>
            <div class="kura-ai-card-body">
                <p><?php _e('No AI suggestions have been generated for recent scan issues.', 'kura-ai'); ?></p>
            </div>
        </div>
    <?php endif; ?>

    <!-- Loading Card -->
    <div id="kura-ai-suggestion-loading" class="kura-ai-card kura-ai-loading-card" style="display: none;">
        <div class="kura-ai-card-body">
            <div class="kura-ai-loading-spinner"></div>
            <p><?php _e('Generating AI suggestion...', 'kura-ai'); ?></p>
        </div>
    </div>
</div>