<?php
/**
 * Gemini Integration
 *
 * @package    Kura_AI
 * @subpackage Kura_AI/includes/ai-integrations
 * @author     Daniel Abughdyer <daniel@danovatesolutions.org>
 */

class Kura_AI_Gemini implements Kura_AI_Interface
{
    private $api_key;
    private $model;
    private $max_tokens;

    public function __construct($api_key)
    {
        $this->api_key = $api_key;
        $this->model = 'gemini-pro'; // Gemini's model name
        $this->max_tokens = 1000; // Gemini allows larger responses
    }

    public function get_suggestion($issue)
    {
        try {
            $prompt = $this->build_prompt($issue);

            $response = wp_remote_post('https://generativelanguage.googleapis.com/v1beta/models/' . $this->model . ':generateContent?key=' . urlencode($this->api_key), array(
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
            ));

            if (is_wp_error($response)) {
                throw new Exception($response->get_error_message());
            }

            $body = json_decode(wp_remote_retrieve_body($response), true);

            if (isset($body['error'])) {
                throw new Exception($body['error']['message']);
            }

            if (!isset($body['candidates'][0]['content']['parts'][0]['text'])) {
                throw new Exception(__('Unexpected response format from Gemini', 'kura-ai'));
            }

            return $body['candidates'][0]['content']['parts'][0]['text'];
        } catch (Exception $e) {
            return sprintf(__('Error getting AI suggestion: %s', 'kura-ai'), $e->getMessage());
        }
    }

    public function verify_connection()
    {
        try {
            $response = wp_remote_get('https://generativelanguage.googleapis.com/v1beta/models/' . $this->model . '?key=' . urlencode($this->api_key), array(
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