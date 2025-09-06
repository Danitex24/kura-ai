<?php
/**
 * The OpenAI integration functionality of the plugin.
 *
 * @link       https://danovatesolutions.org
 * @since      1.0.0
 *
 * @package    Kura_AI
 * @subpackage Kura_AI/includes
 */

namespace Kura_AI;

use WP_Error;
use Exception;

// Import WordPress functions
use function wp_remote_post;
use function wp_remote_get;
use function wp_json_encode;
use function is_wp_error;
use function wp_remote_retrieve_body;
use function get_option;
use function maybe_serialize;

/**
 * The OpenAI integration class of the plugin.
 *
 * Handles all OpenAI API interactions including:
 * - API authentication
 * - Code analysis
 * - Threat detection
 * - Response processing
 *
 * @package    Kura_AI
 * @subpackage Kura_AI/includes
 * @author     Daniel Abughdyer <daniel@danovatesolutions.org>
 */
class Kura_AI_OpenAI {

    /**
     * The OpenAI API key.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $api_key    The OpenAI API key.
     */
    private $api_key;

    /**
     * The OpenAI API endpoint.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $api_endpoint    The OpenAI API endpoint.
     */
    private $api_endpoint = 'https://api.openai.com/v1/chat/completions';

    /**
     * Plugin settings.
     *
     * @since    1.0.0
     * @access   private
     * @var      array    $settings    Plugin settings.
     */
    private $settings;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param    string    $api_key    The API key for OpenAI.
     */
    public function __construct($api_key = null) {
        global $wpdb;
        $this->settings = get_option('kura_ai_settings');
        
        // If API key is provided, use it directly
        if ($api_key !== null) {
            $this->api_key = $api_key;
            return;
        }
        
        // Otherwise get API key from database
        $table_name = $wpdb->prefix . 'kura_ai_api_keys';
        $db_api_key = $wpdb->get_var($wpdb->prepare(
            "SELECT api_key FROM $table_name WHERE provider = %s AND status = %s",
            'openai',
            'active'
        ));

        $this->api_key = $db_api_key;
    }

    /**
     * Analyze code for potential security threats.
     *
     * @since    1.0.0
     * @param    string    $prompt    Analysis prompt including code.
     * @return   array               Analysis results.
     */
    public function analyze_code($prompt) {
        if (empty($this->api_key)) {
            throw new \Exception('OpenAI API key not configured');
        }

        $response = $this->make_api_request(array(
            'model' => 'gpt-4',
            'messages' => array(
                array(
                    'role' => 'system',
                    'content' => 'You are a cybersecurity expert specialized in malware detection and code analysis. '
                               . 'Analyze the provided code for security threats and provide detailed explanations.'
                ),
                array(
                    'role' => 'user',
                    'content' => $prompt
                )
            ),
            'temperature' => 0.2,
            'max_tokens' => 1000
        ));

        return $this->process_analysis_response($response);
    }

    /**
     * Make API request to OpenAI.
     *
     * @since    1.0.0
     * @param    array    $data    Request data.
     * @return   array            API response.
     */
    private function make_api_request($data) {
        $args = array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $this->api_key,
                'Content-Type' => 'application/json'
            ),
            'body' => wp_json_encode($data),
            'timeout' => 30,
            'sslverify' => true
        );

        $response = wp_remote_post($this->api_endpoint, $args);

        if (is_wp_error($response)) {
            throw new \Exception('OpenAI API request failed: ' . $response->get_error_message());
        }

        $body = wp_remote_retrieve_body($response);
        $result = json_decode($body, true);

        if (isset($result['error'])) {
            throw new \Exception('OpenAI API error: ' . $result['error']['message']);
        }

        return $result;
    }

    /**
     * Process and structure the AI analysis response.
     *
     * @since    1.0.0
     * @param    array    $response    Raw API response.
     * @return   array               Structured analysis results.
     */
    private function process_analysis_response($response) {
        if (empty($response['choices'][0]['message']['content'])) {
            return array(
                'confidence' => 0,
                'explanation' => 'No analysis available'
            );
        }

        $content = $response['choices'][0]['message']['content'];
        
        // Extract confidence score using regex
        preg_match('/confidence[:\s]*(\d*\.?\d+)/i', $content, $matches);
        $confidence = isset($matches[1]) ? floatval($matches[1]) : 0.0;

        // Normalize confidence to 0-1 range if necessary
        if ($confidence > 1) {
            $confidence = $confidence / 100;
        }

        return array(
            'confidence' => $confidence,
            'explanation' => $this->format_explanation($content)
        );
    }

    /**
     * Format the AI explanation for better readability.
     *
     * @since    1.0.0
     * @param    string    $content    Raw AI response content.
     * @return   string               Formatted explanation.
     */
    private function format_explanation($content) {
        // Remove confidence score section if present
        $content = preg_replace('/confidence[:\s]*\d*\.?\d+[%]?/i', '', $content);

        // Clean up formatting
        $content = trim($content);
        $content = preg_replace('/\n{3,}/', '\n\n', $content);

        return $content;
    }

    /**
     * Log API usage for monitoring.
     *
     * @since    1.0.0
     * @param    string    $endpoint    API endpoint used.
     * @param    int       $tokens      Number of tokens used.
     */
    private function log_api_usage($endpoint, $tokens) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'kura_ai_logs';

        $wpdb->insert(
            $table_name,
            array(
                'log_type' => 'api_usage',
                'log_message' => sprintf(
                    'OpenAI API call to %s used %d tokens',
                    $endpoint,
                    $tokens
                ),
                'log_data' => maybe_serialize(array(
                    'endpoint' => $endpoint,
                    'tokens' => $tokens,
                    'timestamp' => current_time('mysql')
                )),
                'severity' => 'info'
            ),
            array('%s', '%s', '%s', '%s')
        );
    }
}