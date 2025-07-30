<div class="wrap">
    <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
    <p><?php esc_html_e( 'Audit your competitor\'s store with the power of AI.', 'kura-ai' ); ?></p>
    <form id="kura-ai-competitor-audit-form">
        <table class="form-table">
            <tr valign="top">
                <th scope="row"><?php esc_html_e( 'Competitor URL', 'kura-ai' ); ?></th>
                <td><input type="text" id="kura-ai-competitor-url" name="kura_ai_competitor_url" class="regular-text" /></td>
            </tr>
        </table>
        <?php submit_button( __( 'Run AI Audit', 'kura-ai' ), 'primary', 'kura-ai-run-competitor-audit' ); ?>
    </form>
    <div id="kura-ai-competitor-audit-results" style="margin-top: 20px;"></div>
    <div id="kura-ai-competitor-audit-cta" style="margin-top: 20px; display: none;">
        <p><?php esc_html_e( 'Upgrade to Pro to audit up to 10 competitors and access detailed product strategy breakdowns.', 'kura-ai' ); ?></p>
    </div>
</div>
