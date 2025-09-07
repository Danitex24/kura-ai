<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap kura-ai-analysis">
    <h1><?php echo function_exists('esc_html__') ? \esc_html__('AI Security Analysis', 'kura-ai') : 'AI Security Analysis'; ?></h1>

    <div class="kura-ai-analysis-form">
        <div class="kura-ai-form-group">
            <label for="kura-ai-code"><?php echo function_exists('esc_html__') ? \esc_html__('Code to Analyze:', 'kura-ai') : 'Code to Analyze:'; ?></label>
        <textarea id="kura-ai-code" rows="10" class="large-text code" placeholder="<?php echo function_exists('esc_attr__') ? \esc_attr__('Paste your code here...', 'kura-ai') : 'Paste your code here...'; ?>"></textarea>
        </div>

        <div class="kura-ai-form-group">
            <label for="kura-ai-context"><?php echo function_exists('esc_html__') ? \esc_html__('Additional Context:', 'kura-ai') : 'Additional Context:'; ?></label>
        <input type="text" id="kura-ai-context" class="regular-text" placeholder="<?php echo function_exists('esc_attr__') ? \esc_attr__('Optional context about the code...', 'kura-ai') : 'Optional context about the code...'; ?>" />
        </div>

        <div class="kura-ai-form-group">
            <button type="button" id="kura-ai-analyze" class="button button-primary">
                <span class="spinner"></span>
                <?php echo function_exists('esc_html__') ? \esc_html__('Analyze Code', 'kura-ai') : 'Analyze Code'; ?>
            </button>
        </div>
    </div>

    <div id="kura-ai-results" class="kura-ai-results" style="display: none;">
        <div class="kura-ai-results-header">
            <h2><?php echo function_exists('esc_html__') ? \esc_html__('Analysis Results', 'kura-ai') : 'Analysis Results'; ?></h2>
            <div class="kura-ai-results-meta">
                <span class="kura-ai-timestamp"></span>
            </div>
        </div>

        <div class="kura-ai-results-content"></div>

        <div class="kura-ai-feedback">
            <h3><?php echo function_exists('esc_html__') ? \esc_html__('Was this analysis helpful?', 'kura-ai') : 'Was this analysis helpful?'; ?></h3>
        <div class="kura-ai-feedback-actions">
            <button type="button" class="button kura-ai-feedback-btn" data-feedback="helpful">
                <span class="dashicons dashicons-yes"></span>
                <?php echo function_exists('esc_html__') ? \esc_html__('Yes', 'kura-ai') : 'Yes'; ?>
            </button>
            <button type="button" class="button kura-ai-feedback-btn" data-feedback="not_helpful">
                <span class="dashicons dashicons-no"></span>
                <?php echo function_exists('esc_html__') ? \esc_html__('No', 'kura-ai') : 'No'; ?>
            </button>
        </div>
        <div class="kura-ai-feedback-comment" style="display: none;">
            <label for="kura-ai-feedback-text"><?php echo function_exists('esc_html__') ? \esc_html__('Additional Comments:', 'kura-ai') : 'Additional Comments:'; ?></label>
            <textarea id="kura-ai-feedback-text" rows="3" class="large-text"></textarea>
            <button type="button" class="button button-primary kura-ai-submit-feedback">
                <?php echo function_exists('esc_html__') ? \esc_html__('Submit Feedback', 'kura-ai') : 'Submit Feedback'; ?>
            </button>
            </div>
        </div>
    </div>
</div>