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
            
            // Show progress dialog
            Swal.fire({
                title: 'Applying Security Rules',
                html: `
                    <div class="modal-progress-bar">
                        <div class="modal-progress-fill" id="modal-progress-fill"></div>
                        <div class="modal-progress-text" id="modal-progress-text">Initializing...</div>
                    </div>
                    <div class="progress-container">
                        <div class="progress-step active">
                            <i class="fas fa-check-circle"></i>
                            <span>Validating .htaccess file permissions...</span>
                            <small>Checking if .htaccess file is writable</small>
                        </div>
                        <div class="progress-step" id="step-backup">
                            <i class="fas fa-circle-notch fa-spin"></i>
                            <span>Creating backup of existing rules...</span>
                            <small>Preserving current .htaccess content</small>
                        </div>
                        <div class="progress-step" id="step-rules">
                            <i class="fas fa-circle"></i>
                            <span>Applying security hardening rules...</span>
                            <small>Adding directory protection, file blocking, bot filtering</small>
                        </div>
                        <div class="progress-step" id="step-verify">
                            <i class="fas fa-circle"></i>
                            <span>Verifying rule installation...</span>
                            <small>Confirming all security measures are active</small>
                        </div>
                        <div class="progress-step" id="step-details" style="display: none;">
                            <i class="fas fa-info-circle"></i>
                            <span>Security Rules Being Applied:</span>
                            <div class="rule-details">
                                <ul>
                                    <li>• Directory browsing protection</li>
                                    <li>• .htaccess file access blocking</li>
                                    <li>• wp-config.php protection</li>
                                    <li>• Sensitive file access prevention</li>
                                    <li>• Script injection blocking</li>
                                    <li>• Malicious bot filtering</li>
                                    <li>• Image hotlinking prevention</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                `,
                allowOutsideClick: false,
                allowEscapeKey: false,
                showConfirmButton: false,
                didOpen: () => {
                    // Update modal progress bar
                    this.updateModalProgressBar(10, 'Validating permissions...');
                    
                    // Show details after initial validation
                    setTimeout(() => {
                        $('#step-details').slideDown(300);
                        this.updateModalProgressBar(20, 'Preparing backup...');
                    }, 1500);
                    
                    // Simulate progress steps with slower timing
                    setTimeout(() => {
                        $('#step-backup').addClass('active');
                        $('#step-backup i').removeClass('fa-circle-notch fa-spin').addClass('fa-check-circle');
                        this.updateModalProgressBar(40, 'Backup completed');
                    }, 2500);
                    
                    setTimeout(() => {
                        $('#step-rules').addClass('active');
                        $('#step-rules i').removeClass('fa-circle').addClass('fa-circle-notch fa-spin');
                        this.updateModalProgressBar(60, 'Installing security rules...');
                    }, 3500);
                    
                    setTimeout(() => {
                        $('#step-verify').addClass('active');
                        $('#step-verify i').removeClass('fa-circle').addClass('fa-circle-notch fa-spin');
                        $('#step-rules i').removeClass('fa-circle-notch fa-spin').addClass('fa-check-circle');
                        this.updateModalProgressBar(80, 'Verifying installation...');
                    }, 4500);
                }
            });

            $.ajax({
                url: kura_ai_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'kura_ai_apply_htaccess_rules',
                    nonce: kura_ai_ajax.nonce
                },
                success: (response) => {
                    setTimeout(() => {
                        $('#step-verify i').removeClass('fa-circle-notch fa-spin').addClass('fa-check-circle');
                        this.updateModalProgressBar(100, 'Security rules applied successfully!');
                        
                        setTimeout(() => {
                            // Close the progress modal first
                            Swal.close();
                            
                            // Then show the result modal
                            setTimeout(() => {
                                if (response.success) {
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'Security Rules Applied!',
                                        html: `
                                            <div class="success-message">
                                                <p>${response.data.message}</p>
                                                <div class="applied-rules">
                                                    <h4>Applied Security Rules:</h4>
                                                    <ul>
                                                        <li><i class="fas fa-shield-alt"></i> Directory browsing disabled</li>
                                                        <li><i class="fas fa-lock"></i> .htaccess file protected</li>
                                                        <li><i class="fas fa-file-shield"></i> wp-config.php secured</li>
                                                        <li><i class="fas fa-ban"></i> Sensitive files blocked</li>
                                                        <li><i class="fas fa-robot"></i> Bad bots blocked</li>
                                                        <li><i class="fas fa-link"></i> Hotlinking prevented</li>
                                                    </ul>
                                                </div>
                                            </div>
                                        `,
                                        confirmButtonText: 'Great!',
                                        allowOutsideClick: true,
                                        allowEscapeKey: true
                                    }).then(() => {
                                        window.location.reload();
                                    });
                                } else {
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'Application Failed',
                                        text: response.data.message,
                                        confirmButtonText: 'OK',
                                        allowOutsideClick: true,
                                        allowEscapeKey: true
                                    });
                                }
                            }, 300);
                        }, 500);
                    }, 500);
                },
                error: () => {
                    // Close the progress modal first
                    Swal.close();
                    
                    // Then show the error modal
                    setTimeout(() => {
                        Swal.fire({
                            icon: 'error',
                            title: 'Connection Error',
                            text: 'Failed to connect to server. Please try again.',
                            confirmButtonText: 'OK',
                            allowOutsideClick: true,
                            allowEscapeKey: true
                        });
                    }, 300);
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
        
        /**
         * Update modal progress bar
         */
        updateModalProgressBar(percentage, text) {
            $('#modal-progress-fill').css('width', percentage + '%');
            $('#modal-progress-text').text(text);
        }
    }

    // Initialize when document is ready
    $(document).ready(() => {
        new SecurityHardening();
    });

})(jQuery);