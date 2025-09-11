<?php
/**
 * CAPTCHA functionality.
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

// Include WordPress functions if they don't exist
if (!function_exists('\wp_rand')) {
    require_once ABSPATH . 'wp-includes/pluggable.php';
}

if (!function_exists('\wp_remote_post')) {
    require_once ABSPATH . 'wp-includes/http.php';
}

/**
 * Class for handling CAPTCHA functionality
 *
 * @since      1.0.0
 * @package    Kura_AI
 * @subpackage Kura_AI/includes
 */
class Kura_AI_Captcha {

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
        
        // Initialize the CAPTCHA functionality
        $this->init();
    }

    /**
     * Initialize the CAPTCHA functionality.
     *
     * @since    1.0.0
     */
    public function init() {
        // Register hooks for CAPTCHA
        \add_action('login_form', array($this, 'add_captcha_to_login_form'));
        \add_filter('authenticate', array($this, 'verify_captcha'), 30, 3);
        
        // Register hooks for WooCommerce integration if WooCommerce is active
        if ($this->is_woocommerce_active()) {
            \add_action('woocommerce_login_form', array($this, 'add_captcha_to_woocommerce_login_form'));
            \add_filter('woocommerce_process_login_errors', array($this, 'verify_woocommerce_captcha'), 10, 3);
        }
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
     * Add CAPTCHA to login form.
     *
     * @since    1.0.0
     */
    public function add_captcha_to_login_form() {
        $settings = $this->login_security->get_settings();
        
        // Check if CAPTCHA is enabled
        if (!isset($settings['enable_captcha']) || !$settings['enable_captcha']) {
            return;
        }
        
        // Get CAPTCHA type
        $captcha_type = isset($settings['captcha_type']) ? $settings['captcha_type'] : 'math';
        
        switch ($captcha_type) {
            case 'recaptcha_v2':
                $this->add_recaptcha_v2($settings);
                break;
                
            case 'recaptcha_v3':
                $this->add_recaptcha_v3($settings);
                break;
                
            case 'math':
            default:
                $this->add_math_captcha();
                break;
        }
    }
    
    /**
     * Add math CAPTCHA to login form.
     *
     * @since    1.0.0
     */
    private function add_math_captcha() {
        // Generate a simple math CAPTCHA
        $num1 = \wp_rand(1, 10);
        $num2 = \wp_rand(1, 10);
        $captcha_answer = $num1 + $num2;
        
        // Store the CAPTCHA answer in a transient
        $captcha_key = \wp_generate_password(32, false);
        \set_transient('kura_ai_captcha_' . $captcha_key, $captcha_answer, 600); // 10 minutes expiration
        
        ?>
        <p>
            <label for="kura_ai_captcha"><?php \printf(\__('Security Check: %1$d + %2$d = ?', 'kura-ai'), $num1, $num2); ?></label>
            <input type="text" name="kura_ai_captcha" id="kura_ai_captcha" class="input" value="" size="20" autocomplete="off" />
            <input type="hidden" name="kura_ai_captcha_key" value="<?php echo \esc_attr($captcha_key); ?>" />
            <input type="hidden" name="kura_ai_captcha_type" value="math" />
        </p>
        <?php
    }
    
    /**
     * Add Google reCAPTCHA v2 to login form.
     *
     * @since    1.0.0
     * @param    array    $settings    The settings array.
     */
    private function add_recaptcha_v2($settings) {
        $site_key = isset($settings['recaptcha_site_key']) ? $settings['recaptcha_site_key'] : '';
        
        if (empty($site_key)) {
            echo '<p class="error">' . \__('reCAPTCHA Site Key is not configured. Please configure it in the Login Security settings.', 'kura-ai') . '</p>';
            return;
        }
        
        // Enqueue reCAPTCHA script
        \wp_enqueue_script('google-recaptcha', 'https://www.google.com/recaptcha/api.js', array(), null, true);
        
        ?>
        <div class="g-recaptcha" data-sitekey="<?php echo \esc_attr($site_key); ?>"></div>
        <input type="hidden" name="kura_ai_captcha_type" value="recaptcha_v2" />
        <?php
    }
    
    /**
     * Add Google reCAPTCHA v3 to login form.
     *
     * @since    1.0.0
     * @param    array    $settings    The settings array.
     */
    private function add_recaptcha_v3($settings) {
        $site_key = isset($settings['recaptcha_site_key']) ? $settings['recaptcha_site_key'] : '';
        
        if (empty($site_key)) {
            echo '<p class="error">' . \__('reCAPTCHA Site Key is not configured. Please configure it in the Login Security settings.', 'kura-ai') . '</p>';
            return;
        }
        
        // Enqueue reCAPTCHA script
        \wp_enqueue_script('google-recaptcha-v3', 'https://www.google.com/recaptcha/api.js?render=' . \esc_attr($site_key), array(), null, true);
        
        ?>
        <input type="hidden" id="g-recaptcha-response" name="g-recaptcha-response">
        <input type="hidden" name="kura_ai_captcha_type" value="recaptcha_v3" />
        <script>
        grecaptcha.ready(function() {
            grecaptcha.execute('<?php echo \esc_js($site_key); ?>', {action: 'login'}).then(function(token) {
                document.getElementById('g-recaptcha-response').value = token;
            });
        });
        </script>
        <?php
    }

    /**
     * Verify CAPTCHA on login form.
     *
     * @since    1.0.0
     * @param    WP_User|WP_Error    $user        WP_User object or WP_Error.
     * @param    string              $username    Username.
     * @param    string              $password    Password.
     * @return   WP_User|WP_Error                 WP_User object if CAPTCHA is valid, WP_Error otherwise.
     */
    public function verify_captcha($user, $username, $password) {
        // Skip CAPTCHA verification if the user is already authenticated or if there's an error
        if (is_wp_error($user) || empty($username) || empty($password)) {
            return $user;
        }
        
        $settings = $this->login_security->get_settings();
        
        // Check if CAPTCHA is enabled
        if (!isset($settings['enable_captcha']) || !$settings['enable_captcha']) {
            return $user;
        }
        
        // Get CAPTCHA type
        $captcha_type = isset($_POST['kura_ai_captcha_type']) ? sanitize_text_field($_POST['kura_ai_captcha_type']) : 'math';
        
        switch ($captcha_type) {
            case 'recaptcha_v2':
                return $this->verify_recaptcha_v2($user, $settings);
                
            case 'recaptcha_v3':
                return $this->verify_recaptcha_v3($user, $settings);
                
            case 'math':
            default:
                return $this->verify_math_captcha($user);
        }
    }
    
    /**
     * Verify math CAPTCHA.
     *
     * @since    1.0.0
     * @param    WP_User|WP_Error    $user    WP_User object or WP_Error.
     * @return   WP_User|WP_Error             WP_User object if CAPTCHA is valid, WP_Error otherwise.
     */
    private function verify_math_captcha($user) {
        // Check if CAPTCHA is provided
        if (!isset($_POST['kura_ai_captcha']) || !isset($_POST['kura_ai_captcha_key'])) {
            return new \WP_Error('captcha_required', \__('<strong>ERROR</strong>: Please complete the security check.', 'kura-ai'));
        }
        
        $captcha_answer = \sanitize_text_field($_POST['kura_ai_captcha']);
        $captcha_key = \sanitize_text_field($_POST['kura_ai_captcha_key']);
        
        // Get the stored CAPTCHA answer
        $stored_answer = \get_transient('kura_ai_captcha_' . $captcha_key);
        
        // Delete the transient to prevent reuse
        \delete_transient('kura_ai_captcha_' . $captcha_key);
        
        if (!$stored_answer || \intval($captcha_answer) !== \intval($stored_answer)) {
            return new \WP_Error('captcha_invalid', \__('<strong>ERROR</strong>: Incorrect security check answer. Please try again.', 'kura-ai'));
        }
        
        return $user;
    }
    
    /**
     * Verify Google reCAPTCHA v2.
     *
     * @since    1.0.0
     * @param    WP_User|WP_Error    $user       WP_User object or WP_Error.
     * @param    array               $settings   The settings array.
     * @return   WP_User|WP_Error                WP_User object if CAPTCHA is valid, WP_Error otherwise.
     */
    private function verify_recaptcha_v2($user, $settings) {
        // Check if reCAPTCHA response is provided
        if (!isset($_POST['g-recaptcha-response'])) {
            return new \WP_Error('captcha_missing', \__('<strong>ERROR</strong>: Please complete the reCAPTCHA.', 'kura-ai'));
        }
        
        $recaptcha_response = \sanitize_text_field($_POST['g-recaptcha-response']);
        $secret_key = isset($settings['recaptcha_secret_key']) ? $settings['recaptcha_secret_key'] : '';
        
        if (empty($secret_key)) {
            // Log the error but allow login if the admin hasn't configured reCAPTCHA properly
            \error_log('Kura AI: reCAPTCHA Secret Key is not configured.');
            return $user;
        }
        
        // Verify with Google reCAPTCHA API
        $verify_response = \wp_remote_post('https://www.google.com/recaptcha/api/siteverify', [
            'body' => [
                'secret' => $secret_key,
                'response' => $recaptcha_response,
                'remoteip' => $_SERVER['REMOTE_ADDR']
            ]
        ]);
        
        if (\is_wp_error($verify_response)) {
            \error_log('Kura AI: reCAPTCHA verification failed: ' . $verify_response->get_error_message());
            return $user; // Allow login if verification fails due to connection issues
        }
        
        $response_body = \wp_remote_retrieve_body($verify_response);
        $response_data = \json_decode($response_body, true);
        
        if (!isset($response_data['success']) || !$response_data['success']) {
            $error_codes = isset($response_data['error-codes']) ? \implode(', ', $response_data['error-codes']) : 'unknown';
            \error_log('Kura AI: reCAPTCHA verification failed with error codes: ' . $error_codes);
            return new \WP_Error('captcha_invalid', \__('<strong>ERROR</strong>: reCAPTCHA verification failed. Please try again.', 'kura-ai'));
        }
        
        return $user;
    }
    
    /**
     * Verify Google reCAPTCHA v3.
     *
     * @since    1.0.0
     * @param    WP_User|WP_Error    $user       WP_User object or WP_Error.
     * @param    array               $settings   The settings array.
     * @return   WP_User|WP_Error                WP_User object if CAPTCHA is valid, WP_Error otherwise.
     */
    private function verify_recaptcha_v3($user, $settings) {
        // Check if reCAPTCHA response is provided
        if (!isset($_POST['g-recaptcha-response'])) {
            return new \WP_Error('captcha_missing', \__('<strong>ERROR</strong>: reCAPTCHA verification failed. Please try again.', 'kura-ai'));
        }
        
        $recaptcha_response = \sanitize_text_field($_POST['g-recaptcha-response']);
        $secret_key = isset($settings['recaptcha_secret_key']) ? $settings['recaptcha_secret_key'] : '';
        
        if (empty($secret_key)) {
            // Log the error but allow login if the admin hasn't configured reCAPTCHA properly
            error_log('Kura AI: reCAPTCHA Secret Key is not configured.');
            return $user;
        }
        
        // Verify with Google reCAPTCHA API
        $verify_response = wp_remote_post('https://www.google.com/recaptcha/api/siteverify', [
            'body' => [
                'secret' => $secret_key,
                'response' => $recaptcha_response,
                'remoteip' => $_SERVER['REMOTE_ADDR']
            ]
        ]);
        
        if (is_wp_error($verify_response)) {
            error_log('Kura AI: reCAPTCHA verification failed: ' . $verify_response->get_error_message());
            return $user; // Allow login if verification fails due to connection issues
        }
        
        $response_body = wp_remote_retrieve_body($verify_response);
        $response_data = json_decode($response_body, true);
        
        if (!isset($response_data['success']) || !$response_data['success']) {
            $error_codes = isset($response_data['error-codes']) ? implode(', ', $response_data['error-codes']) : 'unknown';
            error_log('Kura AI: reCAPTCHA verification failed with error codes: ' . $error_codes);
            return new \WP_Error('captcha_invalid', __('<strong>ERROR</strong>: reCAPTCHA verification failed. Please try again.', 'kura-ai'));
        }
        
        // For v3, also check the score (0.0 is bot, 1.0 is human)
        if (isset($response_data['score'])) {
            $score = \floatval($response_data['score']);
            $min_score = isset($settings['recaptcha_v3_threshold']) ? \floatval($settings['recaptcha_v3_threshold']) : 0.5;
            
            if ($score < $min_score) {
                \error_log('Kura AI: reCAPTCHA v3 score too low: ' . $score . ' (threshold: ' . $min_score . ')');
                return new \WP_Error('captcha_score_low', \__('<strong>ERROR</strong>: reCAPTCHA verification failed. Please try again or contact the site administrator.', 'kura-ai'));
            }
        }
        
        return $user;
    }

    /**
     * Add CAPTCHA to WooCommerce login form.
     *
     * @since    1.0.0
     */
    public function add_captcha_to_woocommerce_login_form() {
        $settings = $this->login_security->get_settings();
        
        // Check if CAPTCHA is enabled
        if (!isset($settings['enable_captcha']) || !$settings['enable_captcha']) {
            return;
        }
        
        // Generate a simple math CAPTCHA
        $num1 = \wp_rand(1, 10);
        $num2 = \wp_rand(1, 10);
        $captcha_answer = $num1 + $num2;
        
        // Store the CAPTCHA answer in a transient
        $captcha_key = \wp_generate_password(32, false);
        \set_transient('kura_ai_captcha_' . $captcha_key, $captcha_answer, 600); // 10 minutes expiration
        
        ?>
        <p class="form-row form-row-wide">
            <label for="kura_ai_captcha"><?php \printf(\__('Security Check: %1$d + %2$d = ?', 'kura-ai'), $num1, $num2); ?> <span class="required">*</span></label>
            <input type="text" class="input-text" name="kura_ai_captcha" id="kura_ai_captcha" autocomplete="off" />
            <input type="hidden" name="kura_ai_captcha_key" value="<?php echo \esc_attr($captcha_key); ?>" />
        </p>
        <?php
    }

    /**
     * Verify CAPTCHA on WooCommerce login form.
     *
     * @since    1.0.0
     * @param    WP_Error    $validation_error    WP_Error object.
     * @param    string      $username            Username.
     * @param    string      $password            Password.
     * @return   WP_Error                         WP_Error object.
     */
    public function verify_woocommerce_captcha($validation_error, $username, $password) {
        $settings = $this->login_security->get_settings();
        
        // Check if CAPTCHA is enabled
        if (!isset($settings['enable_captcha']) || !$settings['enable_captcha']) {
            return $validation_error;
        }
        
        // Check if CAPTCHA is provided
        if (!isset($_POST['kura_ai_captcha']) || !isset($_POST['kura_ai_captcha_key'])) {
            return new \WP_Error('captcha_required', \__('Please complete the security check.', 'kura-ai'));
        }
        
        $captcha_answer = \sanitize_text_field($_POST['kura_ai_captcha']);
        $captcha_key = \sanitize_text_field($_POST['kura_ai_captcha_key']);
        
        // Get the stored CAPTCHA answer
        $stored_answer = \get_transient('kura_ai_captcha_' . $captcha_key);
        
        // Delete the transient to prevent reuse
        \delete_transient('kura_ai_captcha_' . $captcha_key);
        
        if (!$stored_answer || \intval($captcha_answer) !== \intval($stored_answer)) {
            return new \WP_Error('captcha_invalid', \__('Incorrect security check answer. Please try again.', 'kura-ai'));
        }
        
        return $validation_error;
    }
}