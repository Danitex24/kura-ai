<?php
/**
 * User Profile OAuth Connections
 *
 * @package    Kura_AI
 * @subpackage Kura_AI/admin/partials
 * @author     Daniel Abughdyer <daniel@danovatesolutions.org>
 */

$user_id = get_current_user_id();
?>
<h2><?php _e('KuraAI Connections', 'kura-ai'); ?></h2>
<table class="form-table">
    <tr>
        <th><?php _e('OpenAI', 'kura-ai'); ?></th>
        <td>
            <?php if (get_user_meta($user_id, 'kura_ai_openai_access_token', true)) : ?>
                <p><?php _e('Connected', 'kura-ai'); ?> ✅</p>
                <button class="button kura-ai-oauth-disconnect" data-provider="openai"><?php _e('Disconnect', 'kura-ai'); ?></button>
            <?php else : ?>
                <p><?php _e('Not Connected', 'kura-ai'); ?> ❌</p>
                <button class="button kura-ai-oauth-connect" data-provider="openai"><?php _e('Connect to OpenAI', 'kura-ai'); ?></button>
            <?php endif; ?>
        </td>
    </tr>
    <tr>
        <th><?php _e('Google Gemini', 'kura-ai'); ?></th>
        <td>
            <?php if (get_user_meta($user_id, 'kura_ai_gemini_access_token', true)) : ?>
                <p><?php _e('Connected', 'kura-ai'); ?> ✅</p>
                <button class="button kura-ai-oauth-disconnect" data-provider="gemini"><?php _e('Disconnect', 'kura-ai'); ?></button>
            <?php else : ?>
                <p><?php _e('Not Connected', 'kura-ai'); ?> ❌</p>
                <button class="button kura-ai-oauth-connect" data-provider="gemini"><?php _e('Connect to Gemini', 'kura-ai'); ?></button>
            <?php endif; ?>
        </td>
    </tr>
</table>
<script>
jQuery(document).ready(function($) {
    $('.kura-ai-oauth-connect').on('click', function(e) {
        e.preventDefault();
        var provider = $(this).data('provider');
        $.post(ajaxurl, {
            action: 'kura_ai_oauth_init',
            provider: provider,
            _wpnonce: '<?php echo wp_create_nonce('kura_ai_oauth_nonce'); ?>'
        }, function(response) {
            if (response.success) {
                window.location.href = response.data.redirect_url;
            } else {
                alert(response.data);
            }
        });
    });

    $('.kura-ai-oauth-disconnect').on('click', function(e) {
        e.preventDefault();
        if (!confirm('<?php _e('Are you sure you want to disconnect?', 'kura-ai'); ?>')) {
            return;
        }
        var provider = $(this).data('provider');
        $.post(ajaxurl, {
            action: 'kura_ai_oauth_disconnect',
            provider: provider,
            _wpnonce: '<?php echo wp_create_nonce('kura_ai_oauth_disconnect'); ?>'
        }, function(response) {
            if (response.success) {
                location.reload();
            } else {
                alert(response.data);
            }
        });
    });
});
</script>
