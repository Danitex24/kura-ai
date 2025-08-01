jQuery(document).ready(function ($) {
    $('#kura-ai-run-audit').on('click', function () {
        var $button = $(this);
        var $results = $('#kura-ai-audit-results');

        $button.prop('disabled', true).text('Running Audit...');
        $results.html('');

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'kura_ai_run_store_audit',
                nonce: kura_ai_woocommerce_admin.nonce
            },
            success: function (response) {
                if (response.success) {
                    $results.html('<p>' + response.data.suggestion.replace(/\n/g, '<br>') + '</p>');
                } else {
                    $results.html('<p>Error: ' + response.data + '</p>');
                }
            },
            error: function (xhr, status, error) {
                $results.html('<p>Error: ' + error + '</p>');
            },
            complete: function () {
                $button.prop('disabled', false).text('Run AI Audit');
                $('#kura-ai-audit-cta').show();
            }
        });
    });

    $('#kura-ai-export-audit-csv').on('click', function (e) {
        e.preventDefault();
        if ( confirm( 'Are you sure you want to export the audit summary to CSV?' ) ) {
            window.location.href = ajaxurl + '?action=kura_ai_export_audit&nonce=' + kura_ai_woocommerce_admin.export_nonce;
        }
    });

    $('#kura-ai-export-audit-pdf').on('click', function (e) {
        e.preventDefault();
        if ( confirm( 'Are you sure you want to export the audit summary to PDF?' ) ) {
            window.location.href = ajaxurl + '?action=kura_ai_export_audit_pdf&nonce=' + kura_ai_woocommerce_admin.export_pdf_nonce;
        }
    });

    $('#kura-ai-competitor-audit-form').on('submit', function (e) {
        e.preventDefault();

        var $form = $(this);
        var $button = $form.find('button');
        var $results = $('#kura-ai-competitor-audit-results');
        var url = $('#kura-ai-competitor-url').val();

        $button.prop('disabled', true).text('Running Audit...');
        $results.html('');

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'kura_ai_run_competitor_audit',
                nonce: kura_ai_woocommerce_admin.competitor_nonce,
                url: url
            },
            success: function (response) {
                if (response.success) {
                    $results.html('<p>' + response.data.suggestion.replace(/\n/g, '<br>') + '</p>');
                } else {
                    $results.html('<p>Error: ' + response.data + '</p>');
                }
            },
            error: function (xhr, status, error) {
                $results.html('<p>Error: ' + error + '</p>');
            },
            complete: function () {
                $button.prop('disabled', false).text('Run AI Audit');
                $('#kura-ai-competitor-audit-cta').show();
            }
        });
    });
});
