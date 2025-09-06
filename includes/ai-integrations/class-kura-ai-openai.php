<?php
/**
 * The OpenAI integration functionality of the plugin.
 *
 * @package    Kura_AI
 * @subpackage Kura_AI/includes/ai-integrations
 * @author     Daniel Abughdyer <daniel@danovatesolutions.org>
 */

class Kura_AI_OpenAI implements Kura_AI_Interface {
    private $api_key;
    private $model;
    private $max_tokens;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param    string    $api_key    The API key for OpenAI.
     */
    public function __construct($api_key) {
        $this->api_key = $api_key;
        $this->model = 'gpt-4-turbo-preview';
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

        $response = wp_remote_post('https://api.openai.com/v1/chat/completions', array(
            'headers' => array(
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $this->api_key
            ),
            'body' => json_encode(array(
                'model' => $this->model,
                'messages' => array(
                    array(
                        'role' => 'system',
                        'content' => 'You are a WordPress security expert providing detailed, actionable advice.'
                    ),
                    array(
                        'role' => 'user',
                        'content' => $prompt
                    )
                ),
                'temperature' => 0.7,
                'max_tokens' => $this->max_tokens
            )),
            'timeout' => 30
        ));

        if (is_wp_error($response)) {
            return new WP_Error(
                'openai_api_error',
                sprintf(__('OpenAI API Error: %s', 'kura-ai'), $response->get_error_message())
            );
        }

        $response_code = wp_remote_retrieve_response_code($response);
        if ($response_code !== 200) {
            return new WP_Error(
                'openai_api_error',
                sprintf(__('OpenAI API Error: Received response code %d', 'kura-ai'), $response_code)
            );
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        if (isset($body['error'])) {
            return new WP_Error(
                'openai_api_error',
                sprintf(__('OpenAI API Error: %s', 'kura-ai'), $body['error']['message'])
            );
        }

        if (empty($body['choices'][0]['message']['content'])) {
            return new WP_Error(
                'openai_api_error',
                __('Invalid response from OpenAI API', 'kura-ai')
            );
        }

        return $body['choices'][0]['message']['content'];
    }

    /**
     * Verify connection to OpenAI.
     *
     * @since    1.0.0
     * @return   bool|WP_Error    True if connected, WP_Error if not
     */
    public function verify_connection() {
        $response = wp_remote_get('https://api.openai.com/v1/models', array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $this->api_key
            ),
            'timeout' => 15
        ));

        if (is_wp_error($response)) {
            return new WP_Error(
                'openai_connection_error',
                sprintf(__('Could not connect to OpenAI API: %s', 'kura-ai'), $response->get_error_message())
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
        $prompt = "I'm managing a WordPress site and have identified a security issue. ";
        $prompt .= "Here are the details:\n\n";
        $prompt .= "Issue Type: " . $issue['type'] . "\n";
        $prompt .= "Severity: " . $issue['severity'] . "\n";
        $prompt .= "Message: " . $issue['message'] . "\n";

        if (!empty($issue['fix'])) {
            $prompt .= "Current suggested fix: " . $issue['fix'] . "\n";
        }

        $prompt .= "\nPlease provide:\n";
        $prompt .= "1. A more detailed explanation of the risk\n";
        $prompt .= "2. Step-by-step instructions to fix the issue\n";
        $prompt .= "3. Any additional recommendations to prevent similar issues\n";
        $prompt .= "\nFormat your response in clear, concise paragraphs with bullet points for steps.";

        return $prompt;
    }
}