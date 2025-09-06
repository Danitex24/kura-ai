<?php
/**
 * The public-facing functionality of the plugin.
 *
 * @package    Kura_AI
 * @subpackage Kura_AI/public
 * @author     Daniel Abughdyer <daniel@danovatesolutions.org>
 */

namespace Kura_AI;

class Kura_AI_Public
{

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
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param    string    $plugin_name       The name of this plugin.
     * @param    string    $version    The version of this plugin.
     */
    public function __construct($plugin_name, $version)
    {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    /**
     * Register the stylesheets for the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function enqueue_styles()
    {
        \wp_enqueue_style(
            $this->plugin_name,
            \plugin_dir_url(__FILE__) . '../assets/css/kura-ai-public.css',
            array(),
            $this->version,
            'all',
        );
    }

    /**
     * Register the JavaScript for the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts()
    {
        \wp_enqueue_script(
            $this->plugin_name,
            \plugin_dir_url(__FILE__) . '../assets/js/kura-ai-public.js',
            array('jquery'),
            $this->version,
            false,
        );

        // Localize the script with PHP data for JS
        \wp_localize_script(
            $this->plugin_name, // Same handle as used in wp_enqueue_script()
            'kura_ai_public',  // JS object name
            [
                'ajax_url' => \admin_url('admin-ajax.php'),
                'nonce' => \wp_create_nonce('kura_ai_public_nonce')
            ],
        );
    }
    
    /**
     * Register AJAX actions for the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function register_ajax_actions()
    {
        // Register public AJAX actions
        \add_action('wp_ajax_kura_ai_dismiss_notice', array($this, 'ajax_dismiss_notice'));
        \add_action('wp_ajax_nopriv_kura_ai_dismiss_notice', array($this, 'ajax_dismiss_notice'));
        
        \add_action('wp_ajax_kura_ai_security_heartbeat', array($this, 'ajax_security_heartbeat'));
        \add_action('wp_ajax_nopriv_kura_ai_security_heartbeat', array($this, 'ajax_security_heartbeat'));
    }
    
    /**
     * Handle dismissing notices via AJAX
     *
     * @since  1.0.0
     * @access public
     * @return void
     */
    public function ajax_dismiss_notice() {
        try {
            // Verify nonce
            if (!\check_ajax_referer('kura_ai_public_nonce', 'nonce', false)) {
                \wp_send_json_error(\esc_html__('Invalid security token.', 'kura-ai'));
                return;
            }
            
            // Get notice ID
            $notice_id = isset($_POST['notice_id']) ? \sanitize_text_field(\wp_unslash($_POST['notice_id'])) : '';
            
            if (\empty($notice_id)) {
                \wp_send_json_error(\esc_html__('Missing notice ID.', 'kura-ai'));
                return;
            }
            
            // Store the dismissed notice in user meta or options table
            $user_id = \get_current_user_id();
            
            if ($user_id > 0) {
                // For logged-in users, store in user meta
                \update_user_meta($user_id, 'kura_ai_dismissed_notice_' . $notice_id, true);
            } else {
                // For non-logged in users, store in a cookie
                $expiration = time() + (30 * DAY_IN_SECONDS); // 30 days
                \setcookie('kura_ai_dismissed_notice_' . $notice_id, '1', $expiration, COOKIEPATH, COOKIE_DOMAIN);
            }
            
            \wp_send_json_success();
            
        } catch (\Exception $e) {
            \error_log(\sprintf('[Kura AI] Notice dismissal error: %s', $e->getMessage()));
            \wp_send_json_error($e->getMessage());
        }
    }
    
    /**
     * Handle security heartbeat checks via AJAX
     *
     * @since  1.0.0
     * @access public
     * @return void
     */
    public function ajax_security_heartbeat() {
        try {
            // Basic security check - no sensitive operations here, so we keep it simple
            $nonce = isset($_REQUEST['nonce']) ? \sanitize_text_field(\wp_unslash($_REQUEST['nonce'])) : '';
            
            if (!\wp_verify_nonce($nonce, 'kura_ai_public_nonce')) {
                \wp_send_json_error(\esc_html__('Security check failed.', 'kura-ai'));
                return;
            }
            
            // Get client information for logging/monitoring
            $client_data = array(
                'ip' => \sanitize_text_field(\wp_unslash($_SERVER['REMOTE_ADDR'])),
                'user_agent' => \sanitize_text_field(\wp_unslash($_SERVER['HTTP_USER_AGENT'])),
                'timestamp' => \current_time('mysql'),
            );
            
            // You could log this information or check against security rules
            // For now, we'll just acknowledge the heartbeat
            
            \wp_send_json_success(array(
                'status' => 'active',
                'timestamp' => \current_time('mysql'),
            ));
            
        } catch (\Exception $e) {
            \error_log(\sprintf('[Kura AI] Security heartbeat error: %s', $e->getMessage()));
            \wp_send_json_error(\esc_html__('An error occurred during security check.', 'kura-ai'));
        }
    }
}