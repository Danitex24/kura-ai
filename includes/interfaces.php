<?php
/**
 * AI Integration Interface
 *
 * @package    Kura_AI
 * @subpackage Kura_AI/includes/interfaces
 */

namespace Kura_AI;

// Exit if accessed directly
if (!defined('\ABSPATH')) {
    exit;
}

// WordPress core includes
require_once \ABSPATH . 'wp-includes/class-wp-error.php';

interface Kura_AI_Interface
{
    /**
     * Constructor with API key
     * @param string $api_key The API key for authentication
     */
    public function __construct($api_key);

    /**
     * Get AI suggestion for security issue
     * @param array $issue The security issue details
     * @return string|\WP_Error AI-generated suggestion or error
     */
    public function get_suggestion($issue);

    /**
     * Verify API connection
     * @return bool|\WP_Error True if valid, error message if not
     */
    public function verify_connection();
}