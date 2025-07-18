<?php
/**
 * AI Integration Interface
 *
 * @package    Kura_AI
 * @subpackage Kura_AI/includes/interfaces
 */

interface Kura_AI_Interface
{
    /**
     * Constructor with OAuth tokens
     * @param array $oauth_tokens Array containing access_token, refresh_token, etc.
     */
    public function __construct($oauth_tokens);

    /**
     * Get AI suggestion for security issue
     * @param array $issue The security issue details
     * @return string AI-generated suggestion
     */
    public function get_suggestion($issue);

    /**
     * Verify API connection
     * @return bool|string True if valid, error message if not
     */
    public function verify_connection();
}