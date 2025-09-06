(function($) {
    'use strict';

    /**
     * Security Hardening UI Handler
     */
    class SecurityHardening {
        constructor() {
            this.initializeEventListeners();
        }

        /**
         * Initialize event listeners
         */
        initializeEventListeners() {
            $('#apply-htaccess-rules').on('click', () => this.applyHtaccessRules());
            $('#optimize-database').on('click', () => this.optimizeDatabase());
        }

        /**
         * Apply .htaccess security rules
         */
        applyHtaccessRules() {
            const button = $('#apply-htaccess-rules');
            button.addClass('loading');

            $.ajax({
                url: kura_ai_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'kura_ai_apply_htaccess_rules',
                    nonce: kura_ai_ajax.nonce
                },
                success: (response) => {
                    if (response.success) {
                        Swal.fire({
                            icon: 'success',
                            title: kura_ai_ajax.strings.success,
                            text: response.data.message,
                            confirmButtonText: kura_ai_ajax.strings.ok,
                            allowOutsideClick: true,
                            allowEscapeKey: true
                        }).then(() => {
                            window.location.reload();
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: kura_ai_ajax.strings.error,
                            text: response.data.message,
                            confirmButtonText: kura_ai_ajax.strings.ok,
                            allowOutsideClick: true,
                            allowEscapeKey: true
                        });
                    }
                },
                error: () => {
                    Swal.fire({
                        icon: 'error',
                        title: kura_ai_ajax.strings.error,
                        text: kura_ai_ajax.strings.htaccess_error,
                        confirmButtonText: kura_ai_ajax.strings.ok,
                        allowOutsideClick: true,
                        allowEscapeKey: true
                    });
                },
                complete: () => {
                    button.removeClass('loading');
                }
            });
        }

        /**
         * Optimize WordPress database
         */
        optimizeDatabase() {
            const button = $('#optimize-database');
            button.addClass('loading');

            $.ajax({
                url: kura_ai_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'kura_ai_optimize_database',
                    nonce: kura_ai_ajax.nonce
                },
                success: (response) => {
                    if (response.success) {
                        const details = response.data.details;
                        let detailsHtml = '<ul class="optimization-details">';
                        
                        for (const [key, value] of Object.entries(details)) {
                            const label = key.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase());
                            detailsHtml += `<li>${label}: ${value}</li>`;
                        }
                        
                        detailsHtml += '</ul>';

                        Swal.fire({
                            icon: 'success',
                            title: kura_ai_ajax.strings.success,
                            html: `${response.data.message}<br><br>${detailsHtml}`,
                            confirmButtonText: kura_ai_ajax.strings.ok,
                            allowOutsideClick: true,
                            allowEscapeKey: true
                        }).then(() => {
                            window.location.reload();
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: kura_ai_ajax.strings.error,
                            text: response.data.message,
                            confirmButtonText: kura_ai_ajax.strings.ok,
                            allowOutsideClick: true,
                            allowEscapeKey: true
                        });
                    }
                },
                error: () => {
                    Swal.fire({
                        icon: 'error',
                        title: kura_ai_ajax.strings.error,
                        text: kura_ai_ajax.strings.optimization_error,
                        confirmButtonText: kura_ai_ajax.strings.ok,
                        allowOutsideClick: true,
                        allowEscapeKey: true
                    });
                },
                complete: () => {
                    button.removeClass('loading');
                }
            });
        }
    }

    // Initialize when document is ready
    $(document).ready(() => {
        new SecurityHardening();
    });

})(jQuery);