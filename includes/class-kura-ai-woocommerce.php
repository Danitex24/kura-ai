<?php
/**
 * WooCommerce integration for Login Security.
 *
 * @link       https://kura.ai
 * @since      1.1.0
 *
 * @package    Kura_AI
 * @subpackage Kura_AI/includes
 */

namespace Kura_AI;

// If this file is called directly, abort.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * WooCommerce integration for Login Security.
 *
 * This class handles the integration with WooCommerce for 2FA and other login security features.
 *
 * @package    Kura_AI
 * @subpackage Kura_AI/includes
 * @author     Kura AI <support@kura.ai>
 */
class Kura_AI_WooCommerce {

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.1.0
     */
    public function __construct() {
        // Nothing to do here
    }

    /**
     * Initialize the WooCommerce integration.
     *
     * @since    1.1.0
     */
    public function init() {
        // Check if WooCommerce is active
        if (!$this->is_woocommerce_active()) {
            return;
        }

        // Add 2FA to WooCommerce login form
        \add_action('woocommerce_login_form', array($this, 'add_2fa_field_to_login_form'));
        \add_filter('woocommerce_process_login_errors', array($this, 'validate_2fa_code'), 10, 3);

        // Add 2FA to WooCommerce account page
        \add_action('woocommerce_edit_account_form', array($this, 'add_2fa_to_account_page'));
        \add_action('woocommerce_save_account_details', array($this, 'save_2fa_account_details'));

        // Add CAPTCHA to WooCommerce login form
        \add_action('woocommerce_login_form', array($this, 'add_captcha_to_login_form'));
        \add_filter('woocommerce_process_login_errors', array($this, 'validate_captcha'), 10, 3);

        // Add password strength meter to WooCommerce registration
        \add_action('woocommerce_register_form', array($this, 'add_password_strength_meter'));
        \add_filter('woocommerce_process_registration_errors', array($this, 'validate_password_strength'), 10, 4);
    }

    /**
     * Check if WooCommerce is active.
     *
     * @since    1.1.0
     * @return   bool    True if WooCommerce is active, false otherwise.
     */
    private function is_woocommerce_active() {
        return \class_exists('WooCommerce');
    }

    /**
     * Add 2FA field to WooCommerce login form.
     *
     * @since    1.1.0
     */
    public function add_2fa_field_to_login_form() {
        // Only show the field if 2FA is enabled in settings
        $settings = \get_option('kura_ai_login_security_settings', array());
        if (empty($settings['enable_2fa_woocommerce'])) {
            return;
        }

        // Add the 2FA field
        ?>
        <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
            <label for="kura_ai_2fa_code"><?php \esc_html_e('Two-Factor Authentication Code', 'kura-ai'); ?></label>
            <input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="kura_ai_2fa_code" id="kura_ai_2fa_code" autocomplete="off" />
            <span><em><?php \esc_html_e('If you have enabled 2FA for your account, enter the code from your authenticator app. Otherwise, leave this field empty.', 'kura-ai'); ?></em></span>
        </p>
        <?php
    }

    /**
     * Validate 2FA code during WooCommerce login.
     *
     * @since    1.1.0
     * @param    WP_Error    $validation_error    The validation error.
     * @param    string      $username            The username.
     * @param    string      $password            The password.
     * @return   WP_Error                         The validation error.
     */
    public function validate_2fa_code($validation_error, $username, $password) {
        // Only validate if 2FA is enabled in settings
        $settings = \get_option('kura_ai_login_security_settings', array());
        if (empty($settings['enable_2fa_woocommerce'])) {
            return $validation_error;
        }

        // Get the user
        $user = \get_user_by('login', $username);
        if (!$user) {
            return $validation_error;
        }

        // Check if 2FA is enabled for the user
        $is_2fa_enabled = \get_user_meta($user->ID, 'kura_ai_2fa_enabled', true);
        if (!$is_2fa_enabled) {
            return $validation_error;
        }

        // Get the 2FA code
        $code = isset($_POST['kura_ai_2fa_code']) ? \sanitize_text_field($_POST['kura_ai_2fa_code']) : '';

        // Validate the code
        $two_fa = new \Kura_AI\Kura_AI_2FA(null, null, null);
        if (!$two_fa->verify_code($user->ID, $code, true)) {
            return new \WP_Error('2fa_error', \esc_html__('Invalid two-factor authentication code.', 'kura-ai'));
        }

        return $validation_error;
    }

    /**
     * Add 2FA to WooCommerce account page.
     *
     * @since    1.1.0
     */
    public function add_2fa_to_account_page() {
        // Only show if 2FA is enabled in settings
        $settings = \get_option('kura_ai_login_security_settings', array());
        if (empty($settings['enable_2fa_woocommerce'])) {
            return;
        }

        // Get the current user
        $user = \wp_get_current_user();
        $user_id = $user->ID;

        // Get 2FA status for the user
        $is_2fa_enabled = \get_user_meta($user_id, 'kura_ai_2fa_enabled', true);
        $secret_key = \get_user_meta($user_id, 'kura_ai_2fa_secret', true);

        // Check if we need to generate a new secret key
        if (empty($secret_key)) {
            $two_fa = new \Kura_AI\Kura_AI_2FA();
            $secret_key = $two_fa->generate_secret_key();
            \update_user_meta($user_id, 'kura_ai_2fa_secret', $secret_key);
        }

        // Generate QR code URL
        $two_fa = new \Kura_AI\Kura_AI_2FA();
        $qr_code_url = $two_fa->get_qr_code_url($user->user_login, $secret_key, \get_bloginfo('name'));

        // Output the 2FA settings
        ?>
        <fieldset>
            <legend><?php \esc_html_e('Two-Factor Authentication', 'kura-ai'); ?></legend>

            <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
                <label for="kura_ai_2fa_enabled">
                    <input type="checkbox" name="kura_ai_2fa_enabled" id="kura_ai_2fa_enabled" value="1" <?php \checked($is_2fa_enabled, '1'); ?> />
                    <?php \esc_html_e('Enable two-factor authentication for your account', 'kura-ai'); ?>
                </label>
            </p>

            <div id="kura-ai-2fa-setup" class="kura-ai-2fa-container" <?php echo $is_2fa_enabled ? '' : 'style="display:none;"'; ?>>
                <p><?php \esc_html_e('Scan this QR code with your authenticator app (Google Authenticator, Authy, etc.)', 'kura-ai'); ?></p>
                
                <div class="kura-ai-2fa-qr-container">
                    <img src="<?php echo \esc_url($qr_code_url); ?>" alt="<?php \esc_attr_e('QR Code for 2FA', 'kura-ai'); ?>" />
                </div>
                
                <p><?php \esc_html_e('Or enter this code manually into your app:', 'kura-ai'); ?></p>
                <div class="kura-ai-2fa-secret"><?php echo \esc_html(\chunk_split($secret_key, 4, ' ')); ?></div>
                
                <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
                    <label for="kura_ai_2fa_code"><?php \esc_html_e('Verification Code', 'kura-ai'); ?></label>
                    <input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="kura_ai_2fa_code" id="kura_ai_2fa_code" autocomplete="off" />
                    <span><em><?php \esc_html_e('Enter the 6-digit code from your authenticator app to verify setup', 'kura-ai'); ?></em></span>
                </p>
            </div>
        </fieldset>

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
        });
        </script>
        <?php
    }

    /**
     * Save 2FA settings from WooCommerce account page.
     *
     * @since    1.1.0
     * @param    int    $user_id    The user ID.
     */
    public function save_2fa_account_details($user_id) {
        // Only process if 2FA is enabled in settings
        $settings = \get_option('kura_ai_login_security_settings', array());
        if (empty($settings['enable_2fa_woocommerce'])) {
            return;
        }

        // Get the 2FA enabled status
        $is_2fa_enabled = isset($_POST['kura_ai_2fa_enabled']) ? '1' : '0';

        // If enabling 2FA, verify the code
        if ($is_2fa_enabled === '1') {
            $code = isset($_POST['kura_ai_2fa_code']) ? \sanitize_text_field($_POST['kura_ai_2fa_code']) : '';
            
            // Verify the code
            $two_fa = new \Kura_AI\Kura_AI_2FA();
            if (!$two_fa->verify_code($user_id, $code)) {
                \wc_add_notice(\esc_html__('Invalid two-factor authentication code. 2FA has not been enabled.', 'kura-ai'), 'error');
                return;
            }
        }

        // Save the 2FA enabled status
        \update_user_meta($user_id, 'kura_ai_2fa_enabled', $is_2fa_enabled);

        // If enabling 2FA, generate backup codes if they don't exist
        if ($is_2fa_enabled === '1') {
            $backup_codes = \get_user_meta($user_id, 'kura_ai_2fa_backup_codes', true);
            if (empty($backup_codes)) {
                $two_fa = new \Kura_AI\Kura_AI_2FA();
                $backup_codes = $two_fa->generate_backup_codes();
                \update_user_meta($user_id, 'kura_ai_2fa_backup_codes', $backup_codes);
            }

            // Add notice about backup codes
            \wc_add_notice(\esc_html__('Two-factor authentication has been enabled. Please visit your WordPress profile page to view your backup codes.', 'kura-ai'), 'success');
        }
    }

    /**
     * Add CAPTCHA to WooCommerce login form.
     *
     * @since    1.1.0
     */
    public function add_captcha_to_login_form() {
        // Only show if CAPTCHA is enabled in settings
        $settings = \get_option('kura_ai_login_security_settings', array());
        if (empty($settings['enable_captcha_woocommerce'])) {
            return;
        }

        // Get the CAPTCHA type
        $captcha_type = isset($settings['captcha_type']) ? $settings['captcha_type'] : 'math';

        // Add the appropriate CAPTCHA
        $captcha = new \Kura_AI\Kura_AI_CAPTCHA();
        
        if ($captcha_type === 'recaptcha_v2') {
            $captcha->add_recaptcha_v2();
        } elseif ($captcha_type === 'recaptcha_v3') {
            $captcha->add_recaptcha_v3();
        } else {
            // Generate a random math captcha
            $captcha_id = 'wc_login_' . \wp_generate_password(12, false);
            \set_transient('kura_ai_captcha_' . $captcha_id, $captcha->generate_math_captcha(), 600);
            
            ?>
            <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
                <label for="kura_ai_captcha"><?php echo \esc_html($captcha->get_math_captcha_question($captcha_id)); ?></label>
                <input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="kura_ai_captcha" id="kura_ai_captcha" autocomplete="off" />
                <input type="hidden" name="kura_ai_captcha_id" value="<?php echo \esc_attr($captcha_id); ?>" />
            </p>
            <?php
        }
    }

    /**
     * Validate CAPTCHA during WooCommerce login.
     *
     * @since    1.1.0
     * @param    WP_Error    $validation_error    The validation error.
     * @param    string      $username            The username.
     * @param    string      $password            The password.
     * @return   WP_Error                         The validation error.
     */
    public function validate_captcha($validation_error, $username, $password) {
        // Only validate if CAPTCHA is enabled in settings
        $settings = \get_option('kura_ai_login_security_settings', array());
        if (empty($settings['enable_captcha_woocommerce'])) {
            return $validation_error;
        }

        // Get the CAPTCHA type
        $captcha_type = isset($settings['captcha_type']) ? $settings['captcha_type'] : 'math';

        // Validate the CAPTCHA
        $captcha = new \Kura_AI\Kura_AI_CAPTCHA();
        
        if ($captcha_type === 'recaptcha_v2') {
            $result = $captcha->verify_recaptcha_v2();
        } elseif ($captcha_type === 'recaptcha_v3') {
            $result = $captcha->verify_recaptcha_v3();
        } else {
            // Validate math captcha
            $captcha_id = isset($_POST['kura_ai_captcha_id']) ? \sanitize_text_field($_POST['kura_ai_captcha_id']) : '';
            $captcha_answer = isset($_POST['kura_ai_captcha']) ? \sanitize_text_field($_POST['kura_ai_captcha']) : '';
            
            $result = $captcha->verify_math_captcha($captcha_id, $captcha_answer);
        }

        if (\is_wp_error($result)) {
            return new \WP_Error('captcha_error', \esc_html__('CAPTCHA validation failed.', 'kura-ai'));
        }

        return $validation_error;
    }

    /**
     * Add password strength meter to WooCommerce registration form.
     *
     * @since    1.1.0
     */
    public function add_password_strength_meter() {
        // Only add if password security is enabled in settings
        $settings = \get_option('kura_ai_login_security_settings', array());
        if (empty($settings['enable_password_security_woocommerce'])) {
            return;
        }

        // Enqueue the password strength meter script
        \wp_enqueue_script('password-strength-meter');
        \wp_enqueue_style('dashicons');
        
        // Add our custom script
        \wp_enqueue_script(
            'kura-ai-password-strength',
            \plugin_dir_url(\dirname(__FILE__)) . 'assets/js/password-strength.js',
            array('jquery', 'password-strength-meter'),
            KURA_AI_VERSION,
            true
        );
        
        // Localize the script
        \wp_localize_script(
            'kura-ai-password-strength',
            'kura_ai_password_params',
            array(
                'min_password_strength' => 3,
                'i18n_password_error' => \esc_html__('Please enter a stronger password.', 'kura-ai'),
                'i18n_password_hint' => \esc_html__('Hint: The password should be at least 12 characters long. To make it stronger, use upper and lower case letters, numbers, and symbols like ! " ? $ % ^ & ).', 'kura-ai'),
            )
        );
    }

    /**
     * Validate password strength during WooCommerce registration.
     *
     * @since    1.1.0
     * @param    WP_Error    $validation_error    The validation error.
     * @param    string      $username            The username.
     * @param    string      $password            The password.
     * @param    string      $email               The email.
     * @return   WP_Error                         The validation error.
     */
    public function validate_password_strength($validation_error, $username, $password, $email) {
        // Only validate if password security is enabled in settings
        $settings = \get_option('kura_ai_login_security_settings', array());
        if (empty($settings['enable_password_security_woocommerce'])) {
            return $validation_error;
        }

        // Check password strength
        $password_security = new \Kura_AI\Kura_AI_Password_Security(null, null);
        $is_pwned = $password_security->is_password_pwned($password);

        if ($is_pwned) {
            return new \WP_Error('password_pwned', \esc_html__('This password has been found in data breaches. Please choose a different password.', 'kura-ai'));
        }

        return $validation_error;
    }
}