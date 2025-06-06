<?php
class Kura_AI_OAuth_Handler
{
    private $providers = [
        'openai' => [
            'auth_url' => 'https://auth0.openai.com/authorize',
            'token_url' => 'https://auth0.openai.com/oauth/token',
            'scopes' => 'danieladasho@gmail.com',
            'client_id' => 'proj_csBHYYdgryVM69btmSrXy8yn',
            'redirect_uri' => ''
        ],
        'gemini' => [
            'auth_url' => 'https://accounts.google.com/o/oauth2/auth',
            'token_url' => 'https://oauth2.googleapis.com/token',
            'scopes' => 'https://www.googleapis.com/auth/cloud-platform',
            'client_id' => 'YOUR_GOOGLE_CLIENT_ID',
            'redirect_uri' => ''
        ]
    ];

    public function __construct()
    {
        $this->providers['openai']['redirect_uri'] = admin_url('admin-ajax.php?action=kura_ai_oauth_callback&provider=openai');
        $this->providers['gemini']['redirect_uri'] = admin_url('admin-ajax.php?action=kura_ai_oauth_callback&provider=gemini');
    }

    public function get_auth_url($provider)
    {
        if (!isset($this->providers[$provider])) {
            return false;
        }

        $params = [
            'response_type' => 'code',
            'client_id' => $this->providers[$provider]['client_id'],
            'redirect_uri' => $this->providers[$provider]['redirect_uri'],
            'scope' => $this->providers[$provider]['scopes'],
            'state' => wp_create_nonce('kura_ai_oauth_' . $provider)
        ];

        return $this->providers[$provider]['auth_url'] . '?' . http_build_query($params);
    }

    public function handle_callback($provider, $code)
    {
        // Validate state/nonce first
        if (!wp_verify_nonce($_GET['state'], 'kura_ai_oauth_' . $provider)) {
            return new WP_Error('invalid_nonce', 'Invalid OAuth state');
        }

        $response = wp_remote_post($this->providers[$provider]['token_url'], [
            'body' => [
                'grant_type' => 'authorization_code',
                'client_id' => $this->providers[$provider]['client_id'],
                'client_secret' => $this->get_client_secret($provider),
                'code' => $code,
                'redirect_uri' => $this->providers[$provider]['redirect_uri']
            ]
        ]);

        // Process and store tokens
        return $this->process_token_response($provider, $response);
    }

    private function process_token_response($provider, $response)
    {
        // Implementation for token processing
    }
}