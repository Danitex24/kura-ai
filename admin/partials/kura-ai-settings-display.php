<div class="wrap kura-ai-settings">
    <div class="kura-ai-header">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        <div class="kura-ai-version">v<?php echo KURA_AI_VERSION; ?></div>
    </div>

    <div class="kura-ai-settings-grid">
        <!-- AI Providers Card -->
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

                            <div class="api-key-section" style="display: none;">
                                <input type="text" id="<?php echo $provider; ?>-api-key" placeholder="Enter API Key" />
                                <button class="button button-primary save-api-key" data-provider="<?php echo $provider; ?>" data-nonce="<?php echo wp_create_nonce('save_api_key_action'); ?>">
                                    <span class="button-text"><?php _e('Save API Key', 'kura-ai'); ?></span>
                                    <span class="spinner" style="display: none;"></span>
                                </button>
                            </div>

                            <label>
                                <input type="checkbox" class="enable-provider" data-provider="<?php echo $provider; ?>" />
                                <?php _e('Enable', 'kura-ai'); ?>
                            </label>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="kura-ai-oauth-help">
                    <p><?php _e('Enable your preferred AI provider and enter your API key to get started.', 'kura-ai'); ?></p>
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
                <p><?php _e('Are you sure you want to reset all settings to default? This action cannot be undone.', 'kura-ai'); ?></p>
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

<script>
    jQuery(document).ready(function($) {
        // Handle provider enable/disable
        $('.enable-provider').on('change', function() {
            const provider = $(this).data('provider');
            const isChecked = $(this).is(':checked');
            $(this).closest('.kura-ai-provider').find('.api-key-section').toggle(isChecked);
        });

        // Handle save API key
        $('.save-api-key').on('click', function(e) {
            e.preventDefault();
            const $button = $(this);
            const provider = $button.data('provider');
            const apiKey = $(`#${provider}-api-key`).val();
            const nonce = $button.data('nonce');

            // Show loading state
            $button.prop('disabled', true);
            $button.find('.button-text').hide();
            $button.find('.spinner').show();

            $.post(kura_ai_ajax.ajax_url, {
                action: 'save_api_key',
                api_key: apiKey,
                provider: provider,
                _wpnonce: nonce
            }, function(response) {
                // Reset button state
                $button.prop('disabled', false);
                $button.find('.button-text').show();
                $button.find('.spinner').hide();

                if (response.success) {
                    Swal.fire({
                        title: 'Success!',
                        text: response.data.message || 'API Key saved successfully!',
                        icon: 'success',
                        confirmButtonText: 'OK',
                        customClass: {
                            confirmButton: 'button button-primary'
                        }
                    });
                } else {
                    Swal.fire({
                        title: 'Error!',
                        text: response.data.message || 'Failed to save API key.',
                        icon: 'error',
                        confirmButtonText: 'OK',
                        customClass: {
                            confirmButton: 'button button-primary'
                        }
                    });
                }
            }).fail(function() {
                // Reset button state
                $button.prop('disabled', false);
                $button.find('.button-text').show();
                $button.find('.spinner').hide();

                Swal.fire({
                    title: 'Error!',
                    text: 'Network error occurred while saving API key.',
                    icon: 'error',
                    confirmButtonText: 'OK',
                    customClass: {
                        confirmButton: 'button button-primary'
                    }
                });
            });
        });
    });
</script>