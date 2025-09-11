<?php
/**
 * Login Security settings page.
 *
 * @package    Kura_AI
 * @subpackage Kura_AI/admin/partials
 * @author     Daniel Abughdyer <daniel@danovatesolutions.org>
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Include necessary WordPress core files
require_once(ABSPATH . 'wp-admin/includes/plugin.php');
require_once(ABSPATH . 'wp-includes/pluggable.php');
require_once(ABSPATH . 'wp-includes/formatting.php');
require_once(ABSPATH . 'wp-includes/functions.php');
require_once(ABSPATH . 'wp-admin/includes/admin.php');

// Get the login security settings
$login_security = new Kura_AI\Kura_AI_Login_Security(KURA_AI_PLUGIN_NAME, KURA_AI_VERSION);
$settings = $login_security->get_settings();

// Process form submission
if (isset($_POST['kura_ai_login_security_nonce']) && \wp_verify_nonce($_POST['kura_ai_login_security_nonce'], 'kura_ai_login_security')) {
    // Update settings
    $new_settings = array(
        'enable_2fa' => isset($_POST['enable_2fa']),
        'enable_captcha' => isset($_POST['enable_captcha']),
        'captcha_type' => isset($_POST['captcha_type']) ? \sanitize_text_field($_POST['captcha_type']) : 'math',
        'recaptcha_site_key' => isset($_POST['recaptcha_site_key']) ? \sanitize_text_field($_POST['recaptcha_site_key']) : '',
        'recaptcha_secret_key' => isset($_POST['recaptcha_secret_key']) ? \sanitize_text_field($_POST['recaptcha_secret_key']) : '',
        'recaptcha_v3_threshold' => isset($_POST['recaptcha_v3_threshold']) ? \sanitize_text_field($_POST['recaptcha_v3_threshold']) : '0.5',
        'disable_xmlrpc' => isset($_POST['disable_xmlrpc']),
        'enforce_2fa_on_xmlrpc' => isset($_POST['enforce_2fa_on_xmlrpc']),
        'check_password_pwned' => isset($_POST['check_password_pwned']),
        'block_admin_on_pwned' => isset($_POST['block_admin_on_pwned']),
        'required_roles' => isset($_POST['required_roles']) ? array_map('\sanitize_text_field', $_POST['required_roles']) : array('administrator')
    );
    
    $login_security->update_settings($new_settings);
    $settings = $login_security->get_settings();
    
    echo '<div class="notice notice-success is-dismissible"><p>' . \__('Settings saved successfully.', 'kura-ai') . '</p></div>';
}

// Display warning if CAPTCHA is enabled but API keys are not configured
if (isset($settings['enable_captcha']) && $settings['enable_captcha'] && 
    isset($settings['captcha_type']) && ($settings['captcha_type'] === 'recaptcha_v2' || $settings['captcha_type'] === 'recaptcha_v3')) {
    
    if (empty($settings['recaptcha_site_key']) || empty($settings['recaptcha_secret_key'])) {
        echo '<div class="notice notice-warning is-dismissible"><p>' . 
            \sprintf(
                \__('Google reCAPTCHA is enabled but API keys are not configured. Please %1$sget your API keys%2$s and configure them below.', 'kura-ai'),
                '<a href="https://www.google.com/recaptcha/admin" target="_blank">',
                '</a>'
            ) . 
            '</p></div>';
    }
}

// Get available roles
$roles = \wp_roles()->get_names();
?>

<div class="wrap kura-ai-settings">
    <h1><?php \_e('Login Security Settings', 'kura-ai'); ?></h1>
    
    <form method="post" action="">
        <?php \wp_nonce_field('kura_ai_login_security', 'kura_ai_login_security_nonce'); ?>
        
        <div class="kura-ai-settings-grid">
            <!-- 2FA Card -->
            <div class="kura-ai-settings-card" id="2fa-card">
                <div class="card-header">
                    <div class="card-icon 2fa-icon">
                        <span class="dashicons dashicons-lock"></span>
                    </div>
                    <h2><?php \_e('Two-Factor Authentication (2FA)', 'kura-ai'); ?></h2>
                </div>
                <div class="card-content">
                    <div class="form-field">
                        <label class="toggle-switch">
                            <input type="checkbox" name="enable_2fa" id="enable_2fa" value="1" <?php \checked(isset($settings['enable_2fa']) && $settings['enable_2fa']); ?> />
                            <span class="toggle-slider"></span>
                        </label>
                        <label for="enable_2fa" class="setting-label"><?php \_e('Enable 2FA', 'kura-ai'); ?></label>
                        <p class="description"><?php \_e('Enable Two-Factor Authentication for user accounts.', 'kura-ai'); ?></p>
                    </div>
                    
                    <div class="form-field 2fa-settings" <?php echo !isset($settings['enable_2fa']) || !$settings['enable_2fa'] ? 'style="display:none;"' : ''; ?>>
                        <label class="setting-label"><?php \_e('Required Roles', 'kura-ai'); ?></label>
                        <div class="checkbox-group">
                            <?php foreach ($roles as $role_key => $role_name) : ?>
                                <label class="checkbox-label">
                                    <input type="checkbox" name="required_roles[]" value="<?php echo \esc_attr($role_key); ?>" <?php \checked(isset($settings['required_roles']) && \in_array($role_key, $settings['required_roles'])); ?> />
                                    <span><?php echo \esc_html($role_name); ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                        <p class="description"><?php \_e('Select which user roles are required to use 2FA.', 'kura-ai'); ?></p>
                    </div>
                </div>
            </div>
            
            <!-- CAPTCHA Card -->
            <div class="kura-ai-settings-card" id="captcha-card">
                <div class="card-header">
                    <div class="card-icon captcha-icon">
                        <span class="dashicons dashicons-shield"></span>
                    </div>
                    <h2><?php \_e('CAPTCHA', 'kura-ai'); ?></h2>
                </div>
                <div class="card-content">
                    <div class="form-field">
                        <label class="toggle-switch">
                            <input type="checkbox" name="enable_captcha" id="enable_captcha" value="1" <?php \checked(isset($settings['enable_captcha']) && $settings['enable_captcha']); ?> />
                            <span class="toggle-slider"></span>
                        </label>
                        <label for="enable_captcha" class="setting-label"><?php \_e('Enable CAPTCHA', 'kura-ai'); ?></label>
                        <p class="description"><?php \_e('Enable CAPTCHA on login forms to prevent bot logins.', 'kura-ai'); ?></p>
                    </div>
                    
                    <div class="captcha-settings" <?php echo !isset($settings['enable_captcha']) || !$settings['enable_captcha'] ? 'style="display:none;"' : ''; ?>>
                        <div class="form-field">
                            <label for="captcha_type" class="setting-label"><?php \_e('CAPTCHA Type', 'kura-ai'); ?></label>
                            <select name="captcha_type" id="captcha_type" class="kura-select">
                                <option value="math" <?php \selected(isset($settings['captcha_type']) && $settings['captcha_type'] === 'math'); ?>><?php \_e('Math Challenge', 'kura-ai'); ?></option>
                                <option value="recaptcha_v2" <?php \selected(isset($settings['captcha_type']) && $settings['captcha_type'] === 'recaptcha_v2'); ?>><?php \_e('Google reCAPTCHA v2', 'kura-ai'); ?></option>
                                <option value="recaptcha_v3" <?php \selected(isset($settings['captcha_type']) && $settings['captcha_type'] === 'recaptcha_v3'); ?>><?php \_e('Google reCAPTCHA v3', 'kura-ai'); ?></option>
                            </select>
                            <p class="description"><?php \_e('Select the type of CAPTCHA to use.', 'kura-ai'); ?></p>
                        </div>
                        
                        <div class="recaptcha-settings" <?php echo !isset($settings['captcha_type']) || ($settings['captcha_type'] !== 'recaptcha_v2' && $settings['captcha_type'] !== 'recaptcha_v3') || !isset($settings['enable_captcha']) || !$settings['enable_captcha'] ? 'style="display:none;"' : ''; ?>>
                            <div class="form-field">
                                <label for="recaptcha_site_key" class="setting-label"><?php \_e('reCAPTCHA Site Key', 'kura-ai'); ?></label>
                                <input type="text" name="recaptcha_site_key" id="recaptcha_site_key" value="<?php echo \esc_attr(isset($settings['recaptcha_site_key']) ? $settings['recaptcha_site_key'] : ''); ?>" class="regular-text" />
                                <p class="description"><?php \_e('Enter your Google reCAPTCHA Site Key.', 'kura-ai'); ?> <a href="https://www.google.com/recaptcha/admin" target="_blank"><?php \_e('Get your reCAPTCHA keys here', 'kura-ai'); ?></a></p>
                            </div>
                            
                            <div class="form-field">
                                <label for="recaptcha_secret_key" class="setting-label"><?php \_e('reCAPTCHA Secret Key', 'kura-ai'); ?></label>
                                <input type="password" name="recaptcha_secret_key" id="recaptcha_secret_key" value="<?php echo \esc_attr(isset($settings['recaptcha_secret_key']) ? $settings['recaptcha_secret_key'] : ''); ?>" class="regular-text" />
                                <p class="description"><?php \_e('Enter your Google reCAPTCHA Secret Key.', 'kura-ai'); ?></p>
                            </div>
                        </div>
                        
                        <div class="recaptcha-v3-settings" <?php echo !isset($settings['captcha_type']) || $settings['captcha_type'] !== 'recaptcha_v3' || !isset($settings['enable_captcha']) || !$settings['enable_captcha'] ? 'style="display:none;"' : ''; ?>>
                            <div class="form-field">
                                <label for="recaptcha_v3_threshold" class="setting-label"><?php \_e('reCAPTCHA v3 Score Threshold', 'kura-ai'); ?></label>
                                <select name="recaptcha_v3_threshold" id="recaptcha_v3_threshold" class="kura-select">
                                    <option value="0.1" <?php \selected(isset($settings['recaptcha_v3_threshold']) && $settings['recaptcha_v3_threshold'] === '0.1'); ?>><?php \_e('0.1 - Very Low Security', 'kura-ai'); ?></option>
                                    <option value="0.3" <?php \selected(isset($settings['recaptcha_v3_threshold']) && $settings['recaptcha_v3_threshold'] === '0.3'); ?>><?php \_e('0.3 - Low Security', 'kura-ai'); ?></option>
                                    <option value="0.5" <?php \selected(!isset($settings['recaptcha_v3_threshold']) || $settings['recaptcha_v3_threshold'] === '0.5'); ?>><?php \_e('0.5 - Medium Security (Default)', 'kura-ai'); ?></option>
                                    <option value="0.7" <?php \selected(isset($settings['recaptcha_v3_threshold']) && $settings['recaptcha_v3_threshold'] === '0.7'); ?>><?php \_e('0.7 - High Security', 'kura-ai'); ?></option>
                                    <option value="0.9" <?php \selected(isset($settings['recaptcha_v3_threshold']) && $settings['recaptcha_v3_threshold'] === '0.9'); ?>><?php \_e('0.9 - Very High Security', 'kura-ai'); ?></option>
                                </select>
                                <p class="description"><?php \_e('Set the minimum score threshold for reCAPTCHA v3 (0.0 is bot, 1.0 is human). Higher values provide more security but may block legitimate users.', 'kura-ai'); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- XML-RPC Security Card -->
            <div class="kura-ai-settings-card" id="xmlrpc-card">
                <div class="card-header">
                    <div class="card-icon xmlrpc-icon">
                        <span class="dashicons dashicons-admin-site-alt3"></span>
                    </div>
                    <h2><?php \_e('XML-RPC Security', 'kura-ai'); ?></h2>
                </div>
                <div class="card-content">
                    <div class="form-field">
                        <label class="toggle-switch">
                            <input type="checkbox" name="disable_xmlrpc" id="disable_xmlrpc" value="1" <?php \checked(isset($settings['disable_xmlrpc']) && $settings['disable_xmlrpc']); ?> />
                            <span class="toggle-slider"></span>
                        </label>
                        <label for="disable_xmlrpc" class="setting-label"><?php \_e('Disable XML-RPC', 'kura-ai'); ?></label>
                        <p class="description"><?php \_e('Completely disable XML-RPC functionality. Note: This may affect some plugins or services that rely on XML-RPC.', 'kura-ai'); ?></p>
                    </div>
                    
                    <div class="form-field xmlrpc-settings" <?php echo isset($settings['disable_xmlrpc']) && $settings['disable_xmlrpc'] ? 'style="display:none;"' : ''; ?>>
                        <label class="toggle-switch">
                            <input type="checkbox" name="enforce_2fa_on_xmlrpc" id="enforce_2fa_on_xmlrpc" value="1" <?php \checked(isset($settings['enforce_2fa_on_xmlrpc']) && $settings['enforce_2fa_on_xmlrpc']); ?> />
                            <span class="toggle-slider"></span>
                        </label>
                        <label for="enforce_2fa_on_xmlrpc" class="setting-label"><?php \_e('Enforce 2FA on XML-RPC', 'kura-ai'); ?></label>
                        <p class="description"><?php \_e('Require 2FA for XML-RPC authentication requests. Users must append their 2FA code to their password.', 'kura-ai'); ?></p>
                    </div>
                </div>
            </div>
            
            <!-- Password Security Card -->
            <div class="kura-ai-settings-card" id="password-card">
                <div class="card-header">
                    <div class="card-icon password-icon">
                        <span class="dashicons dashicons-privacy"></span>
                    </div>
                    <h2><?php \_e('Password Security', 'kura-ai'); ?></h2>
                </div>
                <div class="card-content">
                    <div class="form-field">
                        <label class="toggle-switch">
                            <input type="checkbox" name="check_password_pwned" id="check_password_pwned" value="1" <?php \checked(isset($settings['check_password_pwned']) && $settings['check_password_pwned']); ?> />
                            <span class="toggle-slider"></span>
                        </label>
                        <label for="check_password_pwned" class="setting-label"><?php \_e('Check Compromised Passwords', 'kura-ai'); ?></label>
                        <p class="description"><?php \_e('Check passwords against the HaveIBeenPwned database to prevent the use of compromised passwords.', 'kura-ai'); ?></p>
                    </div>
                    
                    <div class="form-field password-security-settings" <?php echo !isset($settings['check_password_pwned']) || !$settings['check_password_pwned'] ? 'style="display:none;"' : ''; ?>>
                        <label class="toggle-switch">
                            <input type="checkbox" name="block_admin_on_pwned" id="block_admin_on_pwned" value="1" <?php \checked(isset($settings['block_admin_on_pwned']) && $settings['block_admin_on_pwned']); ?> />
                            <span class="toggle-slider"></span>
                        </label>
                        <label for="block_admin_on_pwned" class="setting-label"><?php \_e('Block Admin Logins', 'kura-ai'); ?></label>
                        <p class="description"><?php \_e('Block administrator logins if compromised passwords are detected, and force password reset.', 'kura-ai'); ?></p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="kura-ai-settings-submit">
            <input type="submit" name="submit" id="submit" class="button button-primary" value="<?php \_e('Save Changes', 'kura-ai'); ?>" />
        </div>
    </form>
</div>

<script>
jQuery(document).ready(function($) {
    // Improve toggle switch functionality
    $('.toggle-switch').on('click', function(e) {
        // Only handle clicks on the toggle-switch or toggle-slider, not the input itself
        if (!$(e.target).is('input')) {
            var checkbox = $(this).find('input[type="checkbox"]');
            checkbox.prop('checked', !checkbox.is(':checked')).trigger('change');
            e.preventDefault();
        }
    });
    
    // Make setting labels clickable to toggle the associated checkbox
    $('.setting-label').on('click', function() {
        var forAttr = $(this).attr('for');
        if (forAttr) {
            var checkbox = $('#' + forAttr);
            checkbox.prop('checked', !checkbox.is(':checked')).trigger('change');
        }
    });
    
    // Toggle 2FA settings
    $('#enable_2fa').on('change', function() {
        if ($(this).is(':checked')) {
            $('.2fa-settings').show();
        } else {
            $('.2fa-settings').hide();
        }
    });
    
    // Toggle XML-RPC settings
    $('#disable_xmlrpc').on('change', function() {
        if ($(this).is(':checked')) {
            $('.xmlrpc-settings').hide();
        } else {
            $('.xmlrpc-settings').show();
        }
    });
    
    // Toggle password security settings
    $('#check_password_pwned').on('change', function() {
        if ($(this).is(':checked')) {
            $('.password-security-settings').show();
        } else {
            $('.password-security-settings').hide();
        }
    });
    
    // Fix for toggle switches - ensure proper state is reflected
    $('.toggle-switch input[type="checkbox"]').each(function() {
        // Make sure the toggle state matches the checkbox state
        $(this).trigger('change');
    });
});
</script>