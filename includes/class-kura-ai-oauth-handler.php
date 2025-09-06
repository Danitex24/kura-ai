<?php

namespace Kura_AI;

use \WP_Error;

class Kura_AI_OAuth_Handler
{
    private $providers;
    public function __construct()
    {
        $this->providers = [
            'openai' => [
                'auth_url' => 'https://auth.openai.com/oauth/authorize',
                'token_url' => 'https://auth.openai.com/oauth/token',
                'client_id' => defined('KURA_AI_OPENAI_CLIENT_ID') ? KURA_AI_OPENAI_CLIENT_ID : '',
                'client_secret' => defined('KURA_AI_OPENAI_CLIENT_SECRET') ? KURA_AI_OPENAI_CLIENT_SECRET : '',
                'scopes' => 'openid email profile',
                'redirect_uri' => \site_url('/wp-admin/admin.php?page=kura-ai-settings&action=kura_ai_oauth_callback&provider=openai')
            ],
            'gemini' => [
                'auth_url' => 'https://accounts.google.com/o/oauth2/auth',
                'token_url' => 'https://oauth2.googleapis.com/token',
                'client_id' => defined('KURA_AI_GEMINI_CLIENT_ID') ? KURA_AI_GEMINI_CLIENT_ID : '',
                'client_secret' => defined('KURA_AI_GEMINI_CLIENT_SECRET') ? KURA_AI_GEMINI_CLIENT_SECRET : '',
                'scopes' => 'https://www.googleapis.com/auth/cloud-platform',
                'redirect_uri' => \admin_url('admin.php?page=kura-ai-settings&action=kura_ai_oauth_callback&provider=gemini'),
                'access_type' => 'offline',
                'prompt' => 'consent'
            ]
        ];
    }

    public function get_auth_url($provider, $state)
    {
        error_log("Generating auth URL for $provider with state: $state");

        if (!isset($this->providers[$provider])) {
            return new WP_Error('invalid_provider', \__('Invalid OAuth provider', 'kura-ai'));
        }

        $config = $this->providers[$provider];
        $params = [
            'response_type' => 'code',
            'client_id' => $config['client_id'],
            'redirect_uri' => $config['redirect_uri'],
            'scope' => $config['scopes'],
            'state' => $state
        ];

        if ($provider === 'gemini') {
            $params['access_type'] = 'offline';
            $params['prompt'] = 'consent';
        }

        $auth_url = $config['auth_url'] . '?' . http_build_query($params);
        error_log("Final auth URL: $auth_url");
        return $auth_url;
    }

    public function handle_callback($provider, $code)
    {
        error_log("Starting callback for $provider");
        error_log("Received code: " . ($code ? 'PROVIDED' : 'MISSING'));

        $state = isset($_GET['state']) ? sanitize_text_field($_GET['state']) : '';
        error_log("Received state: " . ($state ?: 'MISSING'));

        if (!isset($this->providers[$provider])) {
            error_log("Invalid provider: $provider");
            return new WP_Error('invalid_provider', \__('Invalid OAuth provider', 'kura-ai'));
        }

        // Verify state
        $expected_provider = \get_transient('kura_ai_oauth_state_' . $state);
        if (!$expected_provider || $expected_provider !== $provider) {
            error_log("State verification failed. Expected: $provider, Got: " . ($expected_provider ?: 'NONE'));
            return new WP_Error('invalid_state', \__('Invalid OAuth state', 'kura-ai'));
        }

        // Clean up transient
        \delete_transient('kura_ai_oauth_state_' . $state);

        $config = $this->providers[$provider];

        $response = \wp_remote_post($config['token_url'], [
            'body' => [
                'code' => $code,
                'client_id' => $config['client_id'],
                'client_secret' => $config['client_secret'],
                'redirect_uri' => $config['redirect_uri'],
                'grant_type' => 'authorization_code'
            ]
        ]);

        if (\is_wp_error($response)) {
            error_log("Token request failed: " . $response->get_error_message());
            return $response;
        }

        $body = json_decode(\wp_remote_retrieve_body($response), true);
        error_log("Token response: " . print_r($body, true));

        if (isset($body['error'])) {
            error_log("OAuth error: " . ($body['error_description'] ?? $body['error']));
            return new WP_Error('oauth_error', $body['error_description'] ?? $body['error']);
        }

        return [
            'access_token' => $body['access_token'],
            'refresh_token' => $body['refresh_token'] ?? null,
            'expires_in' => $body['expires_in'] ?? 3600,
            'token_type' => $body['token_type'] ?? 'Bearer',
            'created' => time()
        ];
    }

    public function refresh_token($provider, $refresh_token)
    {
        if (!isset($this->providers[$provider])) {
            return new WP_Error('invalid_provider', \__('Invalid OAuth provider', 'kura-ai'));
        }

        $config = $this->providers[$provider];

        $response = \wp_remote_post($config['token_url'], [
            'body' => [
                'refresh_token' => $refresh_token,
                'client_id' => $config['client_id'],
                'client_secret' => $config['client_secret'],
                'grant_type' => 'refresh_token'
            ]
        ]);

        if (\is_wp_error($response)) {
            return $response;
        }

        $body = \json_decode(\wp_remote_retrieve_body($response), true);

        if (isset($body['error'])) {
            return new WP_Error('oauth_error', $body['error_description'] ?? $body['error']);
        }

        return [
            'access_token' => $body['access_token'],
            'refresh_token' => $body['refresh_token'] ?? $refresh_token,
            'expires_in' => $body['expires_in'] ?? 3600,
            'created' => time()
        ];
    }
}