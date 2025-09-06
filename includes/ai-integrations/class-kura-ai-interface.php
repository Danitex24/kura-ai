<?php
/**
 * AI Integration Interface
 *
 * @package    Kura_AI
 * @subpackage Kura_AI/includes/ai-integrations
 * @author     Daniel Abughdyer <daniel@danovatesolutions.org>
 */

namespace Kura_AI;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// WordPress core includes
require_once ABSPATH . 'wp-includes/class-wp-error.php';

// Import WordPress classes
use WP_Error;

interface Kura_AI_Interface
{
    /**
     * Constructor with API key
     * 
     * @param string $api_key The API key for authentication
     */
    public function __construct($api_key);

    /**
     * Get a suggestion from the AI service
     *
     * @param array $issue The issue to get a suggestion for
     * @return string|\WP_Error The suggestion or \WP_Error on failure
     */
    public function get_suggestion($issue);

    /**
     * Verify API connection
     *
     * @return bool|\WP_Error True if valid, error message if not
     */
    public function verify_connection();
}