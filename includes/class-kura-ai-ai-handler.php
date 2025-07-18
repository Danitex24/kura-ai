<?php
/**
 * The AI handler functionality of the plugin.
 *
 * @package    Kura_AI
 * @subpackage Kura_AI/includes
 * @author     Daniel Abughdyer <daniel@danovatesolutions.org>
 */
class Kura_AI_AI_Handler {
    private $plugin_name;
    private $version;

    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    public function get_suggestion($issue) {
        $settings = get_option('kura_ai_settings');
        $service = $settings['ai_service'] ?? 'openai';

        if (empty($settings['ai_oauth_tokens'][$service]['access_token'])) {
            return __('Please connect to an AI provider first.', 'kura-ai');
        }

        try {
            return $this->get_oauth_suggestion($service, $issue);
        } catch (Exception $e) {
            return sprintf(__('Error: %s', 'kura-ai'), $e->getMessage());
        }
    }

    private function get_oauth_suggestion($service, $issue) {
        $tokens = get_option('kura_ai_settings')['ai_oauth_tokens'][$service];
        
        if (empty($tokens['access_token'])) {
            throw new Exception(__('Access token is missing.', 'kura-ai'));
        }

        switch ($service) {
            case 'openai':
                require_once plugin_dir_path(__FILE__) . 'ai-integrations/class-kura-ai-openai.php';
                $ai_service = new Kura_AI_OpenAI($tokens);
                break;
            case 'gemini':
                require_once plugin_dir_path(__FILE__) . 'ai-integrations/class-kura-ai-gemini.php';
                $ai_service = new Kura_AI_Gemini($tokens);
                break;
            default:
                throw new Exception(__('Selected AI service is not supported.', 'kura-ai'));
        }

        return $ai_service->get_suggestion($issue);
    }
}