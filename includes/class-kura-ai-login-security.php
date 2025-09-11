<?php
/**
 * Login Security functionality.
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
 * Class for handling login security features including 2FA, CAPTCHA, and password security
 *
 * @since      1.0.0
 * @package    Kura_AI
 * @subpackage Kura_AI/includes
 */
class Kura_AI_Login_Security {

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
     * Plugin settings.
     *
     * @since    1.0.0
     * @access   private
     * @var      array    $settings    Plugin settings.
     */
    private $settings;

    /**
     * The 2FA instance.
     *
     * @since    1.0.0
     * @access   private
     * @var      Kura_AI_2FA    $two_factor_auth    The 2FA instance.
     */
    private $two_factor_auth;

    /**
     * The CAPTCHA instance.
     *
     * @since    1.0.0
     * @access   private
     * @var      Kura_AI_CAPTCHA    $captcha    The CAPTCHA instance.
     */
    private $captcha;

    /**
     * The Password Security instance.
     *
     * @since    1.0.0
     * @access   private
     * @var      Kura_AI_Password_Security    $password_security    The Password Security instance.
     */
    private $password_security;

    /**
     * The XML-RPC Security instance.
     *
     * @since    1.0.0
     * @access   private
     * @var      Kura_AI_XMLRPC_Security    $xmlrpc_security    The XML-RPC Security instance.
     */
    private $xmlrpc_security;
    
    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param    string    $plugin_name       The name of this plugin.
     * @param    string    $version    The version of this plugin.
     */
    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        $this->settings = \get_option('kura_ai_settings', array());
        
        // Initialize the login security features
        $this->init();
    }
    
    /**
     * Initialize the WooCommerce integration.
     *
     * @since    1.1.0
     */
    private function init_woocommerce_integration() {
        if ($this->is_woocommerce_active()) {
            require_once \plugin_dir_path(\dirname(__FILE__)) . 'includes/class-kura-ai-woocommerce.php';
            new Kura_AI_WooCommerce($this->plugin_name, $this->version, $this);
        }
    }

    /**
     * Initialize the login security features.
     *
     * @since    1.0.0
     */
    public function init() {
        // Initialize 2FA
        $this->init_2fa();
        
        // Initialize CAPTCHA
        $this->init_captcha();
        
        // Initialize Password Security
        $this->init_password_security();
        
        // Initialize XML-RPC Security
        $this->init_xmlrpc_security();
        
        // Initialize WooCommerce integration
        $this->init_woocommerce_integration();
        
        // Register settings
        \add_action('admin_init', array($this, 'register_settings'));
    }
    
    /**
     * Initialize 2FA.
     *
     * @since    1.0.0
     */
    private function init_2fa() {
        require_once \plugin_dir_path(\dirname(__FILE__)) . 'includes/class-kura-ai-2fa.php';
        $this->two_factor_auth = new Kura_AI_2FA($this->plugin_name, $this->version, $this);
    }

    /**
     * Initialize CAPTCHA.
     *
     * @since    1.0.0
     */
    private function init_captcha() {
        require_once \plugin_dir_path(\dirname(__FILE__)) . 'includes/class-kura-ai-captcha.php';
        $this->captcha = new Kura_AI_CAPTCHA($this->plugin_name, $this->version, $this);
    }

    /**
     * Initialize Password Security.
     *
     * @since    1.0.0
     */
    private function init_password_security() {
        require_once \plugin_dir_path(\dirname(__FILE__)) . 'includes/class-kura-ai-password-security.php';
        $this->password_security = new Kura_AI_Password_Security($this->plugin_name, $this->version, $this);
    }

    /**
     * Initialize XML-RPC Security.
     *
     * @since    1.0.0
     */
    private function init_xmlrpc_security() {
        require_once \plugin_dir_path(\dirname(__FILE__)) . 'includes/class-kura-ai-xmlrpc-security.php';
        $this->xmlrpc_security = new Kura_AI_XMLRPC_Security($this->plugin_name, $this->version, $this, $this->two_factor_auth);
    }

    /**
     * Check if WooCommerce is active.
     *
     * @since    1.0.0
     * @return   bool    True if WooCommerce is active, false otherwise.
     */
    private function is_woocommerce_active() {
        return \class_exists('WooCommerce');
    }

    /**
     * Register settings.
     *
     * @since    1.0.0
     */
    public function register_settings() {
        \register_setting(
            'kura_ai_login_security',
            'kura_ai_login_security_settings',
            array($this, 'sanitize_settings')
        );
    }

    /**
     * Sanitize settings.
     *
     * @since    1.0.0
     * @param    array    $input    The input array.
     * @return   array              The sanitized input array.
     */
    public function sanitize_settings($input) {
        $sanitized_input = array();
        
        // 2FA settings
        $sanitized_input['enable_2fa'] = isset($input['enable_2fa']) ? true : false;
        
        if (isset($input['required_roles']) && \is_array($input['required_roles'])) {
            $sanitized_input['required_roles'] = \array_map('\\sanitize_text_field', $input['required_roles']);
        } else {
            $sanitized_input['required_roles'] = array('administrator');
        }
        
        // CAPTCHA settings
        $sanitized_input['enable_captcha'] = isset($input['enable_captcha']) ? true : false;
        $sanitized_input['captcha_type'] = isset($input['captcha_type']) ? \sanitize_text_field($input['captcha_type']) : 'math';
        $sanitized_input['recaptcha_site_key'] = isset($input['recaptcha_site_key']) ? \sanitize_text_field($input['recaptcha_site_key']) : '';
        $sanitized_input['recaptcha_secret_key'] = isset($input['recaptcha_secret_key']) ? \sanitize_text_field($input['recaptcha_secret_key']) : '';
        $sanitized_input['recaptcha_v3_threshold'] = isset($input['recaptcha_v3_threshold']) ? \sanitize_text_field($input['recaptcha_v3_threshold']) : '0.5';
        
        // Password Security settings
        $sanitized_input['enable_password_security'] = isset($input['enable_password_security']) ? true : false;
        $sanitized_input['min_password_length'] = isset($input['min_password_length']) ? \intval($input['min_password_length']) : 12;
        $sanitized_input['require_special_chars'] = isset($input['require_special_chars']) ? true : false;
        $sanitized_input['check_pwned_passwords'] = isset($input['check_pwned_passwords']) ? true : false;
        
        // XML-RPC Security settings
        $sanitized_input['disable_xmlrpc'] = isset($input['disable_xmlrpc']) ? true : false;
        $sanitized_input['block_multiauth'] = isset($input['block_multiauth']) ? true : false;
        $sanitized_input['enforce_2fa_xmlrpc'] = isset($input['enforce_2fa_xmlrpc']) ? true : false;
        
        return $sanitized_input;
    }

    /**
     * Update settings.
     *
     * @since    1.0.0
     * @param    array    $new_settings    The new settings array.
     * @return   boolean                   Whether the settings were updated.
     */
    public function update_settings($new_settings) {
        // Sanitize settings
        $sanitized_settings = $this->sanitize_settings($new_settings);
        
        // Update the option in the database
        $updated = \update_option('kura_ai_login_security_settings', $sanitized_settings);
        
        // Refresh local settings
        if ($updated) {
            $this->settings = $this->get_settings();
        }
        
        return $updated;
    }

    /**
     * Get settings.
     *
     * @since    1.0.0
     * @return   array    The settings array.
     */
    public function get_settings() {
        $defaults = array(
            'enable_2fa' => false,
            'required_roles' => array('administrator'),
            'enable_captcha' => false,
            'captcha_type' => 'math',
            'enable_password_security' => false,
            'min_password_length' => 12,
            'require_special_chars' => false,
            'check_pwned_passwords' => false,
            'disable_xmlrpc' => false,
            'block_multiauth' => false,
            'enforce_2fa_xmlrpc' => false
        );
        
        $settings = \get_option('kura_ai_login_security_settings', array());
        
        // Merge settings with defaults
        return \array_merge($defaults, $settings);
    }

    /**
     * Get the 2FA instance.
     *
     * @since    1.0.0
     * @return   Kura_AI_2FA    The 2FA instance.
     */
    public function get_2fa() {
        return $this->two_factor_auth;
    }

    /**
     * Get the CAPTCHA instance.
     *
     * @since    1.0.0
     * @return   Kura_AI_CAPTCHA    The CAPTCHA instance.
     */
    public function get_captcha() {
        return $this->captcha;
    }

    /**
     * Get the Password Security instance.
     *
     * @since    1.0.0
     * @return   Kura_AI_Password_Security    The Password Security instance.
     */
    public function get_password_security() {
        return $this->password_security;
    }

    /**
     * Get the XML-RPC Security instance.
     *
     * @since    1.0.0
     * @return   Kura_AI_XMLRPC_Security    The XML-RPC Security instance.
     */
    public function get_xmlrpc_security() {
        return $this->xmlrpc_security;
    }
}