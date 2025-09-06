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
        global $wpdb;
        $table_name = $wpdb->prefix . 'kura_ai_api_keys';
        
        // Get active API key for the selected service
        $settings = get_option('kura_ai_settings');
        $service = $settings['ai_service'] ?? 'openai';
        
        $api_key = $wpdb->get_var($wpdb->prepare(
            "SELECT api_key FROM $table_name WHERE provider = %s AND active = 1",
            $service
        ));

        if (empty($api_key)) {
            return __('Please connect to an AI provider first.', 'kura-ai');
        }

        try {
            return $this->get_ai_suggestion($service, $api_key, $issue);
        } catch (Exception $e) {
            return sprintf(__('Error: %s', 'kura-ai'), $e->getMessage());
        }
    }

    private function get_ai_suggestion($service, $api_key, $issue) {
        switch ($service) {
            case 'openai':
                require_once plugin_dir_path(__FILE__) . 'ai-integrations/class-kura-ai-openai.php';
                $ai_service = new Kura_AI_OpenAI($api_key);
                break;
            case 'gemini':
                require_once plugin_dir_path(__FILE__) . 'ai-integrations/class-kura-ai-gemini.php';
                $ai_service = new Kura_AI_Gemini($api_key);
                break;
            default:
                throw new Exception(__('Selected AI service is not supported.', 'kura-ai'));
        }

        return $ai_service->get_suggestion($issue);
    }
}