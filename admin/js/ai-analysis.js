(function($) {
    'use strict';

    class KuraAIAnalysis {
        constructor() {
            this.form = $('#code-analysis-form');
            this.resultsContainer = $('#analysis-results');
            this.vulnerabilitiesList = $('.vulnerabilities-list');
            this.breakdownList = $('.breakdown-list');
            this.overallConfidence = $('.overall-confidence .confidence-bar');
            this.confidenceValue = $('.overall-confidence .confidence-value');
            this.feedbackButtons = $('.feedback-btn');

            this.bindEvents();
        }

        bindEvents() {
            this.form.on('submit', (e) => this.handleAnalysis(e));
            this.feedbackButtons.on('click', (e) => this.handleFeedback(e));
        }

        handleAnalysis(e) {
            e.preventDefault();

            const code = $('#code-input').val().trim();
            const context = $('#context-input').val().trim();

            if (!code) {
                this.showNotice('error', kura_ai_ajax.empty_code_error);
                return;
            }

            this.showLoading();

            $.ajax({
                url: kura_ai_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'kura_ai_analyze_code',
                    nonce: kura_ai_ajax.nonce,
                    code: code,
                    context: context
                },
                success: (response) => {
                    if (response.success) {
                        this.displayResults(response.data);
                    } else {
                        this.showNotice('error', response.data.message || kura_ai_ajax.analysis_error);
                    }
                },
                error: () => {
                    this.showNotice('error', kura_ai_ajax.analysis_error);
                },
                complete: () => {
                    this.hideLoading();
                }
            });
        }

        displayResults(data) {
            // Clear previous results
            this.vulnerabilitiesList.empty();
            this.breakdownList.empty();

            // Update overall confidence
            const overallConfidence = Math.round(data.overall_confidence * 100);
            this.overallConfidence.css('width', `${overallConfidence}%`);
            this.confidenceValue.text(`${overallConfidence}%`);

            // Display vulnerabilities
            data.vulnerabilities.forEach(vuln => {
                this.vulnerabilitiesList.append(this.createVulnerabilityItem(vuln));
            });

            // Display provider breakdown
            data.provider_results.forEach(provider => {
                this.breakdownList.append(this.createBreakdownItem(provider));
            });

            // Show results section
            this.resultsContainer.slideDown();
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
            const feedbackValue = button.data('value');

            // Disable feedback buttons
            this.feedbackButtons.prop('disabled', true);

            $.ajax({
                url: kura_ai_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'kura_ai_submit_feedback',
                    nonce: kura_ai_ajax.nonce,
                    feedback: feedbackValue
                },
                success: (response) => {
                    if (response.success) {
                        this.showNotice('success', kura_ai_ajax.feedback_success);
                        // Highlight selected button
                        button.addClass('button-primary').siblings().removeClass('button-primary');
                    } else {
                        this.showNotice('error', response.data.message || kura_ai_ajax.feedback_error);
                        // Re-enable feedback buttons on error
                        this.feedbackButtons.prop('disabled', false);
                    }
                },
                error: () => {
                    this.showNotice('error', kura_ai_ajax.feedback_error);
                    // Re-enable feedback buttons on error
                    this.feedbackButtons.prop('disabled', false);
                }
            });
        }

        showLoading() {
            this.form.find('button[type="submit"]')
                .prop('disabled', true)
                .html('<i class="fas fa-spinner fa-spin"></i> ' + kura_ai_ajax.analyzing_text);
        }

        hideLoading() {
            this.form.find('button[type="submit"]')
                .prop('disabled', false)
                .html('<i class="fas fa-search"></i> ' + kura_ai_ajax.analyze_text);
        }

        showNotice(type, message) {
            const notice = $(`
                <div class="notice notice-${type} is-dismissible">
                    <p>${this.escapeHtml(message)}</p>
                </div>
            `);

            // Remove existing notices
            $('.notice').remove();

            // Add new notice before the first card
            $('.kura-ai-card').first().before(notice);

            // Make notice dismissible
            notice.find('.notice-dismiss').on('click', () => {
                notice.fadeOut(300, function() { $(this).remove(); });
            });
        }

        escapeHtml(unsafe) {
            return unsafe
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");
        }
    }

    // Initialize when document is ready
    $(document).ready(() => {
        new KuraAIAnalysis();
    });

})(jQuery);