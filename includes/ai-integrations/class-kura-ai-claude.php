<?php
/**
 * The Claude AI integration functionality of the plugin.
 *
 * @package    Kura_AI
 * @subpackage Kura_AI/includes/ai-integrations
 * @author     Daniel Abughdyer <daniel@danovatesolutions.org>
 */

namespace Kura_AI;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// WordPress core includes
require_once ABSPATH . 'wp-includes/class-wp-error.php';
require_once ABSPATH . 'wp-includes/http.php';
require_once ABSPATH . 'wp-includes/formatting.php';
require_once ABSPATH . 'wp-includes/l10n.php';

use WP_Error;
use Exception;

// Import WordPress functions
use function wp_remote_post;
use function wp_remote_get;
use function wp_json_encode;
use function is_wp_error;
use function wp_remote_retrieve_response_code;
use function wp_remote_retrieve_body;
use function __;
use function sprintf;
use function json_decode;

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
     * @return   string|\WP_Error   The AI suggestion or error
     */
    public function get_suggestion($issue) {
        // Build prompt from issue data
        $prompt = $this->build_prompt($issue);
        
        $headers = array(
            'Content-Type' => 'application/json',
            'x-api-key' => $this->api_key,
            'anthropic-version' => '2023-06-01'
        );
        
        $body = array(
            'messages' => array(
                array(
                    'role' => 'user',
                    'content' => $prompt
                )
            ),
            'model' => 'claude-3-opus-20240229',
            'max_tokens' => 1000
        );
        
        $response = \wp_remote_post('https://api.anthropic.com/v1/messages', array(
            'headers' => $headers,
            'body' => \wp_json_encode($body),
            'timeout' => 30
        ));

        if (\is_wp_error($response)) {
            return new \WP_Error(
                'claude_api_error',
                \sprintf(\__('Claude API Error: %s', 'kura-ai'), $response->get_error_message())
            );
        }

        $response_code = \wp_remote_retrieve_response_code($response);

        if ($response_code !== 200) {
            return new \WP_Error(
                'claude_api_error',
                \sprintf(\__('Claude API Error: Received response code %d', 'kura-ai'), $response_code)
            );
        }

        $body = \json_decode(\wp_remote_retrieve_body($response), true);

        if (empty($body['content'][0]['text'])) {
            return new \WP_Error(
                'claude_api_error',
                \__('No suggestion was returned by the AI.', 'kura-ai')
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

        $response = \wp_remote_post($api_url, array(
            'headers' => $headers,
            'body' => \wp_json_encode($body),
            'timeout' => 10
        ));

        if (\is_wp_error($response)) {
            return new \WP_Error(
                'claude_connection_error',
                \sprintf(\__('Could not connect to Claude API: %s', 'kura-ai'), $response->get_error_message())
            );
        }

        $response_code = \wp_remote_retrieve_response_code($response);
        return $response_code === 200;
    }

    /**
     * Build the prompt for the AI.
     *
     * @since    1.0.0
     * @param    array     $issue    The security issue data
     * @return   string    The formatted prompt
     */
    private function build_prompt($issue) {
        $prompt = "You are a WordPress security expert. I need help with a security issue:\n\n";
        
        // Safely access array keys with defaults
        $issue_type = isset($issue['type']) ? $issue['type'] : 'Unknown';
        $issue_severity = isset($issue['severity']) ? $issue['severity'] : 'Medium';
        $issue_message = isset($issue['message']) ? $issue['message'] : 'No details provided';
        
        $prompt .= "Issue Type: " . $issue_type . "\n";
        $prompt .= "Severity: " . $issue_severity . "\n";
        $prompt .= "Message: " . $issue_message . "\n";

        if (!empty($issue['fix'])) {
            $prompt .= "Current suggested fix: " . $issue['fix'] . "\n";
        }

        $prompt .= "\nPlease provide:\n";
        $prompt .= "1. A detailed explanation of the risk\n";
        $prompt .= "2. Step-by-step instructions to fix the issue\n";
        $prompt .= "3. Additional recommendations to prevent similar issues\n";
        $prompt .= "\nFormat your response with clear headings and bullet points.";

        return $prompt;
    }
}