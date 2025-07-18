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
    private $access_token;
    private $refresh_token;
    private $expires_in;
    private $token_created;
    private $model;
    private $max_tokens;

    public function __construct($oauth_tokens)
    {
        $this->access_token = $oauth_tokens['access_token'] ?? '';
        $this->refresh_token = $oauth_tokens['refresh_token'] ?? '';
        $this->expires_in = $oauth_tokens['expires_in'] ?? 3600;
        $this->token_created = $oauth_tokens['created'] ?? time();
        $this->model = 'gpt-3.5-turbo';
        $this->max_tokens = 500;
    }

    public function get_suggestion($issue)
    {
        try {
            // Check if token needs refreshing
            $this->check_token_expiry();

            $prompt = $this->build_prompt($issue);

            $response = wp_remote_post('https://api.openai.com/v1/chat/completions', array(
                'headers' => array(
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $this->access_token
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
                // If unauthorized, try refreshing token once
                if ($body['error']['code'] === 'invalid_api_key' || $body['error']['code'] === 'invalid_auth') {
                    $this->refresh_token();
                    return $this->get_suggestion($issue); // Retry with new token
                }
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
            // Check if token needs refreshing
            $this->check_token_expiry();

            $response = wp_remote_get('https://api.openai.com/v1/models', array(
                'headers' => array(
                    'Authorization' => 'Bearer ' . $this->access_token
                ),
                'timeout' => 15
            ));

            if (is_wp_error($response)) {
                return $response->get_error_message();
            }

            if (wp_remote_retrieve_response_code($response) !== 200) {
                // If unauthorized, try refreshing token once
                if (wp_remote_retrieve_response_code($response) === 401) {
                    $this->refresh_token();
                    return $this->verify_connection(); // Retry with new token
                }
                return __('Invalid access token or connection failed', 'kura-ai');
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

    private function check_token_expiry()
    {
        // Check if token is expired or about to expire (within 5 minutes)
        if (time() > ($this->token_created + $this->expires_in - 300)) {
            $this->refresh_token();
        }
    }

    private function refresh_token()
    {
        if (empty($this->refresh_token)) {
            throw new Exception(__('No refresh token available', 'kura-ai'));
        }

        $oauth_handler = new Kura_AI_OAuth_Handler();
        $new_tokens = $oauth_handler->refresh_token('openai', $this->refresh_token);

        if (is_wp_error($new_tokens)) {
            throw new Exception($new_tokens->get_error_message());
        }

        // Update the current instance with new tokens
        $this->access_token = $new_tokens['access_token'];
        $this->refresh_token = $new_tokens['refresh_token'] ?? $this->refresh_token;
        $this->expires_in = $new_tokens['expires_in'] ?? $this->expires_in;
        $this->token_created = time();

        // Update the stored tokens in settings
        $settings = get_option('kura_ai_settings');
        $settings['ai_oauth_tokens']['openai'] = array(
            'access_token' => $this->access_token,
            'refresh_token' => $this->refresh_token,
            'expires_in' => $this->expires_in,
            'created' => $this->token_created
        );
        update_option('kura_ai_settings', $settings);
    }
}