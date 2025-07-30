<div class="wrap">
    <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
    <p><?php esc_html_e( 'Audit your WooCommerce store with the power of AI.', 'kura-ai' ); ?></p>
    <button id="kura-ai-run-audit" class="button button-primary"><?php esc_html_e( 'Run AI Audit', 'kura-ai' ); ?></button>
    <a href="#" id="kura-ai-export-audit" class="button"><?php esc_html_e( 'Export Audit', 'kura-ai' ); ?></a>
    <div id="kura-ai-audit-results" style="margin-top: 20px;"></div>
    <div id="kura-ai-audit-cta" style="margin-top: 20px; display: none;">
        <p><?php esc_html_e( 'Upgrade to KuraAI Pro to unlock unlimited smart suggestions and deeper optimization insights.', 'kura-ai' ); ?></p>
    </div>
</div>
