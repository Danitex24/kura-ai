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
            }
        });
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
            }
        });
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
            }
        });
    });
});
