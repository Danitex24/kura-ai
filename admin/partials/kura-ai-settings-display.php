<?php
// WordPress function stubs for static analysis (these won't execute in real WordPress environment)
if (!function_exists('esc_html')) {
    function esc_html($text) { return htmlspecialchars($text, ENT_QUOTES, 'UTF-8'); }
}
if (!function_exists('get_admin_page_title')) {
    function get_admin_page_title() { return 'Kura AI Settings'; }
}
if (!function_exists('_e')) {
    function _e($text, $domain = 'default') { echo $text; }
}
if (!function_exists('esc_url')) {
    function esc_url($url) { return $url; }
}
if (!function_exists('wp_create_nonce')) {
    function wp_create_nonce($action = -1) { return 'nonce'; }
}
if (!function_exists('esc_textarea')) {
    function esc_textarea($text) { return htmlspecialchars($text, ENT_QUOTES, 'UTF-8'); }
}
if (!function_exists('__')) {
    function __($text, $domain = 'default') { return $text; }
}
if (!function_exists('get_option')) {
    function get_option($option, $default = false) { return $default; }
}
if (!function_exists('esc_attr')) {
    function esc_attr($text) { return htmlspecialchars($text, ENT_QUOTES, 'UTF-8'); }
}
?>
<div class="wrap kura-ai-settings">
    <div class="kura-ai-header">
        <h1><?php echo \esc_html(\get_admin_page_title()); ?></h1>
        <div class="kura-ai-version">v<?php echo KURA_AI_VERSION; ?></div>
    </div>

    <div class="kura-ai-settings-grid">
        <!-- AI Providers Card -->
        <div class="kura-ai-card kura-ai-oauth-card">
            <div class="kura-ai-card-header">
                <h2><?php \_e('AI Provider Connections', 'kura-ai'); ?></h2>
            </div>
            <div class="kura-ai-card-body">
                <div class="kura-ai-providers-grid">
                    <?php 
                    global $wpdb;
                    $api_keys_table = $wpdb->prefix . 'kura_ai_api_keys';
                    $settings = get_option('kura_ai_settings', array());
                    $current_provider = !empty($settings['ai_service']) ? $settings['ai_service'] : '';
                    
                    foreach (['openai', 'gemini'] as $provider): 
                        // Get existing API key for this provider
                        $existing_key = $wpdb->get_var($wpdb->prepare(
                            "SELECT api_key FROM $api_keys_table WHERE provider = %s AND status = %s",
                            $provider,
                            'active'
                        ));
                        $has_key = !empty($existing_key);
                        $display_key = $has_key ? str_repeat('*', 20) . substr($existing_key, -4) : '';
                        $is_selected = ($current_provider === $provider);
                    ?>
                        <div class="kura-ai-provider">
                            <div class="provider-logo <?php echo $provider; ?>-logo"
                                style="background-image: url('<?php echo \esc_url(KURA_AI_PLUGIN_URL . 'assets/images/' . $provider . '.png'); ?>')">"
    },
    {
      "old_str": 
                            </div>
                            <h3><?php echo ucfirst($provider); ?></h3>

                            <div class="api-key-section" style="display: <?php echo $has_key ? 'block' : 'none'; ?>;">
                                <input type="text" id="<?php echo $provider; ?>-api-key" placeholder="Enter API Key" value="<?php echo esc_attr($display_key); ?>" />
                                <button class="button button-primary save-api-key" data-provider="<?php echo $provider; ?>" data-nonce="<?php echo wp_create_nonce('kura_ai_nonce'); ?>">
                                    <span class="button-text"><?php \_e($has_key ? 'Update API Key' : 'Save API Key', 'kura-ai'); ?></span>
                                    <span class="spinner" style="display: none;"></span>
                                </button>
                            </div>

                            <label>
                                <input type="checkbox" class="enable-provider" data-provider="<?php echo $provider; ?>" <?php echo $is_selected ? 'checked' : ''; ?> />
                                <?php \_e('Enable', 'kura-ai'); ?>
                            </label>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="kura-ai-oauth-help">
                    <p><?php \_e('Enable your preferred AI provider and enter your API key to get started.', 'kura-ai'); ?></p>
                </div>
            </div>
        </div>

        <!-- Chatbot Settings Card -->
        <div class="kura-ai-card kura-ai-chatbot-card">
            <div class="kura-ai-card-header">
                <h2><?php \_e('Chatbot Settings', 'kura-ai'); ?></h2>
            </div>
            <div class="kura-ai-card-body">
                <div class="kura-ai-chatbot-settings">
                    <div class="kura-ai-setting-row">
                        <div class="setting-info">
                            <h3><?php \_e('Enable AI Chatbot', 'kura-ai'); ?></h3>
                            <p><?php \_e('Allow visitors to chat with AI about your website and plugin features.', 'kura-ai'); ?></p>
                        </div>
                        <div class="setting-control">
                            <?php 
                            $chatbot_enabled = get_option('kura_ai_chatbot_enabled', false);
                            ?>
                            <label class="kura-ai-toggle">
                                <input type="checkbox" id="kura-ai-chatbot-enabled" <?php echo $chatbot_enabled ? 'checked' : ''; ?> />
                                <span class="slider"></span>
                            </label>
                        </div>
                    </div>
                    
                    <div class="kura-ai-setting-row">
                        <div class="setting-info">
                            <h3><?php \_e('Chatbot Position', 'kura-ai'); ?></h3>
                            <p><?php \_e('Choose where the chatbot appears on your website.', 'kura-ai'); ?></p>
                        </div>
                        <div class="setting-control">
                            <?php 
                            $chatbot_position = get_option('kura_ai_chatbot_position', 'bottom-right');
                            ?>
                            <select id="kura-ai-chatbot-position">
                                <option value="bottom-right" <?php echo $chatbot_position === 'bottom-right' ? 'selected' : ''; ?>><?php \_e('Bottom Right', 'kura-ai'); ?></option>
                                <option value="bottom-left" <?php echo $chatbot_position === 'bottom-left' ? 'selected' : ''; ?>><?php \_e('Bottom Left', 'kura-ai'); ?></option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Advanced Tools Card -->
        <div class="kura-ai-card kura-ai-tools-card">
            <div class="kura-ai-card-header">
                <h2><?php \_e('Advanced Tools', 'kura-ai'); ?></h2>
            </div>
            <div class="kura-ai-card-body">
                <div class="kura-ai-tools-grid">
                    <!-- Reset Settings -->
                    <div class="kura-ai-tool">
                        <div class="tool-icon reset-icon"></div>
                        <h3><?php \_e('Reset Settings', 'kura-ai'); ?></h3>
                        <p><?php \_e('Restore all plugin settings to their default values.', 'kura-ai'); ?></p>
                        <button id="kura-ai-reset-settings" class="button button-danger">
                            <?php \_e('Reset Now', 'kura-ai'); ?>
                        </button>
                    </div>

                    <!-- Debug Info -->
                    <div class="kura-ai-tool">
                        <div class="tool-icon debug-icon"></div>
                        <h3><?php \_e('Debug Information', 'kura-ai'); ?></h3>
                        <p><?php \_e('View system information for troubleshooting.', 'kura-ai'); ?></p>
                        <button id="kura-ai-view-debug" class="button">
                            <?php \_e('View Debug Info', 'kura-ai'); ?>
                        </button>
                    </div>
                </div>

                <!-- Debug Info Panel (hidden by default) -->
                <div id="kura-ai-debug-info" class="kura-ai-debug-panel" style="display: none;">
                    <div class="debug-header">
                        <h4><?php \_e('System Debug Information', 'kura-ai'); ?></h4>
                        <button id="kura-ai-copy-debug" class="button button-secondary">
                            <?php \_e('Copy to Clipboard', 'kura-ai'); ?>
                        </button>
                    </div>
                    <textarea readonly><?php
                    if (method_exists($this, 'get_debug_info')) {
                        echo \esc_textarea($this->get_debug_info());
                    } else {
                        echo \esc_textarea(\__('Debug information is currently unavailable.', 'kura-ai'));
                    }
                    ?></textarea>
                </div>
            </div>
        </div>
    </div>

    <!-- Reset confirmation now handled by SweetAlert -->
</div>

<script>
    jQuery(document).ready(function($) {
        // Handle provider enable/disable
        $('.enable-provider').on('change', function() {
            const provider = $(this).data('provider');
            const isChecked = $(this).is(':checked');
            $(this).closest('.kura-ai-provider').find('.api-key-section').toggle(isChecked);
            
            // If enabling a provider, save it as the selected AI service
            if (isChecked) {
                // Uncheck other providers (only one can be active)
                $('.enable-provider').not(this).prop('checked', false);
                $('.enable-provider').not(this).closest('.kura-ai-provider').find('.api-key-section').hide();
                
                // Save the selected provider to settings
                $.post(kura_ai_ajax.ajax_url, {
                    action: 'save_ai_service_provider',
                    provider: provider,
                    _wpnonce: $(this).closest('.kura-ai-provider').find('.save-api-key').data('nonce')
                }, function(response) {
                    if (response.success) {
                        console.log('AI service provider updated to: ' + provider);
                    }
                });
            }
        });

        // Handle API key input focus (clear masked key)
        $('[id$="-api-key"]').on('focus', function() {
            const $input = $(this);
            const currentValue = $input.val();
            // If the value contains asterisks (masked key), clear it
            if (currentValue && currentValue.includes('*')) {
                $input.val('');
                $input.attr('placeholder', 'Enter new API Key');
            }
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
                        allowOutsideClick: true,
                        allowEscapeKey: true,
                        customClass: {
                            confirmButton: 'button button-primary'
                        }
                    }).then((result) => {
                        // Modal will close automatically after clicking OK
                        if (result.isConfirmed) {
                            // Optional: Add any additional actions after confirmation
                        }
                    });
                } else {
                    Swal.fire({
                        title: 'Error!',
                        text: response.data.message || 'Failed to save API key.',
                        icon: 'error',
                        confirmButtonText: 'OK',
                        allowOutsideClick: true,
                        allowEscapeKey: true,
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
                    allowOutsideClick: true,
                    allowEscapeKey: true,
                    customClass: {
                        confirmButton: 'button button-primary'
                    }
                });
            });
        });

        // Handle chatbot settings
        $('#kura-ai-chatbot-enabled').on('change', function() {
            const isEnabled = $(this).is(':checked');
            
            $.post(kura_ai_ajax.ajax_url, {
                action: 'save_chatbot_settings',
                enabled: isEnabled ? 1 : 0,
                _wpnonce: $('.save-api-key').first().data('nonce')
            }, function(response) {
                if (response.success) {
                    console.log('Chatbot setting updated: ' + (isEnabled ? 'enabled' : 'disabled'));
                } else {
                    console.error('Failed to update chatbot setting');
                }
            });
        });

        $('#kura-ai-chatbot-position').on('change', function() {
            const position = $(this).val();
            
            $.post(kura_ai_ajax.ajax_url, {
                action: 'save_chatbot_position',
                position: position,
                _wpnonce: $('.save-api-key').first().data('nonce')
            }, function(response) {
                if (response.success) {
                    console.log('Chatbot position updated: ' + position);
                } else {
                    console.error('Failed to update chatbot position');
                }
            });
        });
    });
</script>