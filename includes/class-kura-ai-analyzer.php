<?php

namespace Kura_AI;

if (!defined('ABSPATH')) {
    exit;
}

require_once ABSPATH . 'wp-admin/includes/plugin.php';
require_once ABSPATH . 'wp-includes/formatting.php';

use \WP_Error;

/**
 * Class for handling AI-powered code analysis
 *
 * @since      1.0.0
 * @package    Kura_AI
 * @subpackage Kura_AI/includes
 */
class Kura_AI_Analyzer {

    /**
     * The OpenAI API endpoint
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $api_endpoint    The OpenAI API endpoint
     */
    private $api_endpoint = 'https://api.openai.com/v1/chat/completions';

    /**
     * The OpenAI API key
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $api_key    The OpenAI API key
     */
    private $api_key;

    /**
     * Initialize the class
     *
     * @since    1.0.0
     */
    public function __construct() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'kura_ai_api_keys';
        $this->api_key = $wpdb->get_var($wpdb->prepare(
            "SELECT api_key FROM $table_name WHERE provider = %s AND status = %s",
            'openai',
            'active'
        ));
    }

    /**
     * Analyze code using OpenAI API
     *
     * @since    1.0.0
     * @param    string    $code       The code to analyze
     * @param    string    $context    Additional context for analysis
     * @return   array|WP_Error       Analysis results or error
     */
    public function analyze_code($code, $context = '') {
        if (empty($this->api_key)) {
            return new \WP_Error('api_key_missing', \__('OpenAI API key is not configured', 'kura-ai'));
        }

        $messages = array(
            array(
                'role' => 'system',
                'content' => 'You are a security expert analyzing WordPress code for vulnerabilities and best practices.'
            ),
            array(
                'role' => 'user',
                'content' => sprintf(
                    "Please analyze this code for security vulnerabilities and WordPress best practices:\n\nContext: %s\n\nCode:\n%s",
                    $context,
                    $code
                )
            )
        );

        $response = \wp_remote_post($this->api_endpoint, array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $this->api_key,
                'Content-Type' => 'application/json'
            ),
            'body' => \wp_json_encode(array(
                'model' => 'gpt-4',
                'messages' => $messages,
                'max_tokens' => 1000,
                'temperature' => 0.7
            )),
            'timeout' => 30
        ));

        if (\is_wp_error($response)) {
            return $response;
        }

        $body = \wp_remote_retrieve_body($response);
        $data = \json_decode($body, true);

        if (empty($data['choices'][0]['message']['content'])) {
            return new \WP_Error('api_error', \__('Failed to get analysis from OpenAI API', 'kura-ai'));
        }

        return array(
            'analysis' => \wp_kses_post($data['choices'][0]['message']['content']),
            'timestamp' => \current_time('mysql')
        );
    }

    /**
     * Submit feedback for an analysis
     *
     * @since    1.0.0
     * @param    int       $analysis_id    The analysis ID
     * @param    string    $feedback       The feedback (helpful/not_helpful)
     * @param    string    $comment        Optional feedback comment
     * @return   bool|WP_Error            True on success, WP_Error on failure
     */
    public function submit_feedback($analysis_id, $feedback, $comment = '') {
        global $wpdb;

        $table_name = $wpdb->prefix . 'kura_ai_feedback';
        $user_id = \get_current_user_id();

        $result = $wpdb->insert(
            $table_name,
            array(
                'analysis_id' => $analysis_id,
                'user_id' => $user_id,
                'feedback' => \sanitize_text_field($feedback),
                'comment' => \sanitize_textarea_field($comment),
                'created_at' => \current_time('mysql')
            ),
            array('%d', '%d', '%s', '%s', '%s')
        );

        if ($result === false) {
            return new \WP_Error('db_error', \__('Failed to save feedback', 'kura-ai'));
        }

        return true;
    }

    /**
     * Get feedback statistics
     *
     * @since    1.0.0
     * @return   array|WP_Error    Feedback stats or error
     */
    public function get_feedback_stats() {
        global $wpdb;

        $table_name = $wpdb->prefix . 'kura_ai_feedback';
        $stats = array(
            'total' => 0,
            'helpful' => 0,
            'not_helpful' => 0
        );

        $results = $wpdb->get_results(
            "SELECT feedback, COUNT(*) as count FROM {$table_name} GROUP BY feedback",
            \ARRAY_A
        );

        if ($results === null) {
            return new \WP_Error('db_error', \__('Failed to retrieve feedback stats', 'kura-ai'));
        }

        foreach ($results as $row) {
            $stats['total'] += $row['count'];
            if ($row['feedback'] === 'helpful') {
                $stats['helpful'] = $row['count'];
            } elseif ($row['feedback'] === 'not_helpful') {
                $stats['not_helpful'] = $row['count'];
            }
        }

        return $stats;
    }
}