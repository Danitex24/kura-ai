<?php
/**
 * The AI handler functionality of the plugin.
 *
 * @package    Kura_AI
 * @subpackage Kura_AI/includes
 * @author     Daniel Abughdyer <daniel@danovatesolutions.org>
 */

namespace Kura_AI;

use \WP_Error;
use \Exception;
use Kura_AI\Kura_AI_OpenAI;
use Kura_AI\Kura_AI_Claude;
use Kura_AI\Kura_AI_Gemini;

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
        if (empty($issue['message'])) {
            return new \WP_Error(
                'invalid_input',
                \__('Issue description is required.', 'kura-ai')
            );
        }

        // Get settings to determine current provider
        $settings = \get_option('kura_ai_settings');
        $current_provider = !empty($settings['ai_service']) ? $settings['ai_service'] : '';

        if (empty($current_provider)) {
            return new \WP_Error(
                'no_provider',
                \__('No AI provider selected. Please select a provider in settings.', 'kura-ai')
            );
        }

        // Get active API key for current provider
        global $wpdb;
        $table_name = $wpdb->prefix . 'kura_ai_api_keys';
        $api_key = $wpdb->get_var($wpdb->prepare(
            "SELECT api_key FROM $table_name WHERE provider = %s AND status = 'active' LIMIT 1",
            $current_provider
        ));

        if (empty($api_key)) {
            return new \WP_Error(
                'no_api_key',
                \sprintf(\__('No active API key found for %s. Please add your API key in settings.', 'kura-ai'), \ucfirst($current_provider))
            );
        }

        // Initialize appropriate AI service
        try {
            switch ($current_provider) {
                case 'openai':
                    $ai_service = new Kura_AI_OpenAI($api_key);
                    break;
                case 'claude':
                    $ai_service = new Kura_AI_Claude($api_key);
                    break;
                case 'gemini':
                    $ai_service = new Kura_AI_Gemini($api_key);
                    break;
                default:
                    return new \WP_Error(
                        'unsupported_service',
                        \__('Selected AI service is not supported.', 'kura-ai')
                    );
            }

            // Get suggestion from AI service
            $suggestion = $ai_service->get_suggestion($issue);

            if (empty($suggestion)) {
                throw new \Exception(\__('No suggestion was returned by the AI.', 'kura-ai'));
            }

            // Log the suggestion
            $logger = new Kura_AI_Logger($this->plugin_name, $this->version);
            $logger->log(
                'ai_suggestion',
                sprintf(\__('AI suggestion generated for issue: %s', 'kura-ai'), $issue['type']),
                array(
                    'issue' => $issue,
                    'suggestion' => $suggestion
                )
            );

            return $suggestion;

        } catch (Exception $e) {
            return new \WP_Error(
                'ai_error',
                \sprintf(\__('Error getting AI suggestion: %s', 'kura-ai'), $e->getMessage())
            );
        }
    }
}