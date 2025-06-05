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
     * The AI service instance.
     *
     * @since    1.0.0
     * @access   private
     * @var      object    $ai_service    The AI service implementation.
     */
    private $ai_service;

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

        if (empty($settings['enable_ai']) || empty($settings['api_key'])) {
            return __('AI suggestions are disabled. Please enable AI and provide an API key in settings.', 'kura-ai');
        }

        $service = $settings['ai_service'] ?? 'openai';
        $api_key = $settings['api_key'];

        try {
            $this->init_ai_service($service, $api_key);
            return $this->ai_service->get_suggestion($issue);
        } catch (Exception $e) {
            return sprintf(__('Error getting AI suggestion: %s', 'kura-ai'), $e->getMessage());
        }
    }

    /**
     * Verify API connection to the AI service.
     *
     * @since    1.0.0
     * @param    string    $service    The AI service to verify
     * @param    string    $api_key    The API key for the service
     * @return   bool|string           True if valid, error message if not
     */
    public function verify_connection($service, $api_key)
    {
        try {
            $this->init_ai_service($service, $api_key);
            return $this->ai_service->verify_connection();
        } catch (Exception $e) {
            return sprintf(__('Error verifying connection: %s', 'kura-ai'), $e->getMessage());
        }
    }

    /**
     * Initialize the AI service.
     *
     * @since    1.0.0
     * @param    string    $service    The AI service to initialize
     * @param    string    $api_key    The API key for the service
     * @throws   Exception             If service is not supported
     */
    private function init_ai_service($service, $api_key)
    {
        switch ($service) {
            case 'openai':
                require_once plugin_dir_path(__FILE__) . 'ai-integrations/class-kura-ai-openai.php';
                $this->ai_service = new Kura_AI_OpenAI($api_key);
                break;
            case 'claude':
                require_once plugin_dir_path(__FILE__) . 'ai-integrations/class-kura-ai-claude.php';
                $this->ai_service = new Kura_AI_Claude($api_key);
                break;
            case 'gemini':
                require_once plugin_dir_path(__FILE__) . 'ai-integrations/class-kura-ai-gemini.php';
                $this->ai_service = new Kura_AI_Gemini($api_key);
                break;
            default:
                throw new Exception(__('Selected AI service is not supported.', 'kura-ai'));
        }
    }

    /**
     * Build the prompt for AI services.
     * (Kept for backward compatibility and potential future use)
     *
     * @since    1.0.0
     * @param    array     $issue    The security issue details
     * @return   string    The constructed prompt
     */
    private function build_ai_prompt($issue)
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