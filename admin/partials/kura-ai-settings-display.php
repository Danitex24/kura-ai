<?php
/**
 * Kura AI Settings Page
 *
 * @package    Kura_AI
 * @subpackage Kura_AI/admin/partials
 * @author     Daniel Abughdyer <daniel@danovatesolutions.org>
 */

?>
<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    <form action="options.php" method="post">
        <?php
        settings_fields('kura_ai_settings_group');
        do_settings_sections('kura-ai-settings');
        submit_button('Save Settings');
        ?>
    </form>
</div>
