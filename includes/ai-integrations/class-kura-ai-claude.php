<?php
/**
 * The Claude AI integration functionality of the plugin.
 *
 * @package    Kura_AI
 * @subpackage Kura_AI/includes/ai-integrations
 * @author     Daniel Abughdyer <daniel@danovatesolutions.org>
 */

class Kura_AI_Claude implements Kura_AI_Interface {
    private $api_key;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param    string    $api_key    The API key for Claude AI.
     */
    public function __construct($api_key) {
        $this->api_key = $api_key;
    }

    /**
     * Get AI suggestion for a security issue.
     *
     * @since    1.0.0
     * @param    array    $issue    The security issue data
     * @return   string|WP_Error   The AI suggestion or error
     */
    public function get_suggestion($issue) {
        $api_url = 'https://api.anthropic.com/v1/messages';
        
        // Prepare the system message
        $system_message = 'You are a WordPress security expert. Provide detailed, actionable advice for fixing WordPress security issues.';
        
        // Prepare the user message based on the issue
        $user_message = sprintf(
            "Please provide a detailed solution for this WordPress security issue:\n\nIssue Type: %s\nSeverity: %s\nDescription: %s\n\nProvide step-by-step instructions on how to fix this issue, including any code changes if necessary.",
            $issue['type'],
            $issue['severity'],
            $issue['message']
        );

        $headers = array(
            'Content-Type' => 'application/json',
            'x-api-key' => $this->api_key,
            'anthropic-version' => '2023-06-01'
        );

        $body = array(
            'messages' => array(
                array(
                    'role' => 'user',
                    'content' => $user_message
                )
            ),
            'system' => $system_message,
            'model' => 'claude-3-opus-20240229',
            'max_tokens' => 1000
        );

        $response = wp_remote_post($api_url, array(
            'headers' => $headers,
            'body' => json_encode($body),
            'timeout' => 30
        ));

        if (is_wp_error($response)) {
            return new WP_Error(
                'claude_api_error',
                sprintf(__('Claude API Error: %s', 'kura-ai'), $response->get_error_message())
            );
        }

        $response_code = wp_remote_retrieve_response_code($response);
        if ($response_code !== 200) {
            return new WP_Error(
                'claude_api_error',
                sprintf(__('Claude API Error: Received response code %d', 'kura-ai'), $response_code)
            );
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        if (empty($body['content'][0]['text'])) {
            return new WP_Error(
                'claude_api_error',
                __('Invalid response from Claude API', 'kura-ai')
            );
        }

        return $body['content'][0]['text'];
    }

    /**
     * Verify connection to Claude AI.
     *
     * @since    1.0.0
     * @return   bool|WP_Error    True if connected, WP_Error if not
     */
    public function verify_connection() {
        $api_url = 'https://api.anthropic.com/v1/messages';
        
        $headers = array(
            'Content-Type' => 'application/json',
            'x-api-key' => $this->api_key,
            'anthropic-version' => '2023-06-01'
        );

        $body = array(
            'messages' => array(
                array(
                    'role' => 'user',
                    'content' => 'Test connection'
                )
            ),
            'model' => 'claude-3-opus-20240229',
            'max_tokens' => 10
        );

        $response = wp_remote_post($api_url, array(
            'headers' => $headers,
            'body' => json_encode($body),
            'timeout' => 10
        ));

        if (is_wp_error($response)) {
            return new WP_Error(
                'claude_connection_error',
                sprintf(__('Could not connect to Claude API: %s', 'kura-ai'), $response->get_error_message())
            );
        }

        $response_code = wp_remote_retrieve_response_code($response);
        return $response_code === 200;
    }
}