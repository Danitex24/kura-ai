<?php
/**
 * The Gemini AI integration functionality of the plugin.
 *
 * @package    Kura_AI
 * @subpackage Kura_AI/includes/ai-integrations
 * @author     Daniel Abughdyer <daniel@danovatesolutions.org>
 */

class Kura_AI_Gemini implements Kura_AI_Interface {
    private $api_key;
    private $model;
    private $max_tokens;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param    string    $api_key    The API key for Gemini AI.
     */
    public function __construct($api_key) {
        $this->api_key = $api_key;
        $this->model = 'gemini-pro';
        $this->max_tokens = 1000;
    }

    /**
     * Get AI suggestion for a security issue.
     *
     * @since    1.0.0
     * @param    array    $issue    The security issue data
     * @return   string|WP_Error   The AI suggestion or error
     */
    public function get_suggestion($issue) {
        $prompt = $this->build_prompt($issue);

        $response = wp_remote_post(
            'https://generativelanguage.googleapis.com/v1beta/models/' . $this->model . ':generateContent?key=' . urlencode($this->api_key),
            array(
                'headers' => array(
                    'Content-Type' => 'application/json'
                ),
                'body' => json_encode(array(
                    'contents' => array(
                        'parts' => array(
                            array(
                                'text' => $prompt
                            )
                        )
                    ),
                    'generationConfig' => array(
                        'temperature' => 0.7,
                        'maxOutputTokens' => $this->max_tokens
                    ),
                    'safetySettings' => array(
                        array(
                            'category' => 'HARM_CATEGORY_DANGEROUS_CONTENT',
                            'threshold' => 'BLOCK_NONE'
                        )
                    )
                )),
                'timeout' => 30
            )
        );

        if (is_wp_error($response)) {
            return new WP_Error(
                'gemini_api_error',
                sprintf(__('Gemini API Error: %s', 'kura-ai'), $response->get_error_message())
            );
        }

        $response_code = wp_remote_retrieve_response_code($response);
        if ($response_code !== 200) {
            return new WP_Error(
                'gemini_api_error',
                sprintf(__('Gemini API Error: Received response code %d', 'kura-ai'), $response_code)
            );
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        if (isset($body['error'])) {
            return new WP_Error(
                'gemini_api_error',
                sprintf(__('Gemini API Error: %s', 'kura-ai'), $body['error']['message'])
            );
        }

        if (!isset($body['candidates'][0]['content']['parts'][0]['text'])) {
            return new WP_Error(
                'gemini_api_error',
                __('Invalid response from Gemini API', 'kura-ai')
            );
        }

        return $body['candidates'][0]['content']['parts'][0]['text'];
    }

    /**
     * Verify connection to Gemini AI.
     *
     * @since    1.0.0
     * @return   bool|WP_Error    True if connected, WP_Error if not
     */
    public function verify_connection() {
        $response = wp_remote_get(
            'https://generativelanguage.googleapis.com/v1beta/models/' . $this->model . '?key=' . urlencode($this->api_key),
            array('timeout' => 15)
        );

        if (is_wp_error($response)) {
            return new WP_Error(
                'gemini_connection_error',
                sprintf(__('Could not connect to Gemini API: %s', 'kura-ai'), $response->get_error_message())
            );
        }

        $response_code = wp_remote_retrieve_response_code($response);
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
        $prompt .= "Issue Type: " . $issue['type'] . "\n";
        $prompt .= "Severity: " . $issue['severity'] . "\n";
        $prompt .= "Message: " . $issue['message'] . "\n";

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