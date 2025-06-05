<?php
/**
 * The AI handler functionality of the plugin.
 *
 * @package    Kura_AI
 * @subpackage Kura_AI/includes
 * @author     Daniel Abughdyer <daniel@danovatesolutions.org>
 */
class Kura_AI_AI_Handler
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
     * Get AI-powered suggestions for a security issue.
     *
     * @since    1.0.0
     * @param    array     $issue    The security issue details
     * @return   string    AI-generated suggestion
     */
    public function get_suggestion($issue)
    {
        $settings = get_option('kura_ai_settings');

        // Check if AI is enabled and API key is set
        if (empty($settings['enable_ai']) || empty($settings['api_key'])) {
            return __('AI suggestions are disabled. Please enable AI and provide an API key in settings.', 'kura-ai');
        }

        // Determine which AI service to use
        $service = $settings['ai_service'] ?? 'openai';

        try {
            switch ($service) {
                case 'openai':
                    return $this->get_openai_suggestion($issue, $settings['api_key']);
                    break;
                default:
                    return __('Selected AI service is not supported.', 'kura-ai');
            }
        } catch (Exception $e) {
            return sprintf(__('Error getting AI suggestion: %s', 'kura-ai'), $e->getMessage());
        }
    }

    /**
     * Get suggestion from OpenAI API.
     *
     * @since    1.0.0
     * @param    array     $issue    The security issue details
     * @param    string    $api_key  OpenAI API key
     * @return   string    AI-generated suggestion
     */
    private function get_openai_suggestion($issue, $api_key)
    {
        $prompt = $this->build_openai_prompt($issue);

        $response = wp_remote_post('https://api.openai.com/v1/chat/completions', array(
            'headers' => array(
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $api_key
            ),
            'body' => json_encode(array(
                'model' => 'gpt-3.5-turbo',
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
                'max_tokens' => 500
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
    }

    /**
     * Build the prompt for OpenAI.
     *
     * @since    1.0.0
     * @param    array     $issue    The security issue details
     * @return   string    The constructed prompt
     */
    private function build_openai_prompt($issue)
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