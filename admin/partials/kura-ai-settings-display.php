<div class="wrap kura-ai-settings">
    <div class="kura-ai-header">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        <div class="kura-ai-version">v<?php echo KURA_AI_VERSION; ?></div>
    </div>

    <?php if (isset($_GET['oauth_error'])): ?>
        <div class="notice notice-error">
            <p><?php _e('OAuth connection failed:', 'kura-ai'); ?>     <?php echo esc_html($_GET['oauth_error']); ?></p>
        </div>
    <?php elseif (isset($_GET['oauth_success'])): ?>
        <div class="notice notice-success">
            <p><?php _e('Successfully connected to provider!', 'kura-ai'); ?></p>
        </div>
    <?php endif; ?>

    <div class="kura-ai-settings-grid">
        <!-- Main Settings Card -->
        <div class="kura-ai-card kura-ai-main-settings">
            <div class="kura-ai-card-header">
                <h2><?php _e('General Settings', 'kura-ai'); ?></h2>
            </div>
            <div class="kura-ai-card-body">
                <form method="post" action="options.php">
                    <?php
                    settings_fields('kura_ai_settings_group');
                    do_settings_sections('kura-ai-settings');
                    submit_button(__('Save Settings', 'kura-ai'), 'primary large');
                    ?>
                </form>
            </div>
        </div>

        <!-- OAuth Providers Card - Updated -->

<div class="kura-ai-card kura-ai-oauth-card">
    <div class="kura-ai-card-header">
        <h2><?php _e('AI Provider Connections', 'kura-ai'); ?></h2>
    </div>
    <div class="kura-ai-card-body">
        <div class="kura-ai-providers-grid">
            <?php foreach (['openai', 'gemini'] as $provider): ?>
                <div class="kura-ai-provider">
                    <div class="provider-logo <?php echo $provider; ?>-logo"
                        style="background-image: url('<?php echo esc_url(KURA_AI_PLUGIN_URL . 'assets/images/' . $provider . '.png'); ?>')">
                    </div>
                    <h3><?php echo ucfirst($provider); ?></h3>

                    <?php if (empty($settings['ai_oauth_tokens'][$provider])): ?>
                        <button class="button button-primary kura-ai-oauth-connect" data-provider="<?php echo $provider; ?>"
                            data-nonce="<?php echo wp_create_nonce('kura_ai_oauth_init'); ?>">
                            <?php _e('Connect Account', 'kura-ai'); ?>
                        </button>
                    <?php else: ?>
                        <div class="connection-status connected">
                            <span class="status-icon">✓</span>
                            <?php _e('Connected', 'kura-ai'); ?>
                        </div>
                        <button class="button button-disconnect kura-ai-oauth-disconnect"
                            data-provider="<?php echo $provider; ?>"
                            data-nonce="<?php echo wp_create_nonce('kura_ai_oauth_disconnect'); ?>">
                            <?php _e('Disconnect', 'kura-ai'); ?>
                        </button>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="kura-ai-oauth-help">
            <p><?php _e('Click "Connect" to authenticate with your AI provider account.', 'kura-ai'); ?></p>
        </div>
    </div>
</div>
        <!-- Advanced Tools Card -->
        <div class="kura-ai-card kura-ai-tools-card">
            <div class="kura-ai-card-header">
                <h2><?php _e('Advanced Tools', 'kura-ai'); ?></h2>
            </div>
            <div class="kura-ai-card-body">
                <div class="kura-ai-tools-grid">
                    <!-- Reset Settings -->
                    <div class="kura-ai-tool">
                        <div class="tool-icon reset-icon"></div>
                        <h3><?php _e('Reset Settings', 'kura-ai'); ?></h3>
                        <p><?php _e('Restore all plugin settings to their default values.', 'kura-ai'); ?></p>
                        <button id="kura-ai-reset-settings" class="button button-danger">
                            <?php _e('Reset Now', 'kura-ai'); ?>
                        </button>
                    </div>

                    <!-- Debug Info -->
                    <div class="kura-ai-tool">
                        <div class="tool-icon debug-icon"></div>
                        <h3><?php _e('Debug Information', 'kura-ai'); ?></h3>
                        <p><?php _e('View system information for troubleshooting.', 'kura-ai'); ?></p>
                        <button id="kura-ai-view-debug" class="button">
                            <?php _e('View Debug Info', 'kura-ai'); ?>
                        </button>
                    </div>
                </div>

                <!-- Debug Info Panel (hidden by default) -->
                <div id="kura-ai-debug-info" class="kura-ai-debug-panel" style="display: none;">
                    <div class="debug-header">
                        <h4><?php _e('System Debug Information', 'kura-ai'); ?></h4>
                        <button id="kura-ai-copy-debug" class="button button-secondary">
                            <?php _e('Copy to Clipboard', 'kura-ai'); ?>
                        </button>
                    </div>
                    <textarea readonly><?php
                    if (method_exists($this, 'get_debug_info')) {
                        echo esc_textarea($this->get_debug_info());
                    } else {
                        echo esc_textarea(__('Debug information is currently unavailable.', 'kura-ai'));
                    }
                    ?></textarea>
                </div>
            </div>
        </div>
    </div>

    <!-- Reset Confirmation Modal -->
    <div id="kura-ai-confirm-reset-modal" class="kura-ai-modal">
        <div class="kura-ai-modal-overlay"></div>
        <div class="kura-ai-modal-dialog">
            <div class="kura-ai-modal-header">
                <h3><?php _e('Confirm Reset', 'kura-ai'); ?></h3>
                <button class="kura-ai-modal-close">&times;</button>
            </div>
            <div class="kura-ai-modal-body">
                <div class="modal-warning-icon">⚠️</div>
                <p><?php _e('Are you sure you want to reset all settings to default? This action cannot be undone.', 'kura-ai'); ?>
                </p>
            </div>
            <div class="kura-ai-modal-footer">
                <button id="kura-ai-confirm-reset" class="button button-danger">
                    <?php _e('Reset Settings', 'kura-ai'); ?>
                </button>
                <button class="button kura-ai-modal-close-btn">
                    <?php _e('Cancel', 'kura-ai'); ?>
                </button>
            </div>
        </div>
    </div>
</div>