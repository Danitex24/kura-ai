(function($) {
    'use strict';

    class KuraAIAnalysis {
        constructor() {
            this.analyzeBtn = $('#kura-ai-analyze');
            this.codeInput = $('#code-input');
            this.contextInput = $('#context-input');
            this.resultsCard = $('#kura-ai-results');
            this.feedbackCard = $('#kura-ai-feedback-card');
            this.resultsContent = $('.kura-ai-results-content');
            this.timestamp = $('.kura-ai-timestamp');
            this.feedbackButtons = $('.kura-ai-feedback-btn');
            this.feedbackComment = $('.kura-ai-feedback-comment');
            this.submitFeedbackBtn = $('.kura-ai-submit-feedback');

            this.bindEvents();
        }

        bindEvents() {
            this.analyzeBtn.on('click', (e) => this.handleAnalysis(e));
            this.feedbackButtons.on('click', (e) => this.handleFeedback(e));
            this.submitFeedbackBtn.on('click', (e) => this.submitFeedback(e));
        }

        handleAnalysis(e) {
            e.preventDefault();

            const code = this.codeInput.val().trim();
            const context = this.contextInput.val().trim();

            if (!code) {
                Swal.fire({
                    title: 'Code Required',
                    text: 'Please enter code to analyze.',
                    icon: 'warning',
                    confirmButtonText: 'OK'
                });
                return;
            }

            // Show SweetAlert loading modal
            Swal.fire({
                title: 'Analyzing Code...',
                text: 'Please wait while we analyze your code for security vulnerabilities and best practices.',
                icon: 'info',
                allowOutsideClick: false,
                allowEscapeKey: false,
                showConfirmButton: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            $.ajax({
                url: (typeof kura_ai_ajax !== 'undefined' ? kura_ai_ajax.ajax_url : ajaxurl) || '/wp-admin/admin-ajax.php',
                type: 'POST',
                data: {
                    action: 'kura_ai_analyze_code',
                    _wpnonce: (typeof kura_ai_ajax !== 'undefined' ? kura_ai_ajax.nonce : $('#_wpnonce').val()) || '',
                    code: code,
                    context: context
                },
                success: (response) => {
                    if (response.success) {
                        // Close loading modal and show success
                        Swal.fire({
                            title: 'Analysis Complete!',
                            text: `Code analysis completed successfully. Health Score: ${response.data.health_score || 'N/A'}`,
                            icon: 'success',
                            confirmButtonText: 'View Results',
                            showCancelButton: true,
                            cancelButtonText: 'View Dashboard'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                // Show results on current page
                                this.displayResults(response.data);
                            } else if (result.dismiss === Swal.DismissReason.cancel) {
                                // Redirect to analytics dashboard
                                window.location.href = 'admin.php?page=kura-ai-analytics-dashboard';
                            }
                        });
                        
                        // Always display results regardless of user choice
                        this.displayResults(response.data);
                        
                        // Trigger analytics dashboard refresh if it exists on the page
                        if (typeof window.refreshAnalyticsDashboard === 'function') {
                            window.refreshAnalyticsDashboard();
                        }
                    } else {
                        Swal.fire({
                            title: 'Analysis Failed',
                            text: response.data?.message || 'Analysis failed. Please try again.',
                            icon: 'error',
                            confirmButtonText: 'OK'
                        });
                    }
                },
                error: () => {
                    Swal.fire({
                        title: 'Analysis Failed',
                        text: 'Analysis failed due to a network error. Please try again.',
                        icon: 'error',
                        confirmButtonText: 'OK'
                    });
                },
                complete: () => {
                    // Ensure loading is hidden in case of any issues
                    this.hideLoading();
                }
            });
        }

        displayResults(data) {
            // Update timestamp
            const now = new Date();
            this.timestamp.text(`Analysis completed at ${now.toLocaleString()}`);

            // Display analysis content
            if (data.analysis) {
                this.resultsContent.html(`<div class="kura-ai-analysis-result">${data.analysis}</div>`);
            } else {
                this.resultsContent.html('<p>No analysis results available.</p>');
            }

            // Show results and feedback cards
            this.resultsCard.slideDown();
            this.feedbackCard.slideDown();
        }

        createVulnerabilityItem(vuln) {
            return $(`
                <div class="vulnerability-item ${vuln.severity.toLowerCase()}">
                    <div class="vulnerability-header">
                        <span class="vulnerability-title">${this.escapeHtml(vuln.title)}</span>
                        <span class="vulnerability-severity ${vuln.severity.toLowerCase()}">
                            ${this.escapeHtml(vuln.severity)}
                        </span>
                    </div>
                    <div class="vulnerability-description">
                        ${this.escapeHtml(vuln.description)}
                    </div>
                    ${vuln.code_snippet ? `
                        <div class="vulnerability-code">
                            ${this.escapeHtml(vuln.code_snippet)}
                        </div>
                    ` : ''}
                    <div class="vulnerability-recommendation">
                        ${this.escapeHtml(vuln.recommendation)}
                    </div>
                </div>
            `);
        }

        createBreakdownItem(provider) {
            const confidence = Math.round(provider.confidence * 100);
            return $(`
                <div class="breakdown-item">
                    <h4>${this.escapeHtml(provider.name)}</h4>
                    <div class="confidence-meter">
                        <div class="confidence-bar" style="width: ${confidence}%;"></div>
                        <span class="confidence-label">${confidence}% ${kura_ai_ajax.confidence_text}</span>
                    </div>
                </div>
            `);
        }

        handleFeedback(e) {
            const button = $(e.currentTarget);
            const feedbackValue = button.data('feedback');

            // Update button states
            this.feedbackButtons.removeClass('selected');
            button.addClass('selected');

            // Show comment section for negative feedback
            if (feedbackValue === 'not_helpful') {
                this.feedbackComment.slideDown();
            } else {
                this.feedbackComment.slideUp();
                // Submit positive feedback immediately
                this.submitFeedbackData(feedbackValue, '');
            }
        }

        submitFeedback(e) {
            e.preventDefault();
            const feedbackValue = this.feedbackButtons.filter('.selected').data('feedback');
            const comment = $('#kura-ai-feedback-text').val().trim();
            
            this.submitFeedbackData(feedbackValue, comment);
        }

        submitFeedbackData(feedback, comment) {
            $.ajax({
                url: (typeof kura_ai_ajax !== 'undefined' ? kura_ai_ajax.ajax_url : ajaxurl) || '/wp-admin/admin-ajax.php',
                type: 'POST',
                data: {
                    action: 'kura_ai_submit_feedback',
                    _wpnonce: (typeof kura_ai_ajax !== 'undefined' ? kura_ai_ajax.nonce : $('#_wpnonce').val()) || '',
                    feedback: feedback,
                    comment: comment
                },
                success: (response) => {
                    if (response.success) {
                        this.showNotice('success', 'Thank you for your feedback!');
                        this.feedbackComment.slideUp();
                    } else {
                        this.showNotice('error', response.data?.message || 'Failed to submit feedback.');
                    }
                },
                error: () => {
                    this.showNotice('error', 'Failed to submit feedback.');
                }
            });
        }

        showLoading() {
            this.analyzeBtn
                .prop('disabled', true)
                .html('<span class="dashicons dashicons-update-alt kura-ai-loading"></span> Analyzing...');
        }

        hideLoading() {
            this.analyzeBtn
                .prop('disabled', false)
                .html('<span class="dashicons dashicons-search"></span> Analyze Code');
        }

        showNotice(type, message) {
            // Remove existing notices
            $('.kura-ai-notice').remove();
            
            // Create new notice
            const noticeClass = type === 'error' ? 'notice-error' : 'notice-success';
            const notice = $(`
                <div class="notice ${noticeClass} is-dismissible kura-ai-notice">
                    <p>${message}</p>
                    <button type="button" class="notice-dismiss">
                        <span class="screen-reader-text">Dismiss this notice.</span>
                    </button>
                </div>
            `);
            
            // Insert notice after header
            $('.kura-ai-header').after(notice);
            
            // Auto-dismiss success notices
            if (type === 'success') {
                setTimeout(() => {
                    notice.fadeOut(() => notice.remove());
                }, 3000);
            }
            
            // Handle dismiss button
            notice.find('.notice-dismiss').on('click', () => {
                notice.fadeOut(() => notice.remove());
            });
        }

    }

    // Initialize when document is ready
    $(document).ready(() => {
        new KuraAIAnalysis();
    });

})(jQuery);