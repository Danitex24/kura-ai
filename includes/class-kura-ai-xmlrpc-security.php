<?php
/**
 * XML-RPC Security functionality.
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
 * Class for handling XML-RPC security features
 *
 * @since      1.0.0
 * @package    Kura_AI
 * @subpackage Kura_AI/includes
 */
class Kura_AI_XMLRPC_Security {

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
     * The 2FA instance.
     *
     * @since    1.0.0
     * @access   private
     * @var      Kura_AI_2FA    $tfa    The 2FA instance.
     */
    private $tfa;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param    string                   $plugin_name       The name of this plugin.
     * @param    string                   $version           The version of this plugin.
     * @param    Kura_AI_Login_Security   $login_security    The login security instance.
     * @param    Kura_AI_2FA              $tfa               The 2FA instance.
     */
    public function __construct($plugin_name, $version, $login_security, $tfa) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        $this->login_security = $login_security;
        $this->tfa = $tfa;
        
        // Initialize the XML-RPC security features
        $this->init();
    }

    /**
     * Initialize the XML-RPC security features.
     *
     * @since    1.0.0
     */
    public function init() {
        // Register hooks for XML-RPC security
        \add_filter('xmlrpc_enabled', array($this, 'maybe_disable_xmlrpc'));
        \add_action('xmlrpc_call', array($this, 'check_xmlrpc_authentication'));
        
        // Add filter to block certain XML-RPC methods
        \add_filter('xmlrpc_methods', array($this, 'block_xmlrpc_methods'));
    }

    /**
     * Maybe disable XML-RPC.
     *
     * @since    1.0.0
     * @param    bool    $enabled    Whether XML-RPC is enabled.
     * @return   bool                Whether XML-RPC should be enabled.
     */
    public function maybe_disable_xmlrpc($enabled) {
        $settings = $this->login_security->get_settings();
        
        if (isset($settings['disable_xmlrpc']) && $settings['disable_xmlrpc']) {
            return false;
        }
        
        return $enabled;
    }

    /**
     * Check XML-RPC authentication.
     *
     * @since    1.0.0
     * @param    string    $method    XML-RPC method.
     */
    public function check_xmlrpc_authentication($method) {
        $settings = $this->login_security->get_settings();
        
        // Check if 2FA is required for XML-RPC
        if (!isset($settings['enforce_2fa_on_xmlrpc']) || !$settings['enforce_2fa_on_xmlrpc']) {
            return;
        }
        
        // List of XML-RPC methods that require authentication
        $auth_methods = array(
            'wp.getPost',
            'wp.getPosts',
            'wp.newPost',
            'wp.editPost',
            'wp.deletePost',
            'wp.getMediaItem',
            'wp.getMediaLibrary',
            'wp.uploadFile',
            'blogger.getUsersBlogs',
            'metaWeblog.newPost',
            'metaWeblog.editPost',
            'metaWeblog.getPost',
            'metaWeblog.getRecentPosts',
            'metaWeblog.deletePost',
            'metaWeblog.getCategories',
            'metaWeblog.newMediaObject',
        );
        
        // Check if the method requires authentication
        if (!\in_array($method, $auth_methods)) {
            return;
        }
        
        // Get the XML-RPC server global variable
        global $wp_xmlrpc_server;
        
        // Check if the XML-RPC server is available
        if (!$wp_xmlrpc_server || !is_object($wp_xmlrpc_server) || !isset($wp_xmlrpc_server->message)) {
            return;
        }
        
        // Get the username and password from the XML-RPC request
        $username = '';
        $password = '';
        
        // Different XML-RPC methods have different parameter positions for credentials
        if ($method === 'wp.getUsersBlogs') {
            $username = $wp_xmlrpc_server->message->params[0];
            $password = $wp_xmlrpc_server->message->params[1];
        } elseif (\in_array($method, array('wp.getPost', 'wp.getPosts', 'wp.newPost', 'wp.editPost', 'wp.deletePost'))) {
            $username = $wp_xmlrpc_server->message->params[1];
            $password = $wp_xmlrpc_server->message->params[2];
        } elseif (\in_array($method, array('metaWeblog.newPost', 'metaWeblog.editPost', 'metaWeblog.getPost', 'metaWeblog.getRecentPosts'))) {
            $username = $wp_xmlrpc_server->message->params[1];
            $password = $wp_xmlrpc_server->message->params[2];
        }
        
        // If we couldn't extract the username and password, return
        if (empty($username) || empty($password)) {
            return;
        }
        
        // Check if the password contains a 2FA code
        // Format: password + 2FA code (e.g., mypassword123456)
        if (strlen($password) <= 6) {
            $this->xmlrpc_error(__('Two-factor authentication is required for XML-RPC requests.', 'kura-ai'));
            return;
        }
        
        // Extract the 2FA code from the password
        $code = substr($password, -6);
        $actual_password = substr($password, 0, -6);
        
        // Authenticate the user
        $user = wp_authenticate($username, $actual_password);
        
        if (is_wp_error($user)) {
            return;
        }
        
        // Check if 2FA is enabled for the user
        $user_2fa_enabled = get_user_meta($user->ID, 'kura_ai_2fa_enabled', true);
        
        if (!$user_2fa_enabled) {
            return;
        }
        
        // Verify the 2FA code
        if (!$this->tfa->verify_2fa_code($user->ID, $code)) {
            $this->xmlrpc_error(__('Invalid two-factor authentication code.', 'kura-ai'));
        }
    }

    /**
     * Block certain XML-RPC methods.
     *
     * @since    1.0.0
     * @param    array    $methods    XML-RPC methods.
     * @return   array                Filtered XML-RPC methods.
     */
    public function block_xmlrpc_methods($methods) {
        $settings = $this->login_security->get_settings();
        
        // Check if XML-RPC is disabled
        if (isset($settings['disable_xmlrpc']) && $settings['disable_xmlrpc']) {
            return array();
        }
        
        // Check if 2FA is required for XML-RPC
        if (!isset($settings['enforce_2fa_on_xmlrpc']) || !$settings['enforce_2fa_on_xmlrpc']) {
            return $methods;
        }
        
        // Block system.multicall to prevent XML-RPC attacks
        unset($methods['system.multicall']);
        
        return $methods;
    }

    /**
     * Output an XML-RPC error.
     *
     * @since    1.0.0
     * @param    string    $message    Error message.
     */
    private function xmlrpc_error($message) {
        $xml = '<?xml version="1.0"?>
<methodResponse>
  <fault>
    <value>
      <struct>
        <member>
          <name>faultCode</name>
          <value><int>403</int></value>
        </member>
        <member>
          <name>faultString</name>
          <value><string>' . esc_html($message) . '</string></value>
        </member>
      </struct>
    </value>
  </fault>
</methodResponse>';
        
        header('Content-Type: text/xml');
        echo $xml;
        exit;
    }
}