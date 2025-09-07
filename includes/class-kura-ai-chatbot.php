<?php

namespace Kura_Ai;

/**
 * The chatbot functionality of the plugin.
 *
 * @link       https://kura.ai
 * @since      1.0.0
 *
 * @package    Kura_Ai
 * @subpackage Kura_Ai/includes
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// WordPress function stubs for static analysis
if (!function_exists('get_option')) {
    function get_option($option, $default = false) { return $default; }
}
if (!function_exists('wp_send_json_success')) {
    function wp_send_json_success($data = null) { echo json_encode(['success' => true, 'data' => $data]); exit; }
}
if (!function_exists('wp_send_json_error')) {
    function wp_send_json_error($data = null) { echo json_encode(['success' => false, 'data' => $data]); exit; }
}
if (!function_exists('sanitize_textarea_field')) {
    function sanitize_textarea_field($str) { return strip_tags($str); }
}
if (!function_exists('wp_verify_nonce')) {
    function wp_verify_nonce($nonce, $action = -1) { return true; }
}
if (!function_exists('current_user_can')) {
    function current_user_can($capability) { return true; }
}
if (!function_exists('esc_html__')) {
    function esc_html__($text, $domain = 'default') { return htmlspecialchars($text, ENT_QUOTES, 'UTF-8'); }
}

/**
 * The chatbot class.
 *
 * This is used to define chatbot functionality, AJAX handlers, and AI integration.
 *
 * @since      1.0.0
 * @package    Kura_Ai
 * @subpackage Kura_Ai/includes
 * @author     Kura AI Team <support@kura.ai>
 */
class Kura_Ai_Chatbot {

    /**
     * The AI handler instance.
     *
     * @since    1.0.0
     * @access   private
     * @var      Kura_Ai_Ai_Handler    $ai_handler    The AI handler instance.
     */
    private $ai_handler;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     */
    public function __construct() {
        // Initialize AI handler if class exists
        if (class_exists('Kura_Ai\Kura_Ai_Ai_Handler')) {
            $this->ai_handler = new Kura_Ai_Ai_Handler('kura-ai', '1.0.0');
        }
    }

    /**
     * Check if chatbot is enabled.
     *
     * @since    1.0.0
     * @return   bool    True if chatbot is enabled, false otherwise.
     */
    public function is_enabled() {
        return get_option('kura_ai_chatbot_enabled', false);
    }

    /**
     * Handle chatbot message via AJAX.
     *
     * @since    1.0.0
     */
    public function handle_chat_message() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'kura_ai_chatbot_nonce')) {
            wp_send_json_error(array(
                'message' => esc_html__('Security check failed.', 'kura-ai')
            ));
        }

        // Get and sanitize message
        $message = sanitize_textarea_field($_POST['message'] ?? '');
        
        if (empty($message)) {
            wp_send_json_error(array(
                'message' => esc_html__('Message cannot be empty.', 'kura-ai')
            ));
        }

        // Check if chatbot is enabled
        if (!$this->is_enabled()) {
            wp_send_json_error(array(
                'message' => esc_html__('Chatbot is currently disabled.', 'kura-ai')
            ));
        }

        // Generate AI response
        $response = $this->generate_response($message);
        
        if ($response) {
            wp_send_json_success(array(
                'response' => $response,
                'timestamp' => current_time('timestamp')
            ));
        } else {
            wp_send_json_error(array(
                'message' => esc_html__('Failed to generate response. Please try again.', 'kura-ai')
            ));
        }
    }

    /**
     * Generate AI response for user message.
     *
     * @since    1.0.0
     * @param    string    $message    The user message.
     * @return   string    The AI response.
     */
    private function generate_response($message) {
        // Create context-aware prompt for the chatbot
        $system_prompt = "You are Kura AI Assistant, a helpful chatbot for the Kura AI WordPress security plugin. 
        
You help users with:
        - Plugin features and functionality
        - Security best practices
        - WordPress security questions
        - Troubleshooting plugin issues
        - General website security advice
        
Keep responses concise, helpful, and friendly. If asked about features not related to security or the plugin, politely redirect to security topics.";
        
        $user_prompt = "User question: " . $message;
        
        // Get AI service preference
        $ai_service = get_option('kura_ai_service', 'openai');
        
        // Generate response using AI handler if available
        $response = '';
        if ($this->ai_handler && method_exists($this->ai_handler, 'get_suggestion')) {
            $issue_data = array(
                'message' => $message,
                'type' => 'chatbot_query',
                'context' => 'user_question'
            );
            
            $ai_response = $this->ai_handler->get_suggestion($issue_data);
            
            if (!is_wp_error($ai_response)) {
                $response = $ai_response;
            }
        }
        
        // Fallback response if AI fails
        if (empty($response)) {
            $response = esc_html__('I apologize, but I\'m having trouble processing your request right now. Please try again or contact support for assistance.', 'kura-ai');
        }
        
        return $response;
    }

    /**
     * Get chatbot configuration for frontend.
     *
     * @since    1.0.0
     * @return   array    Chatbot configuration.
     */
    public function get_config() {
        return array(
            'enabled' => $this->is_enabled(),
            'welcome_message' => get_option('kura_ai_chatbot_welcome', esc_html__('Hi! I\'m Kura AI Assistant. How can I help you with your website security today?', 'kura-ai')),
            'placeholder' => esc_html__('Type your message...', 'kura-ai'),
            'send_button' => esc_html__('Send', 'kura-ai'),
            'typing_indicator' => esc_html__('Kura AI is typing...', 'kura-ai')
        );
    }

    /**
     * Enqueue chatbot scripts and styles.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts() {
        if (!$this->is_enabled()) {
            return;
        }

        // Enqueue chatbot styles
        wp_enqueue_style(
            'kura-ai-chatbot',
            plugin_dir_url(__FILE__) . '../public/css/chatbot.css',
            array(),
            KURA_AI_VERSION
        );

        // Enqueue chatbot script
        wp_enqueue_script(
            'kura-ai-chatbot',
            plugin_dir_url(__FILE__) . '../public/js/chatbot.js',
            array('jquery'),
            KURA_AI_VERSION,
            true
        );

        // Localize script for AJAX
        wp_localize_script(
            'kura-ai-chatbot',
            'kuraAiChatbot',
            array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('kura_ai_chatbot_nonce'),
                'position' => get_option('kura_ai_chatbot_position', 'bottom-right')
            )
        );
    }

    /**
     * Render chatbot HTML on frontend
     */
    public function render_chatbot() {
        // Only render if chatbot is enabled
        if (!get_option('kura_ai_chatbot_enabled', false)) {
            return;
        }
        
        $position = get_option('kura_ai_chatbot_position', 'bottom-right');
        ?>
        <div id="kura-ai-chatbot" class="kura-ai-chatbot kura-ai-chatbot-<?php echo esc_attr($position); ?>" style="display: none;">
            <div class="kura-ai-chatbot-toggle" id="kura-ai-chatbot-toggle">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M20 2H4C2.9 2 2 2.9 2 4V22L6 18H20C21.1 18 22 17.1 22 16V4C22 2.9 21.1 2 20 2ZM20 16H5.17L4 17.17V4H20V16Z" fill="currentColor"/>
                    <path d="M7 9H17V11H7V9ZM7 12H15V14H7V12Z" fill="currentColor"/>
                </svg>
            </div>
            
            <div class="kura-ai-chatbot-window" id="kura-ai-chatbot-window">
                <div class="kura-ai-chatbot-header">
                    <div class="kura-ai-chatbot-title">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M12 2C13.1 2 14 2.9 14 4C14 5.1 13.1 6 12 6C10.9 6 10 5.1 10 4C10 2.9 10.9 2 12 2ZM21 9V7L15 1L13.5 2.5L16.17 5.17L10.59 10.75C10.21 11.13 10.21 11.75 10.59 12.13L11.87 13.41C12.25 13.79 12.87 13.79 13.25 13.41L18.83 7.83L21.5 10.5L23 9H21ZM1 9V21C1 22.1 1.9 23 3 23H15C16.1 23 17 22.1 17 21V9C17 7.9 16.1 7 15 7H3C1.9 7 1 7.9 1 9Z" fill="currentColor"/>
                        </svg>
                        <span>Kura AI Assistant</span>
                    </div>
                    <button class="kura-ai-chatbot-close" id="kura-ai-chatbot-close">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M19 6.41L17.59 5L12 10.59L6.41 5L5 6.41L10.59 12L5 17.59L6.41 19L12 13.41L17.59 19L19 17.59L13.41 12L19 6.41Z" fill="currentColor"/>
                        </svg>
                    </button>
                </div>
                
                <div class="kura-ai-chatbot-messages" id="kura-ai-chatbot-messages">
                    <div class="kura-ai-message kura-ai-message-bot">
                        <div class="kura-ai-message-content">
                            <p>Hello! I'm your Kura AI assistant. How can I help you today?</p>
                        </div>
                    </div>
                </div>
                
                <div class="kura-ai-chatbot-input">
                    <div class="kura-ai-input-container">
                        <textarea id="kura-ai-message-input" placeholder="Type your message..." rows="1"></textarea>
                        <button id="kura-ai-send-button" type="button">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M2.01 21L23 12L2.01 3L2 10L17 12L2 14L2.01 21Z" fill="currentColor"/>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
}