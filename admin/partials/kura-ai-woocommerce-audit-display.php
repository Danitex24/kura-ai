<div class="wrap">
    <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
    <p><?php esc_html_e( 'Audit your WooCommerce store with the power of AI.', 'kura-ai' ); ?></p>
    <button id="kura-ai-run-audit" class="button button-primary"><?php esc_html_e( 'Run AI Audit', 'kura-ai' ); ?></button>
    <a href="#" id="kura-ai-export-audit-csv" class="button"><?php esc_html_e( 'Export to CSV', 'kura-ai' ); ?></a>
    <a href="#" id="kura-ai-export-audit-pdf" class="button"><?php esc_html_e( 'Export to PDF', 'kura-ai' ); ?></a>
    <div id="kura-ai-audit-results" style="margin-top: 20px;"></div>
    <div id="kura-ai-audit-charts" style="margin-top: 20px;">
        <canvas id="kura-ai-sales-trend-chart"></canvas>
        <canvas id="kura-ai-top-selling-products-chart"></canvas>
        <canvas id="kura-ai-category-distribution-chart"></canvas>
        <canvas id="kura-ai-store-health-chart"></canvas>
        <canvas id="kura-ai-suggestion-frequency-chart"></canvas>
    </div>
    <div id="kura-ai-audit-cta" style="margin-top: 20px; display: none;">
        <p><?php esc_html_e( 'Upgrade to KuraAI Pro to unlock unlimited smart suggestions and deeper optimization insights.', 'kura-ai' ); ?></p>
    </div>
</div>
