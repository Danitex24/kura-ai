<?php
class Kura_AI_OAuth_Handler
{
    private $providers = [
        'openai' => [
            'auth_url' => 'https://auth0.openai.com/authorize',
            'token_url' => 'https://auth0.openai.com/oauth/token',
            'scopes' => 'openid profile email offline_access',
            'client_id' => 'proj_csBHYYdgryVM69btmSrXy8yn',
            'redirect_uri' => '',
            'auth_params' => [
                'audience' => 'https://api.openai.com/v1'
            ]
        ],
        'gemini' => [
            'auth_url' => 'https://accounts.google.com/o/oauth2/auth',
            'token_url' => 'https://oauth2.googleapis.com/token',
            'scopes' => 'https://www.googleapis.com/auth/cloud-platform',
            'client_id' => 'YOUR_GOOGLE_CLIENT_ID',
            'redirect_uri' => '',
            'auth_params' => [
                'access_type' => 'offline',
                'prompt' => 'consent'
            ]
        ]
    ];

    public function __construct()
    {
        $this->providers['openai']['redirect_uri'] = admin_url('admin-ajax.php?action=kura_ai_oauth_callback&provider=openai');
        $this->providers['gemini']['redirect_uri'] = admin_url('admin-ajax.php?action=kura_ai_oauth_callback&provider=gemini');
    }

    public function get_auth_url($provider, $state)
    {
        if (!isset($this->providers[$provider])) {
            return new WP_Error('invalid_provider', __('Invalid OAuth provider', 'kura-ai'));
        }

        $params = [
            'response_type' => 'code',
            'client_id' => $this->providers[$provider]['client_id'],
            'redirect_uri' => $this->providers[$provider]['redirect_uri'],
            'scope' => $this->providers[$provider]['scopes'],
            'state' => $state
        ];

        // Add provider-specific auth parameters
        if (!empty($this->providers[$provider]['auth_params'])) {
            $params = array_merge($params, $this->providers[$provider]['auth_params']);
        }

        return $this->providers[$provider]['auth_url'] . '?' . http_build_query($params);
    }

    public function handle_callback($provider, $code, $state)
    {
        // State is validated in the admin class

        $token_params = [
            'grant_type' => 'authorization_code',
            'client_id' => $this->providers[$provider]['client_id'],
            'client_secret' => $this->get_client_secret($provider),
            'code' => $code,
            'redirect_uri' => $this->providers[$provider]['redirect_uri']
        ];

        // Add provider-specific token parameters
        if ($provider === 'openai') {
            $token_params['audience'] = 'https://api.openai.com/v1';
        }

        $response = wp_remote_post($this->providers[$provider]['token_url'], [
            'headers' => [
                'Accept' => 'application/json'
            ],
            'body' => $token_params,
            'timeout' => 30
        ]);

        return $this->process_token_response($provider, $response);
    }

    public function refresh_token($provider, $refresh_token)
    {
        if (!isset($this->providers[$provider])) {
            return new WP_Error('invalid_provider', __('Invalid OAuth provider', 'kura-ai'));
        }

        if (empty($refresh_token)) {
            return new WP_Error('missing_refresh_token', __('No refresh token available', 'kura-ai'));
        }

        $token_params = [
            'grant_type' => 'refresh_token',
            'client_id' => $this->providers[$provider]['client_id'],
            'client_secret' => $this->get_client_secret($provider),
            'refresh_token' => $refresh_token
        ];

        $response = wp_remote_post($this->providers[$provider]['token_url'], [
            'headers' => [
                'Accept' => 'application/json'
            ],
            'body' => $token_params,
            'timeout' => 30
        ]);

        return $this->process_token_response($provider, $response);
    }

    private function process_token_response($provider, $response)
    {
        if (is_wp_error($response)) {
            return new WP_Error('oauth_error', __('Failed to get access token', 'kura-ai'), $response->get_error_message());
        }

        $status_code = wp_remote_retrieve_response_code($response);
        $body = json_decode(wp_remote_retrieve_body($response), true);

        if ($status_code !== 200) {
            $error_message = __('OAuth token request failed', 'kura-ai');
            if (isset($body['error'])) {
                $error_message = $body['error_description'] ?? $body['error'];
                if (is_array($error_message)) {
                    $error_message = implode(', ', $error_message);
                }
            }
            return new WP_Error('oauth_error', $error_message, [
                'status_code' => $status_code,
                'response' => $body
            ]);
        }

        if (empty($body['access_token'])) {
            return new WP_Error('oauth_error', __('Invalid token response - no access token', 'kura-ai'));
        }

        // Store the relevant tokens
        $tokens = [
            'access_token' => sanitize_text_field($body['access_token']),
            'refresh_token' => isset($body['refresh_token']) ? sanitize_text_field($body['refresh_token']) : '',
            'expires_in' => isset($body['expires_in']) ? absint($body['expires_in']) : 3600,
            'token_type' => isset($body['token_type']) ? sanitize_text_field($body['token_type']) : 'Bearer',
            'scope' => isset($body['scope']) ? sanitize_text_field($body['scope']) : '',
            'created' => time()
        ];

        // For Google, we might get an id_token as well
        if ($provider === 'gemini' && !empty($body['id_token'])) {
            $tokens['id_token'] = sanitize_text_field($body['id_token']);
        }

        $this->store_tokens($provider, $tokens);

        return $tokens;
    }

    private function get_client_secret($provider)
    {
        $settings = get_option('kura_ai_settings');
        switch ($provider) {
            case 'openai':
                return isset($settings['openai_client_secret']) ? sanitize_text_field($settings['openai_client_secret']) : '';
            case 'gemini':
                return isset($settings['gemini_client_secret']) ? sanitize_text_field($settings['gemini_client_secret']) : '';
            default:
                return '';
        }
    }

    private function store_tokens($provider, $tokens)
    {
        $user_id = get_current_user_id();
        if (!$user_id) {
            return new WP_Error('user_not_logged_in', __('User not logged in', 'kura-ai'));
        }

        update_user_meta($user_id, 'kura_ai_' . $provider . '_access_token', $tokens['access_token']);
        update_user_meta($user_id, 'kura_ai_' . $provider . '_refresh_token', $tokens['refresh_token']);
        update_user_meta($user_id, 'kura_ai_' . $provider . '_token_created', $tokens['created']);
        update_user_meta($user_id, 'kura_ai_' . $provider . '_expires_in', $tokens['expires_in']);

        return true;
    }

    private function get_tokens($provider)
    {
        $user_id = get_current_user_id();
        if (!$user_id) {
            return null;
        }

        $access_token = get_user_meta($user_id, 'kura_ai_' . $provider . '_access_token', true);
        if (empty($access_token)) {
            return null;
        }

        return [
            'access_token' => $access_token,
            'refresh_token' => get_user_meta($user_id, 'kura_ai_' . $provider . '_refresh_token', true),
            'created' => get_user_meta($user_id, 'kura_ai_' . $provider . '_token_created', true),
            'expires_in' => get_user_meta($user_id, 'kura_ai_' . $provider . '_expires_in', true),
        ];
    }

    public function get_provider_config($provider)
    {
        return isset($this->providers[$provider]) ? $this->providers[$provider] : null;
    }
}