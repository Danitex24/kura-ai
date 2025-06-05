<div class="wrap kura-ai-suggestions">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <?php
    $settings = get_option('kura_ai_settings');
    $ai_enabled = !empty($settings['enable_ai']) && !empty($settings['api_key']);
    ?>

    <?php if (!$ai_enabled): ?>
        <div class="kura-ai-ai-disabled">
            <div class="notice notice-warning">
                <p>
                    <?php _e('AI suggestions are currently disabled. Please enable AI and provide an API key in the', 'kura-ai'); ?>
                    <a
                        href="<?php echo admin_url('admin.php?page=kura-ai-settings'); ?>"><?php _e('plugin settings', 'kura-ai'); ?></a>.
                </p>
            </div>
        </div>
    <?php endif; ?>

    <div class="kura-ai-suggestions-container">
        <div class="kura-ai-suggestions-form">
            <h2><?php _e('Request AI Security Assistance', 'kura-ai'); ?></h2>

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
                    <label
                        for="kura-ai-issue-description"><?php _e('Describe your issue or question', 'kura-ai'); ?></label>
                    <textarea id="kura-ai-issue-description" name="issue_description" rows="5" required></textarea>
                </div>

                <button type="submit" class="button button-primary" <?php echo $ai_enabled ? '' : 'disabled'; ?>>
                    <?php _e('Get AI Suggestion', 'kura-ai'); ?>
                </button>
            </form>
        </div>

        <div class="kura-ai-suggestions-results" style="display: none;">
            <h2><?php _e('AI Response', 'kura-ai'); ?></h2>
            <div id="kura-ai-suggestion-result"></div>
            <button id="kura-ai-new-request" class="button">
                <?php _e('Ask Another Question', 'kura-ai'); ?>
            </button>
        </div>
    </div>

    <?php if (!empty($settings['scan_results'])): ?>
        <div class="kura-ai-scan-suggestions">
            <h2><?php _e('Suggestions for Recent Scan Issues', 'kura-ai'); ?></h2>

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
                                    <?php echo wp_kses_post(wpautop($issue['ai_suggestion'])); ?>
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
    <?php endif; ?>

    <div id="kura-ai-suggestion-loading" style="display: none;">
        <div class="kura-ai-loading-spinner"></div>
        <p><?php _e('Generating AI suggestion...', 'kura-ai'); ?></p>
    </div>
</div>