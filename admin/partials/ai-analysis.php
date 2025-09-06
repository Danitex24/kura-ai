<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap kura-ai-analysis">
    <h1><?php echo esc_html__('AI Security Analysis', 'kura-ai'); ?></h1>

    <div class="kura-ai-analysis-form">
        <div class="kura-ai-form-group">
            <label for="kura-ai-code"><?php echo esc_html__('Code to Analyze:', 'kura-ai'); ?></label>
            <textarea id="kura-ai-code" rows="10" class="large-text code" placeholder="<?php echo esc_attr__('Paste your code here...', 'kura-ai'); ?>"></textarea>
        </div>

        <div class="kura-ai-form-group">
            <label for="kura-ai-context"><?php echo esc_html__('Additional Context:', 'kura-ai'); ?></label>
            <input type="text" id="kura-ai-context" class="regular-text" placeholder="<?php echo esc_attr__('Optional context about the code...', 'kura-ai'); ?>" />
        </div>

        <div class="kura-ai-form-actions">
            <button type="button" id="kura-ai-analyze" class="button button-primary">
                <span class="spinner"></span>
                <?php echo esc_html__('Analyze Code', 'kura-ai'); ?>
            </button>
        </div>
    </div>

    <div id="kura-ai-results" class="kura-ai-results" style="display: none;">
        <div class="kura-ai-results-header">
            <h2><?php echo esc_html__('Analysis Results', 'kura-ai'); ?></h2>
            <div class="kura-ai-results-meta">
                <span class="kura-ai-timestamp"></span>
            </div>
        </div>

        <div class="kura-ai-results-content"></div>

        <div class="kura-ai-feedback">
            <h3><?php echo esc_html__('Was this analysis helpful?', 'kura-ai'); ?></h3>
            <div class="kura-ai-feedback-actions">
                <button type="button" class="button kura-ai-feedback-btn" data-feedback="helpful">
                    <span class="dashicons dashicons-yes"></span>
                    <?php echo esc_html__('Yes', 'kura-ai'); ?>
                </button>
                <button type="button" class="button kura-ai-feedback-btn" data-feedback="not_helpful">
                    <span class="dashicons dashicons-no"></span>
                    <?php echo esc_html__('No', 'kura-ai'); ?>
                </button>
            </div>
            <div class="kura-ai-feedback-comment" style="display: none;">
                <label for="kura-ai-feedback-text"><?php echo esc_html__('Additional Comments:', 'kura-ai'); ?></label>
                <textarea id="kura-ai-feedback-text" rows="3" class="large-text"></textarea>
                <button type="button" class="button button-primary kura-ai-submit-feedback">
                    <?php echo esc_html__('Submit Feedback', 'kura-ai'); ?>
                </button>
            </div>
        </div>
    </div>
</div>