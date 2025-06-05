<?php
/**
 * OpenAI Integration
 *
 * @package    Kura_AI
 * @subpackage Kura_AI/includes/ai-integrations
 * @author     Daniel Abughdyer <daniel@danovatesolutions.org>
 */

class Kura_AI_OpenAI implements Kura_AI_Interface
{
    private $api_key;
    private $model;
    private $max_tokens;

    public function __construct($api_key)
    {
        $this->api_key = $api_key;
        $this->model = 'gpt-3.5-turbo';
        $this->max_tokens = 500;
    }

    public function get_suggestion($issue)
    {
        try {
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
                throw new Exception($response->get_error_message());
            }

            $body = json_decode(wp_remote_retrieve_body($response), true);

            if (isset($body['error'])) {
                throw new Exception($body['error']['message']);
            }

            return $body['choices'][0]['message']['content'] ?? __('No suggestion was returned by the AI.', 'kura-ai');
        } catch (Exception $e) {
            return sprintf(__('Error getting AI suggestion: %s', 'kura-ai'), $e->getMessage());
        }
    }

    public function verify_connection()
    {
        try {
            $response = wp_remote_get('https://api.openai.com/v1/models', array(
                'headers' => array(
                    'Authorization' => 'Bearer ' . $this->api_key
                ),
                'timeout' => 15
            ));

            if (is_wp_error($response)) {
                return $response->get_error_message();
            }

            if (wp_remote_retrieve_response_code($response) !== 200) {
                return __('Invalid API key or connection failed', 'kura-ai');
            }

            return true;
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    private function build_prompt($issue)
    {
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