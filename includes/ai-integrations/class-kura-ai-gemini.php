<?php
/**
 * Gemini Integration (Coming Soon)
 *
 * @package    Kura_AI
 * @subpackage Kura_AI/includes/ai-integrations
 * @author     Daniel Abughdyer <daniel@danovatesolutions.org>
 */

class Kura_AI_Gemini implements Kura_AI_Interface
{
    public function __construct($api_key)
    {
        // Implementation coming in future update
    }

    public function get_suggestion($issue)
    {
        return __('Gemini AI integration is coming in a future update.', 'kura-ai');
    }

    public function verify_connection()
    {
        return __('Gemini integration not yet available', 'kura-ai');
    }
}