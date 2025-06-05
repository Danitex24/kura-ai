<div class="wrap kura-ai-settings">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <form method="post" action="options.php">
        <?php
        settings_fields('kura_ai_settings_group');
        do_settings_sections('kura-ai-settings');
        submit_button();
        ?>
    </form>

    <div class="kura-ai-settings-advanced">
        <h2><?php _e('Advanced Tools', 'kura-ai'); ?></h2>

        <div class="kura-ai-advanced-tool">
            <h3><?php _e('Reset Plugin Settings', 'kura-ai'); ?></h3>
            <p><?php _e('This will reset all plugin settings to their default values.', 'kura-ai'); ?></p>
            <button id="kura-ai-reset-settings" class="button button-danger">
                <?php _e('Reset Settings', 'kura-ai'); ?>
            </button>
        </div>

        <div class="kura-ai-advanced-tool">
            <h3><?php _e('Debug Information', 'kura-ai'); ?></h3>
            <p><?php _e('View system information and debug data for troubleshooting.', 'kura-ai'); ?></p>
            <button id="kura-ai-view-debug" class="button">
                <?php _e('View Debug Info', 'kura-ai'); ?>
            </button>

            <div id="kura-ai-debug-info" style="display: none; margin-top: 20px;">
                <textarea readonly rows="10"
                    style="width: 100%; font-family: monospace;"><?php echo esc_textarea($this->get_debug_info()); ?></textarea>
                <button id="kura-ai-copy-debug" class="button" style="margin-top: 10px;">
                    <?php _e('Copy to Clipboard', 'kura-ai'); ?>
                </button>
            </div>
        </div>
    </div>

    <div id="kura-ai-confirm-reset-modal" class="kura-ai-modal" style="display: none;">
        <div class="kura-ai-modal-content">
            <div class="kura-ai-modal-header">
                <h3><?php _e('Confirm Reset Settings', 'kura-ai'); ?></h3>
                <span class="kura-ai-modal-close">&times;</span>
            </div>
            <div class="kura-ai-modal-body">
                <p><?php _e('Are you sure you want to reset all settings to default? This action cannot be undone.', 'kura-ai'); ?>
                </p>
            </div>
            <div class="kura-ai-modal-footer">
                <button id="kura-ai-confirm-reset"
                    class="button button-danger"><?php _e('Reset Settings', 'kura-ai'); ?></button>
                <button class="button kura-ai-modal-close-btn"><?php _e('Cancel', 'kura-ai'); ?></button>
            </div>
        </div>
    </div>
</div>