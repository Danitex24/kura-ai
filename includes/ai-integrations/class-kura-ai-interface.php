<?php
/**
 * AI Integration Interface
 *
 * @package    Kura_AI
 * @subpackage Kura_AI/includes/ai-integrations
 * @author     Daniel Abughdyer <daniel@danovatesolutions.org>
 */

interface Kura_AI_Interface
{
    /**
     * Get AI-powered suggestion for security issue
     *
     * @param array $issue The security issue details
     * @return string AI-generated suggestion
     */
    public function get_suggestion($issue);

    /**
     * Verify API connection
     *
     * @return bool|string True if valid, error message if not
     */
    public function verify_connection();
}