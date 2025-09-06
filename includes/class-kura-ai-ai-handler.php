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

    /**
     * Get AI suggestion for a security issue.
     *
     * @since    1.0.0
     * @param    array    $issue    The security issue data
     * @return   string|WP_Error    The AI suggestion or error
     */
    public function get_suggestion($issue) {
        // Get active API key
        global $wpdb;
        $table_name = $wpdb->prefix . 'kura_ai_api_keys';
        $active_key = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT provider, api_key FROM $table_name WHERE active = 1 LIMIT 1"
            )
        );

        if (!$active_key) {
            return new WP_Error(
                'ai_disabled',
                __('AI suggestions are disabled. Please enable AI and provide an API key in settings.', 'kura-ai')
            );
        }

        // Initialize appropriate AI service
        switch ($active_key->provider) {
            case 'openai':
                $ai_service = new Kura_AI_OpenAI($active_key->api_key);
                break;
            case 'claude':
                $ai_service = new Kura_AI_Claude($active_key->api_key);
                break;
            case 'gemini':
                $ai_service = new Kura_AI_Gemini($active_key->api_key);
                break;
            default:
                return new WP_Error(
                    'unsupported_service',
                    __('Selected AI service is not supported.', 'kura-ai')
                );
        }

        // Get suggestion from AI service
        $suggestion = $ai_service->get_suggestion($issue);

        if (empty($suggestion)) {
            return new WP_Error(
                'no_suggestion',
                __('No suggestion was returned by the AI.', 'kura-ai')
            );
        }

        // Log the suggestion
        $logger = new Kura_AI_Logger($this->plugin_name, $this->version);
        $logger->log(
            'ai_suggestion',
            sprintf(__('AI suggestion generated for issue: %s', 'kura-ai'), $issue['type']),
            array(
                'issue' => $issue,
                'suggestion' => $suggestion
            )
        );

        return $suggestion;
    }
}