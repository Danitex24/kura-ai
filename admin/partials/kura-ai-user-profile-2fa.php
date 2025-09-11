<?php
/**
 * User profile 2FA settings template.
 *
 * @link       https://kura.ai
 * @since      1.1.0
 *
 * @package    Kura_AI
 * @subpackage Kura_AI/admin/partials
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Get the current user
$user = wp_get_current_user();
$user_id = $user->ID;

// Get 2FA status for the user
$is_2fa_enabled = get_user_meta($user_id, 'kura_ai_2fa_enabled', true);
$secret_key = get_user_meta($user_id, 'kura_ai_2fa_secret', true);
$backup_codes = get_user_meta($user_id, 'kura_ai_2fa_backup_codes', true);

// Check if we need to generate a new secret key
if (empty($secret_key)) {
    $secret_key = Kura_AI\Kura_AI_2FA::generate_secret_key();
    update_user_meta($user_id, 'kura_ai_2fa_secret', $secret_key);
}

// Generate QR code URL
$qr_code_url = Kura_AI\Kura_AI_2FA::get_qr_code_url($user->user_login, $secret_key, get_bloginfo('name'));

// Check if we need to generate backup codes
if (empty($backup_codes)) {
    $backup_codes = Kura_AI\Kura_AI_2FA::generate_backup_codes();
    update_user_meta($user_id, 'kura_ai_2fa_backup_codes', $backup_codes);
}
?>

<h2><?php esc_html_e('Two-Factor Authentication', 'kura-ai'); ?></h2>

<table class="form-table" role="presentation">
    <tr>
        <th scope="row"><?php esc_html_e('Enable 2FA', 'kura-ai'); ?></th>
        <td>
            <label for="kura_ai_2fa_enabled">
                <input name="kura_ai_2fa_enabled" type="checkbox" id="kura_ai_2fa_enabled" value="1" <?php checked($is_2fa_enabled, '1'); ?> />
                <?php esc_html_e('Enable two-factor authentication for your account', 'kura-ai'); ?>
            </label>
        </td>
    </tr>
</table>

<div id="kura-ai-2fa-setup" class="kura-ai-2fa-container" <?php echo $is_2fa_enabled ? '' : 'style="display:none;"'; ?>>
    <h3><?php esc_html_e('Setup Instructions', 'kura-ai'); ?></h3>
    
    <ol>
        <li><?php esc_html_e('Install an authenticator app on your mobile device (Google Authenticator, Authy, Microsoft Authenticator, etc.)', 'kura-ai'); ?></li>
        <li><?php esc_html_e('Scan the QR code below with your authenticator app', 'kura-ai'); ?></li>
        <li><?php esc_html_e('Enter the verification code from your app to verify setup', 'kura-ai'); ?></li>
    </ol>
    
    <div class="kura-ai-2fa-qr-container">
        <img src="<?php echo esc_url($qr_code_url); ?>" alt="<?php esc_attr_e('QR Code for 2FA', 'kura-ai'); ?>" />
    </div>
    
    <p><?php esc_html_e('If you cannot scan the QR code, enter this code manually into your app:', 'kura-ai'); ?></p>
    <div class="kura-ai-2fa-secret"><?php echo esc_html(chunk_split($secret_key, 4, ' ')); ?></div>
    
    <h3><?php esc_html_e('Verify Setup', 'kura-ai'); ?></h3>
    <p><?php esc_html_e('Enter the verification code from your authenticator app to confirm setup:', 'kura-ai'); ?></p>
    
    <input type="text" name="kura_ai_2fa_code" id="kura_ai_2fa_code" class="regular-text" autocomplete="off" />
    <p class="description"><?php esc_html_e('Enter the 6-digit code from your authenticator app', 'kura-ai'); ?></p>
    
    <div id="kura-ai-2fa-verify-result"></div>
    
    <h3><?php esc_html_e('Backup Codes', 'kura-ai'); ?></h3>
    <p><?php esc_html_e('Save these backup codes in a secure location. You can use these codes to log in if you lose access to your authenticator app. Each code can only be used once.', 'kura-ai'); ?></p>
    
    <div class="kura-ai-2fa-backup-codes">
        <?php foreach ($backup_codes as $code) : ?>
            <div class="kura-ai-2fa-backup-code"><?php echo esc_html($code); ?></div>
        <?php endforeach; ?>
    </div>
    
    <p>
        <button type="button" class="button" id="kura-ai-regenerate-backup-codes"><?php esc_html_e('Generate New Backup Codes', 'kura-ai'); ?></button>
        <span class="description"><?php esc_html_e('Warning: This will invalidate your existing backup codes', 'kura-ai'); ?></span>
    </p>
</div>

<script>
jQuery(document).ready(function($) {
    // Toggle 2FA setup section visibility
    $('#kura_ai_2fa_enabled').on('change', function() {
        if ($(this).is(':checked')) {
            $('#kura-ai-2fa-setup').show();
        } else {
            $('#kura-ai-2fa-setup').hide();
        }
    });
    
    // Verify 2FA code
    $('#kura_ai_2fa_code').on('blur', function() {
        var code = $(this).val();
        if (code.length === 6) {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'kura_ai_verify_2fa_setup',
                    code: code,
                    nonce: '<?php echo wp_create_nonce('kura_ai_2fa_nonce'); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        $('#kura-ai-2fa-verify-result').html('<div class="notice notice-success inline"><p>' + response.data.message + '</p></div>');
                    } else {
                        $('#kura-ai-2fa-verify-result').html('<div class="notice notice-error inline"><p>' + response.data.message + '</p></div>');
                    }
                }
            });
        }
    });
    
    // Regenerate backup codes
    $('#kura-ai-regenerate-backup-codes').on('click', function() {
        if (confirm('<?php esc_html_e('Are you sure you want to generate new backup codes? This will invalidate all existing backup codes.', 'kura-ai'); ?>')) {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'kura_ai_regenerate_backup_codes',
                    nonce: '<?php echo wp_create_nonce('kura_ai_2fa_nonce'); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert(response.data.message);
                    }
                }
            });
        }
    });
});
</script>