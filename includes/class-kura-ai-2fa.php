<?php
/**
 * Two-Factor Authentication functionality.
 *
 * @package    Kura_AI
 * @subpackage Kura_AI/includes
 * @author     Daniel Abughdyer <daniel@danovatesolutions.org>
 */

namespace Kura_AI;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class for handling Two-Factor Authentication (2FA)
 *
 * @since      1.0.0
 * @package    Kura_AI
 * @subpackage Kura_AI/includes
 */
class Kura_AI_2FA {

    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $plugin_name    The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $version    The current version of this plugin.
     */
    private $version;

    /**
     * The login security instance.
     *
     * @since    1.0.0
     * @access   private
     * @var      Kura_AI_Login_Security    $login_security    The login security instance.
     */
    private $login_security;

    /**
     * The secret key length.
     *
     * @since    1.0.0
     * @access   private
     * @var      int    $secret_length    The secret key length.
     */
    private $secret_length = 16;

    /**
     * The number of backup codes to generate.
     *
     * @since    1.0.0
     * @access   private
     * @var      int    $backup_codes_count    The number of backup codes to generate.
     */
    private $backup_codes_count = 10;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param    string                   $plugin_name       The name of this plugin.
     * @param    string                   $version           The version of this plugin.
     * @param    Kura_AI_Login_Security   $login_security    The login security instance.
     */
    public function __construct($plugin_name, $version, $login_security) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        $this->login_security = $login_security;
        
        // Initialize the 2FA functionality
        $this->init();
    }

    /**
     * Initialize the 2FA functionality.
     *
     * @since    1.0.0
     */
    public function init() {
        // Register hooks for 2FA
        add_action('wp_authenticate_user', array($this, 'check_2fa_requirement'), 10, 2);
        add_action('wp_login', array($this, 'handle_2fa_login'), 10, 2);
        add_action('login_form_validate_2fa', array($this, 'validate_2fa_form'));
        
        // Register hooks for user profile 2FA settings
        \add_action('show_user_profile', array($this, 'add_2fa_user_profile_fields'));
        \add_action('edit_user_profile', array($this, 'add_2fa_user_profile_fields'));
        \add_action('personal_options_update', array($this, 'save_2fa_user_profile_fields'));
        \add_action('edit_user_profile_update', array($this, 'save_2fa_user_profile_fields'));
        
        // Register hooks for WooCommerce integration if WooCommerce is active
        if ($this->is_woocommerce_active()) {
            \add_action('woocommerce_login_form_end', array($this, 'add_2fa_woocommerce_login_field'));
            \add_filter('woocommerce_process_login_errors', array($this, 'verify_woocommerce_2fa'), 10, 3);
        }
        
        // Register AJAX handlers for 2FA
        \add_action('wp_ajax_kura_ai_generate_2fa_secret', array($this, 'ajax_generate_2fa_secret'));
        \add_action('wp_ajax_kura_ai_verify_2fa_code', array($this, 'ajax_verify_2fa_code'));
        \add_action('wp_ajax_kura_ai_generate_backup_codes', array($this, 'ajax_generate_backup_codes'));
    }

    /**
     * Check if WooCommerce is active.
     *
     * @since    1.0.0
     * @return   bool    True if WooCommerce is active, false otherwise.
     */
    private function is_woocommerce_active() {
        return class_exists('WooCommerce');
    }

    /**
     * Check if 2FA is required for a user.
     *
     * @since    1.0.0
     * @param    WP_User|WP_Error    $user        WP_User object or WP_Error.
     * @param    string              $password    User password.
     * @return   WP_User|WP_Error                 WP_User object if 2FA is not required, WP_Error otherwise.
     */
    public function check_2fa_requirement($user, $password) {
        if (\is_wp_error($user)) {
            return $user;
        }
        
        $settings = $this->login_security->get_settings();
        
        // Check if 2FA is enabled
        if (!isset($settings['enable_2fa']) || !$settings['enable_2fa']) {
            return $user;
        }
        
        // Check if 2FA is required for the user's role
        $required_roles = isset($settings['required_roles']) ? $settings['required_roles'] : array('administrator');
        $user_roles = $user->roles;
        $requires_2fa = false;
        
        foreach ($user_roles as $role) {
            if (\in_array($role, $required_roles)) {
                $requires_2fa = true;
                break;
            }
        }
        
        if (!$requires_2fa) {
            return $user;
        }
        
        // Check if 2FA is enabled for the user
        $user_2fa_enabled = \get_user_meta($user->ID, 'kura_ai_2fa_enabled', true);
        
        if (!$user_2fa_enabled) {
            return $user;
        }
        
        // Store the user ID and password in a transient for the 2FA validation
        $transient_key = \wp_generate_password(32, false);
        \set_transient('kura_ai_2fa_' . $transient_key, array(
            'user_id' => $user->ID,
            'remember' => isset($_POST['rememberme']) && $_POST['rememberme']
        ), 600); // 10 minutes expiration
        
        // Redirect to the 2FA validation form
        \wp_safe_redirect(\add_query_arg(
            array(
                'action' => 'validate_2fa',
                'key' => $transient_key,
                'redirect_to' => isset($_REQUEST['redirect_to']) ? $_REQUEST['redirect_to'] : \admin_url()
            ),
            \wp_login_url()
        ));
        exit;
    }

    /**
     * Handle 2FA login.
     *
     * @since    1.0.0
     * @param    string    $user_login    User login.
     * @param    WP_User   $user          WP_User object.
     */
    public function handle_2fa_login($user_login, $user) {
        // No action needed here as the check_2fa_requirement method handles the 2FA validation
    }

    /**
     * Validate 2FA form.
     *
     * @since    1.0.0
     */
    public function validate_2fa_form() {
        if (!isset($_GET['key'])) {
            \wp_safe_redirect(\wp_login_url());
            exit;
        }
        
        $transient_key = \sanitize_text_field($_GET['key']);
        $transient_data = \get_transient('kura_ai_2fa_' . $transient_key);
        
        if (!$transient_data) {
            \wp_safe_redirect(\wp_login_url());
            exit;
        }
        
        $user_id = $transient_data['user_id'];
        $user = \get_user_by('id', $user_id);
        
        if (!$user) {
            \wp_safe_redirect(\wp_login_url());
            exit;
        }
        
        if (isset($_POST['kura_ai_2fa_code'])) {
            $code = \sanitize_text_field($_POST['kura_ai_2fa_code']);
            
            if ($this->verify_2fa_code($user_id, $code)) {
                // Code is valid, log the user in
                \wp_set_auth_cookie($user_id, $transient_data['remember']);
                \delete_transient('kura_ai_2fa_' . $transient_key);
                
                $redirect_to = isset($_REQUEST['redirect_to']) ? $_REQUEST['redirect_to'] : \admin_url();
                \wp_safe_redirect($redirect_to);
                exit;
            } else {
                // Code is invalid, show an error
                $error = new \WP_Error('invalid_2fa_code', \__('Invalid authentication code. Please try again.', 'kura-ai'));
            }
        }
        
        // Display the 2FA form
        \login_header(\__('Two-Factor Authentication', 'kura-ai'), '<p class="message">' . \__('Please enter the authentication code from your authenticator app.', 'kura-ai') . '</p>', $error);
        
        ?>
        <form name="validate_2fa" id="loginform" action="<?php echo \esc_url(\add_query_arg('action', 'validate_2fa', \wp_login_url())); ?>" method="post">
            <p>
                <label for="kura_ai_2fa_code"><?php \_e('Authentication Code', 'kura-ai'); ?></label>
                <input type="text" name="kura_ai_2fa_code" id="kura_ai_2fa_code" class="input" value="" size="20" pattern="[0-9]*" autocomplete="one-time-code" />
            </p>
            <p class="submit">
                <input type="submit" name="wp-submit" id="wp-submit" class="button button-primary button-large" value="<?php \esc_attr_e('Log In', 'kura-ai'); ?>" />
                <input type="hidden" name="key" value="<?php echo \esc_attr($transient_key); ?>" />
                <input type="hidden" name="redirect_to" value="<?php echo \esc_attr(isset($_REQUEST['redirect_to']) ? $_REQUEST['redirect_to'] : \admin_url()); ?>" />
            </p>
        </form>
        <?php
        
        \login_footer();
        exit;
    }

    /**
     * Generate a new secret key.
     *
     * @since    1.0.0
     * @return   string    The new secret key.
     */
    public function generate_secret() {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567'; // Base32 characters
        $secret = '';
        
        for ($i = 0; $i < $this->secret_length; $i++) {
            $secret .= $chars[\wp_rand(0, \strlen($chars) - 1)];
        }
        
        return $secret;
    }

    /**
     * Generate backup codes for a user.
     *
     * @since    1.0.0
     * @param    int       $user_id    User ID.
     * @return   string[]              Array of backup codes.
     */
    public function generate_backup_codes($user_id) {
        $backup_codes = array();
        
        for ($i = 0; $i < $this->backup_codes_count; $i++) {
            $backup_codes[] = $this->generate_backup_code();
        }
        
        // Store the backup codes in the user meta
        $hashed_codes = array();
        
        foreach ($backup_codes as $code) {
            $hashed_codes[] = \wp_hash_password($code);
        }
        
        \update_user_meta($user_id, 'kura_ai_2fa_backup_codes', $hashed_codes);
        
        return $backup_codes;
    }

    /**
     * Generate a single backup code.
     *
     * @since    1.0.0
     * @return   string    The backup code.
     */
    private function generate_backup_code() {
        return \substr(\str_replace('-', '', \wp_generate_uuid4()), 0, 8);
    }

    /**
     * Verify a 2FA code.
     *
     * @since    1.0.0
     * @param    int       $user_id    User ID.
     * @param    string    $code       The 2FA code to verify.
     * @return   bool                  True if the code is valid, false otherwise.
     */
    public function verify_2fa_code($user_id, $code) {
        // Check if the code is a backup code
        $backup_codes = \get_user_meta($user_id, 'kura_ai_2fa_backup_codes', true);
        
        if (\is_array($backup_codes)) {
            foreach ($backup_codes as $key => $hashed_code) {
                if (\wp_check_password($code, $hashed_code)) {
                    // Remove the used backup code
                    unset($backup_codes[$key]);
                    \update_user_meta($user_id, 'kura_ai_2fa_backup_codes', $backup_codes);
                    return true;
                }
            }
        }
        
        // Check if the code is a valid TOTP code
        $secret = \get_user_meta($user_id, 'kura_ai_2fa_secret', true);
        
        if (!$secret) {
            return false;
        }
        
        return $this->verify_totp_code($secret, $code);
    }

    /**
     * Verify a TOTP code.
     *
     * @since    1.0.0
     * @param    string    $secret    The secret key.
     * @param    string    $code      The TOTP code to verify.
     * @return   bool                 True if the code is valid, false otherwise.
     */
    public function verify_totp_code($secret, $code) {
        // Since we don't have the OTP library available, we'll implement a basic TOTP verification
        // In a real implementation, you would use a proper TOTP library
        
        // For now, we'll implement a very basic verification that checks if the code is 6 digits
        // and matches the first 6 digits of a hash of the secret and the current time window
        
        // This is NOT secure and should be replaced with a proper TOTP implementation
        // using the christian-riesen/otp library or another TOTP library
        
        if (!\preg_match('/^\d{6}$/', $code)) {
            return false;
        }
        
        // Get the current time window (30-second window)
        $time_window = \floor(\time() / 30);
        
        // Check the current time window and the previous and next windows
        for ($i = -1; $i <= 1; $i++) {
            $hash = \hash_hmac('sha1', \pack('N*', $time_window + $i), $this->base32_decode($secret), true);
            $offset = \ord($hash[19]) & 0xf;
            $truncated = (((\ord($hash[$offset]) & 0x7f) << 24) |
                         ((\ord($hash[$offset + 1]) & 0xff) << 16) |
                         ((\ord($hash[$offset + 2]) & 0xff) << 8) |
                         (\ord($hash[$offset + 3]) & 0xff)) % 1000000;
            
            if ($truncated == $code) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Decode a base32 string.
     *
     * @since    1.0.0
     * @param    string    $base32    The base32 string to decode.
     * @return   string               The decoded string.
     */
    private function base32_decode($base32) {
        $base32 = \strtoupper($base32);
        $base32_map = array(
            'A' => 0, 'B' => 1, 'C' => 2, 'D' => 3, 'E' => 4, 'F' => 5, 'G' => 6, 'H' => 7,
            'I' => 8, 'J' => 9, 'K' => 10, 'L' => 11, 'M' => 12, 'N' => 13, 'O' => 14, 'P' => 15,
            'Q' => 16, 'R' => 17, 'S' => 18, 'T' => 19, 'U' => 20, 'V' => 21, 'W' => 22, 'X' => 23,
            'Y' => 24, 'Z' => 25, '2' => 26, '3' => 27, '4' => 28, '5' => 29, '6' => 30, '7' => 31
        );
        
        $base32 = \str_replace('=', '', $base32);
        $binary = '';
        
        for ($i = 0; $i < \strlen($base32); $i += 8) {
            $chunk = \substr($base32, $i, 8);
            $chunk = \str_pad($chunk, 8, '=');
            
            $binary_chunk = '';
            $bits = 0;
            $value = 0;
            
            for ($j = 0; $j < 8; $j++) {
                if ($chunk[$j] == '=') {
                    continue;
                }
                
                $value = ($value << 5) | $base32_map[$chunk[$j]];
                $bits += 5;
                
                if ($bits >= 8) {
                    $bits -= 8;
                    $binary_chunk .= \chr(($value >> $bits) & 0xff);
                }
            }
            
            $binary .= $binary_chunk;
        }
        
        return $binary;
    }

    /**
     * Add 2FA fields to user profile.
     *
     * @since    1.0.0
     * @param    WP_User    $user    WP_User object.
     */
    public function add_2fa_user_profile_fields($user) {
        $settings = $this->login_security->get_settings();
        
        // Check if 2FA is enabled
        if (!isset($settings['enable_2fa']) || !$settings['enable_2fa']) {
            return;
        }
        
        // Check if 2FA is required for the user's role
        $required_roles = isset($settings['required_roles']) ? $settings['required_roles'] : array('administrator');
        $user_roles = $user->roles;
        $requires_2fa = false;
        
        foreach ($user_roles as $role) {
            if (\in_array($role, $required_roles)) {
                $requires_2fa = true;
                break;
            }
        }
        
        // Get the user's 2FA settings
        $user_2fa_enabled = \get_user_meta($user->ID, 'kura_ai_2fa_enabled', true);
        $user_2fa_secret = \get_user_meta($user->ID, 'kura_ai_2fa_secret', true);
        
        // Generate a QR code URL for the secret
        $qr_code_url = '';
        
        if ($user_2fa_secret) {
            $site_name = \get_bloginfo('name');
            $user_email = $user->user_email;
            $qr_code_url = 'https://chart.googleapis.com/chart?chs=200x200&chld=M|0&cht=qr&chl=' . \urlencode("otpauth://totp/{$user_email}?secret={$user_2fa_secret}&issuer={$site_name}");
        }
        
        // Output the 2FA fields
        ?>
        <h2><?php _e('Two-Factor Authentication', 'kura-ai'); ?></h2>
        <table class="form-table">
            <tr>
                <th><label for="kura_ai_2fa_enabled"><?php \_e('Enable Two-Factor Authentication', 'kura-ai'); ?></label></th>
                <td>
                    <input type="checkbox" name="kura_ai_2fa_enabled" id="kura_ai_2fa_enabled" value="1" <?php \checked($user_2fa_enabled); ?> <?php \disabled($requires_2fa); ?> />
                    <?php if ($requires_2fa): ?>
                        <p class="description"><?php \_e('Two-Factor Authentication is required for your role.', 'kura-ai'); ?></p>
                    <?php else: ?>
                        <p class="description"><?php \_e('Enable Two-Factor Authentication for your account.', 'kura-ai'); ?></p>
                    <?php endif; ?>
                </td>
            </tr>
            <tr class="kura-ai-2fa-setup" <?php echo !$user_2fa_enabled ? 'style="display:none;"' : ''; ?>>
                <th><?php \_e('Setup Instructions', 'kura-ai'); ?></th>
                <td>
                    <ol>
                        <li><?php \_e('Install an authenticator app on your mobile device (Google Authenticator, Authy, Microsoft Authenticator, etc.).', 'kura-ai'); ?></li>
                        <li><?php \_e('Scan the QR code below with your authenticator app.', 'kura-ai'); ?></li>
                        <li><?php \_e('Enter the verification code from your authenticator app below to verify setup.', 'kura-ai'); ?></li>
                    </ol>
                </td>
            </tr>
            <tr class="kura-ai-2fa-setup" <?php echo !$user_2fa_enabled ? 'style="display:none;"' : ''; ?>>
                <th><?php \_e('Secret Key', 'kura-ai'); ?></th>
                <td>
                    <div class="kura-ai-2fa-secret-key">
                        <?php if ($user_2fa_secret): ?>
                            <code><?php echo \esc_html($this->format_secret_key($user_2fa_secret)); ?></code>
                        <?php else: ?>
                            <button type="button" class="button" id="kura-ai-generate-2fa-secret"><?php \_e('Generate Secret Key', 'kura-ai'); ?></button>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
            <tr class="kura-ai-2fa-setup" <?php echo !$user_2fa_enabled || !$user_2fa_secret ? 'style="display:none;"' : ''; ?>>
                <th><?php \_e('QR Code', 'kura-ai'); ?></th>
                <td>
                    <div class="kura-ai-2fa-qr-code">
                        <?php if ($qr_code_url): ?>
                            <img src="<?php echo \esc_url($qr_code_url); ?>" alt="<?php \esc_attr_e('QR Code', 'kura-ai'); ?>" />
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
            <tr class="kura-ai-2fa-setup" <?php echo !$user_2fa_enabled || !$user_2fa_secret ? 'style="display:none;"' : ''; ?>>
                <th><label for="kura_ai_2fa_code"><?php \_e('Verification Code', 'kura-ai'); ?></label></th>
                <td>
                    <input type="text" name="kura_ai_2fa_code" id="kura_ai_2fa_code" class="regular-text" pattern="[0-9]*" autocomplete="one-time-code" />
                    <p class="description"><?php \_e('Enter the verification code from your authenticator app to verify setup.', 'kura-ai'); ?></p>
                </td>
            </tr>
            <tr class="kura-ai-2fa-setup" <?php echo !$user_2fa_enabled || !$user_2fa_secret ? 'style="display:none;"' : ''; ?>>
                <th><?php \_e('Backup Codes', 'kura-ai'); ?></th>
                <td>
                    <button type="button" class="button" id="kura-ai-generate-backup-codes"><?php \_e('Generate Backup Codes', 'kura-ai'); ?></button>
                    <p class="description"><?php \_e('Backup codes can be used to access your account if you lose your device.', 'kura-ai'); ?></p>
                    <div class="kura-ai-2fa-backup-codes"></div>
                </td>
            </tr>
        </table>
        <script>
        jQuery(document).ready(function($) {
            // Toggle 2FA setup fields
            $('#kura_ai_2fa_enabled').on('change', function() {
                if ($(this).is(':checked')) {
                    $('.kura-ai-2fa-setup').show();
                } else {
                    $('.kura-ai-2fa-setup').hide();
                }
            });
            
            // Generate secret key
            $('#kura-ai-generate-2fa-secret').on('click', function() {
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'kura_ai_generate_2fa_secret',
                        user_id: <?php echo $user->ID; ?>,
                        nonce: '<?php echo \wp_create_nonce('kura_ai_2fa_nonce'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            $('.kura-ai-2fa-secret-key').html('<code>' + response.data.formatted_secret + '</code>');
                            $('.kura-ai-2fa-qr-code').html('<img src="' + response.data.qr_code_url + '" alt="QR Code" />');
                            $('.kura-ai-2fa-qr-code').closest('tr').show();
                            $('#kura_ai_2fa_code').closest('tr').show();
                            $('.kura-ai-2fa-backup-codes').closest('tr').show();
                        } else {
                            alert(response.data.message);
                        }
                    }
                });
            });
            
            // Generate backup codes
            $('#kura-ai-generate-backup-codes').on('click', function() {
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'kura_ai_generate_backup_codes',
                        user_id: <?php echo $user->ID; ?>,
                        nonce: '<?php echo \wp_create_nonce('kura_ai_2fa_nonce'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            var codes_html = '<ul class="kura-ai-backup-codes-list">';
                            $.each(response.data.backup_codes, function(index, code) {
                                codes_html += '<li><code>' + code + '</code></li>';
                            });
                            codes_html += '</ul>';
                            codes_html += '<p class="description"><?php _e('Save these backup codes in a secure location. They will not be shown again.', 'kura-ai'); ?></p>';
                            $('.kura-ai-2fa-backup-codes').html(codes_html);
                        } else {
                            alert(response.data.message);
                        }
                    }
                });
            });
        });
        </script>
        <?php
    }

    /**
     * Format a secret key for display.
     *
     * @since    1.0.0
     * @param    string    $secret    The secret key.
     * @return   string               The formatted secret key.
     */
    private function format_secret_key($secret) {
        $formatted = '';
        
        for ($i = 0; $i < \strlen($secret); $i++) {
            $formatted .= $secret[$i];
            
            if (($i + 1) % 4 === 0 && $i < \strlen($secret) - 1) {
                $formatted .= ' ';
            }
        }
        
        return $formatted;
    }

    /**
     * Save 2FA fields from user profile.
     *
     * @since    1.0.0
     * @param    int    $user_id    User ID.
     */
    public function save_2fa_user_profile_fields($user_id) {
        if (!\current_user_can('edit_user', $user_id)) {
            return;
        }
        
        $settings = $this->login_security->get_settings();
        
        // Check if 2FA is enabled
        if (!isset($settings['enable_2fa']) || !$settings['enable_2fa']) {
            return;
        }
        
        // Check if 2FA is required for the user's role
        $user = \get_user_by('id', $user_id);
        $required_roles = isset($settings['required_roles']) ? $settings['required_roles'] : array('administrator');
        $user_roles = $user->roles;
        $requires_2fa = false;
        
        foreach ($user_roles as $role) {
            if (\in_array($role, $required_roles)) {
                $requires_2fa = true;
                break;
            }
        }
        
        // If 2FA is required, force it to be enabled
        if ($requires_2fa) {
            \update_user_meta($user_id, 'kura_ai_2fa_enabled', true);
        } else {
            // Otherwise, update based on the form submission
            $enabled = isset($_POST['kura_ai_2fa_enabled']) ? true : false;
            \update_user_meta($user_id, 'kura_ai_2fa_enabled', $enabled);
        }
        
        // Verify the 2FA code if provided
        if (isset($_POST['kura_ai_2fa_code']) && !empty($_POST['kura_ai_2fa_code'])) {
            $code = \sanitize_text_field($_POST['kura_ai_2fa_code']);
            $secret = \get_user_meta($user_id, 'kura_ai_2fa_secret', true);
            
            if ($secret && $this->verify_totp_code($secret, $code)) {
                \update_user_meta($user_id, 'kura_ai_2fa_verified', true);
            } else {
                \add_settings_error('kura_ai_2fa', 'invalid_code', \__('Invalid verification code. Please try again.', 'kura-ai'));
            }
        }
    }

    /**
     * Add 2FA field to WooCommerce login form.
     *
     * @since    1.0.0
     */
    public function add_2fa_woocommerce_login_field() {
        $settings = $this->login_security->get_settings();
        
        // Check if 2FA is enabled
        if (!isset($settings['enable_2fa']) || !$settings['enable_2fa']) {
            return;
        }
        
        ?>
        <p class="form-row form-row-wide kura-ai-2fa-code" style="display:none;">
            <label for="kura_ai_2fa_code"><?php \_e('Authentication Code', 'kura-ai'); ?> <span class="required">*</span></label>
            <input type="text" class="input-text" name="kura_ai_2fa_code" id="kura_ai_2fa_code" pattern="[0-9]*" autocomplete="one-time-code" />
        </p>
        <script>
        jQuery(document).ready(function($) {
            // Show 2FA field if username is filled
            $('form.woocommerce-form-login').on('submit', function(e) {
                var username = $('#username').val();
                
                if (username) {
                    e.preventDefault();
                    
                    $.ajax({
                        url: '<?php echo \admin_url('admin-ajax.php'); ?>',
                        type: 'POST',
                        data: {
                            action: 'kura_ai_check_2fa_required',
                            username: username,
                            nonce: '<?php echo \wp_create_nonce('kura_ai_2fa_nonce'); ?>'
                        },
                        success: function(response) {
                            if (response.success && response.data.required) {
                                $('.kura-ai-2fa-code').show();
                            } else {
                                $('form.woocommerce-form-login').off('submit').submit();
                            }
                        }
                    });
                }
            });
        });
        </script>
        <?php
    }

    /**
     * Verify WooCommerce 2FA code.
     *
     * @since    1.0.0
     * @param    WP_Error    $validation_error    WP_Error object.
     * @param    string      $username            Username.
     * @param    string      $password            Password.
     * @return   WP_Error                         WP_Error object.
     */
    public function verify_woocommerce_2fa($validation_error, $username, $password) {
        $settings = $this->login_security->get_settings();
        
        // Check if 2FA is enabled
        if (!isset($settings['enable_2fa']) || !$settings['enable_2fa']) {
            return $validation_error;
        }
        
        $user = \get_user_by('login', $username);
        
        if (!$user) {
            return $validation_error;
        }
        
        // Check if 2FA is required for the user
        $user_2fa_enabled = \get_user_meta($user->ID, 'kura_ai_2fa_enabled', true);
        
        if (!$user_2fa_enabled) {
            return $validation_error;
        }
        
        // Check if 2FA code is provided
        if (!isset($_POST['kura_ai_2fa_code']) || empty($_POST['kura_ai_2fa_code'])) {
            return new \WP_Error('2fa_required', \__('Authentication code is required.', 'kura-ai'));
        }
        
        $code = \sanitize_text_field($_POST['kura_ai_2fa_code']);
        
        if (!$this->verify_2fa_code($user->ID, $code)) {
            return new \WP_Error('invalid_2fa_code', \__('Invalid authentication code. Please try again.', 'kura-ai'));
        }
        
        return $validation_error;
    }

    /**
     * AJAX handler for generating a 2FA secret.
     *
     * @since    1.0.0
     */
    public function ajax_generate_2fa_secret() {
        \check_ajax_referer('kura_ai_2fa_nonce', 'nonce');
        
        $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
        
        if (!\current_user_can('edit_user', $user_id)) {
            \wp_send_json_error(array('message' => \__('You do not have permission to perform this action.', 'kura-ai')));
        }
        
        $secret = $this->generate_secret();
        \update_user_meta($user_id, 'kura_ai_2fa_secret', $secret);
        \update_user_meta($user_id, 'kura_ai_2fa_verified', false);
        
        $site_name = \get_bloginfo('name');
        $user = \get_user_by('id', $user_id);
        $user_email = $user->user_email;
        $qr_code_url = 'https://chart.googleapis.com/chart?chs=200x200&chld=M|0&cht=qr&chl=' . \urlencode("otpauth://totp/{$user_email}?secret={$secret}&issuer={$site_name}");
        
        \wp_send_json_success(array(
            'secret' => $secret,
            'formatted_secret' => $this->format_secret_key($secret),
            'qr_code_url' => $qr_code_url
        ));
    }

    /**
     * AJAX handler for verifying a 2FA code.
     *
     * @since    1.0.0
     */
    public function ajax_verify_2fa_code() {
        \check_ajax_referer('kura_ai_2fa_nonce', 'nonce');
        
        $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
        $code = isset($_POST['code']) ? \sanitize_text_field($_POST['code']) : '';
        
        if (!\current_user_can('edit_user', $user_id)) {
            \wp_send_json_error(array('message' => \__('You do not have permission to perform this action.', 'kura-ai')));
        }
        
        $secret = \get_user_meta($user_id, 'kura_ai_2fa_secret', true);
        
        if (!$secret) {
            \wp_send_json_error(array('message' => \__('No secret key found. Please generate a new secret key.', 'kura-ai')));
        }
        
        if ($this->verify_totp_code($secret, $code)) {
            \update_user_meta($user_id, 'kura_ai_2fa_verified', true);
            \wp_send_json_success(array('message' => \__('Verification successful.', 'kura-ai')));
        } else {
            \wp_send_json_error(array('message' => \__('Invalid verification code. Please try again.', 'kura-ai')));
        }
    }

    /**
     * AJAX handler for generating backup codes.
     *
     * @since    1.0.0
     */
    public function ajax_generate_backup_codes() {
        \check_ajax_referer('kura_ai_2fa_nonce', 'nonce');
        
        $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
        
        if (!\current_user_can('edit_user', $user_id)) {
            \wp_send_json_error(array('message' => \__('You do not have permission to perform this action.', 'kura-ai')));
        }
        
        $backup_codes = $this->generate_backup_codes($user_id);
        
        \wp_send_json_success(array('backup_codes' => $backup_codes));
    }
}