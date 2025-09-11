<?php
/**
 * Password Security functionality.
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
 * Class for handling password security features
 *
 * @since      1.0.0
 * @package    Kura_AI
 * @subpackage Kura_AI/includes
 */
class Kura_AI_Password_Security {

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
        
        // Initialize the password security features
        $this->init();
    }

    /**
     * Initialize the password security features.
     *
     * @since    1.0.0
     */
    public function init() {
        // Register hooks for password security
        \add_action('user_profile_update_errors', array($this, 'validate_password_security'), 10, 3);
        \add_action('validate_password_reset', array($this, 'validate_password_reset'), 10, 2);
        \add_action('wp_authenticate_user', array($this, 'check_admin_password_security'), 10, 2);
        
        // Add password strength meter to registration and reset forms
        \add_action('wp_enqueue_scripts', array($this, 'enqueue_password_strength_meter'));
    }

    /**
     * Validate password security.
     *
     * @since    1.0.0
     * @param    WP_Error    $errors    WP_Error object.
     * @param    bool        $update    Whether this is an update or a new user.
     * @param    object      $user      User object.
     */
    public function validate_password_security($errors, $update, $user) {
        $settings = $this->login_security->get_settings();
        
        // Check if password security is enabled
        if (!isset($settings['check_password_pwned']) || !$settings['check_password_pwned']) {
            return;
        }
        
        // Check if a password was provided
        if (empty($_POST['pass1'])) {
            return;
        }
        
        $password = $_POST['pass1'];
        
        // Check if the password has been pwned
        $pwned_count = $this->check_password_pwned($password);
        
        if ($pwned_count > 0) {
            $errors->add('pwned_password', sprintf(
                __('<strong>ERROR</strong>: This password has been found in %d data breaches. Please choose a more secure password.', 'kura-ai'),
                $pwned_count
            ));
        }
    }

    /**
     * Validate password reset.
     *
     * @since    1.0.0
     * @param    WP_Error    $errors    WP_Error object.
     * @param    WP_User     $user      WP_User object.
     */
    public function validate_password_reset($errors, $user) {
        $settings = $this->login_security->get_settings();
        
        // Check if password security is enabled
        if (!isset($settings['check_password_pwned']) || !$settings['check_password_pwned']) {
            return;
        }
        
        // Check if a password was provided
        if (empty($_POST['pass1'])) {
            return;
        }
        
        $password = $_POST['pass1'];
        
        // Check if the password has been pwned
        $pwned_count = $this->check_password_pwned($password);
        
        if ($pwned_count > 0) {
            $errors->add('pwned_password', sprintf(
                __('<strong>ERROR</strong>: This password has been found in %d data breaches. Please choose a more secure password.', 'kura-ai'),
                $pwned_count
            ));
        }
    }

    /**
     * Check admin password security.
     *
     * @since    1.0.0
     * @param    WP_User|WP_Error    $user        WP_User object or WP_Error.
     * @param    string              $password    User password.
     * @return   WP_User|WP_Error                 WP_User object if password is secure, WP_Error otherwise.
     */
    public function check_admin_password_security($user, $password) {
        if (\is_wp_error($user)) {
            return $user;
        }
        
        $settings = $this->login_security->get_settings();
        
        // Check if password security is enabled and if we should block admin logins
        if (!isset($settings['check_password_pwned']) || !$settings['check_password_pwned'] ||
            !isset($settings['block_admin_on_pwned']) || !$settings['block_admin_on_pwned']) {
            return $user;
        }
        
        // Check if the user is an administrator
        if (!\in_array('administrator', $user->roles)) {
            return $user;
        }
        
        // Check if the password has been pwned
        $pwned_count = $this->check_password_pwned($password);
        
        if ($pwned_count > 0) {
            // Set a flag to force password reset
            \update_user_meta($user->ID, 'kura_ai_force_password_reset', true);
            
            // Add a notice for the user
            \add_action('user_admin_notices', array($this, 'show_password_reset_notice'));
            \add_action('admin_notices', array($this, 'show_password_reset_notice'));
            
            // Return an error if the password has been pwned
            return new \WP_Error('pwned_password', sprintf(
                __('<strong>ERROR</strong>: Your password has been found in %d data breaches. For security reasons, you must reset your password before logging in.', 'kura-ai'),
                $pwned_count
            ));
        }
        
        return $user;
    }

    /**
     * Show password reset notice.
     *
     * @since    1.0.0
     */
    public function show_password_reset_notice() {
        $user_id = get_current_user_id();
        $force_reset = get_user_meta($user_id, 'kura_ai_force_password_reset', true);
        
        if ($force_reset) {
            ?>
            <div class="error">
                <p>
                    <?php _e('Your password has been found in data breaches. Please reset your password immediately.', 'kura-ai'); ?>
                    <a href="<?php echo esc_url(wp_lostpassword_url()); ?>"><?php _e('Reset Password', 'kura-ai'); ?></a>
                </p>
            </div>
            <?php
        }
    }

    /**
     * Enqueue password strength meter.
     *
     * @since    1.0.0
     */
    public function enqueue_password_strength_meter() {
        $settings = $this->login_security->get_settings();
        
        // Check if password security is enabled
        if (!isset($settings['check_password_pwned']) || !$settings['check_password_pwned']) {
            return;
        }
        
        // Only enqueue on registration or reset password pages
        if (!is_page('register') && !isset($_GET['action']) && $_GET['action'] !== 'rp') {
            return;
        }
        
        wp_enqueue_script('password-strength-meter');
        wp_enqueue_script(
            'kura-ai-password-strength',
            plugin_dir_url(dirname(__FILE__)) . 'public/js/kura-ai-password-strength.js',
            array('jquery', 'password-strength-meter'),
            $this->version,
            true
        );
    }

    /**
     * Check if a password has been pwned.
     *
     * @since    1.0.0
     * @param    string    $password    The password to check.
     * @return   int                    The number of times the password has been pwned, 0 if not pwned or error.
     */
    public function check_password_pwned($password) {
        // Generate the SHA-1 hash of the password
        $hash = strtoupper(sha1($password));
        $prefix = substr($hash, 0, 5);
        $suffix = substr($hash, 5);
        
        // Make a request to the HaveIBeenPwned API
        $response = wp_remote_get('https://api.pwnedpasswords.com/range/' . $prefix, array(
            'timeout' => 10,
            'headers' => array(
                'User-Agent' => 'Kura-AI-WordPress-Plugin',
            ),
        ));
        
        if (is_wp_error($response)) {
            return 0;
        }
        
        $body = wp_remote_retrieve_body($response);
        $lines = explode("\n", $body);
        
        foreach ($lines as $line) {
            $parts = explode(':', $line);
            
            if (count($parts) === 2 && $parts[0] === $suffix) {
                return intval($parts[1]);
            }
        }
        
        return 0;
    }
}