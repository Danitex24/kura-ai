(function($) {
    'use strict';

    class ComplianceReports {
        constructor() {
            this.standardSelect = $('#compliance-standard');
            this.generateBtn = $('#generate-report');
            this.reportSection = $('#compliance-report');
            this.loader = $('#compliance-loader');
            this.scheduleModal = $('#schedule-modal');
            this.complianceChart = null;

            this.initEventListeners();
        }

        initEventListeners() {
            this.generateBtn.on('click', () => this.generateReport());
            $('#export-pdf').on('click', () => this.exportPDF());
            $('#export-csv').on('click', () => this.exportCSV());
            $('#schedule-scan').on('click', () => this.openScheduleModal());
            $('.close-modal').on('click', () => this.closeScheduleModal());
            $('#schedule-form').on('submit', (e) => this.handleScheduleSubmit(e));

            // Requirement accordion functionality
            $(document).on('click', '.requirement-header', function() {
                const content = $(this).next('.requirement-content');
                content.slideToggle();
                content.toggleClass('active');
            });
        }

        generateReport() {
            const standard = this.standardSelect.val();
            this.showLoader();

            $.ajax({
                url: kura_ai_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'kura_ai_generate_compliance_report',
                    nonce: kura_ai_ajax.nonce,
                    standard: standard
                },
                success: (response) => {
                    if (response.success) {
                        this.displayReport(response.data);
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: kura_ai_ajax.strings.error,
                            text: response.data.message || kura_ai_ajax.strings.general_error,
                            allowOutsideClick: true,
                            allowEscapeKey: true
                        });
                    }
                },
                error: () => {
                    Swal.fire({
                        icon: 'error',
                        title: kura_ai_ajax.strings.error,
                        text: kura_ai_ajax.strings.general_error,
                        allowOutsideClick: true,
                        allowEscapeKey: true
                    });
                },
                complete: () => this.hideLoader()
            });
        }

        displayReport(data) {
            // Update meta information
            $('#report-standard').text(data.standard);
            $('#report-version').text(data.version);
            $('#report-timestamp').text(data.timestamp);

            // Update summary counts
            $('#summary-total').text(data.summary.total);
            $('#summary-compliant').text(data.summary.compliant);
            $('#summary-partially').text(data.summary.partially_compliant);
            $('#summary-non-compliant').text(data.summary.non_compliant);

            // Update compliance chart
            this.updateComplianceChart(data.summary);

            // Generate requirements accordion
            this.generateRequirementsAccordion(data.requirements);

            // Show report section
            this.reportSection.removeClass('hidden');
        }

        updateComplianceChart(summary) {
            const ctx = document.getElementById('compliance-chart').getContext('2d');

            if (this.complianceChart) {
                this.complianceChart.destroy();
            }

            this.complianceChart = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: [
                        kura_ai_ajax.strings.compliant,
                        kura_ai_ajax.strings.partially_compliant,
                        kura_ai_ajax.strings.non_compliant
                    ],
                    datasets: [{
                        data: [
                            summary.compliant,
                            summary.partially_compliant,
                            summary.non_compliant
                        ],
                        backgroundColor: ['#00a32a', '#dba617', '#d63638'],
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });
        }

        generateRequirementsAccordion(requirements) {
            const accordion = $('#requirements-accordion');
            accordion.empty();

            Object.entries(requirements).forEach(([key, req]) => {
                const statusClass = this.getStatusClass(req.status);
                const statusLabel = this.getStatusLabel(req.status);

                const requirementHtml = `
                    <div class="requirement-item">
                        <div class="requirement-header">
                            <span class="requirement-title">${req.description}</span>
                            <span class="requirement-status ${statusClass}">${statusLabel}</span>
                        </div>
                        <div class="requirement-content">
                            ${this.generateDetailsSection(req.details)}
                            ${this.generateRecommendationsSection(req.recommendations)}
                        </div>
                    </div>
                `;

                accordion.append(requirementHtml);
            });
        }

        generateDetailsSection(details) {
            if (!details || !details.length) return '';

            return `
                <div class="content-section">
                    <h4 class="section-title">${kura_ai_ajax.strings.details}</h4>
                    <ul class="detail-list">
                        ${details.map(detail => `<li>${detail}</li>`).join('')}
                    </ul>
                </div>
            `;
        }

        generateRecommendationsSection(recommendations) {
            if (!recommendations || !recommendations.length) return '';

            return `
                <div class="content-section">
                    <h4 class="section-title">${kura_ai_ajax.strings.recommendations}</h4>
                    <ul class="recommendation-list">
                        ${recommendations.map(rec => `<li>${rec}</li>`).join('')}
                    </ul>
                </div>
            `;
        }

        getStatusClass(status) {
            const classes = {
                compliant: 'status-compliant',
                partially_compliant: 'status-partially',
                non_compliant: 'status-non-compliant'
            };
            return classes[status] || '';
        }

        getStatusLabel(status) {
            const labels = {
                compliant: kura_ai_ajax.strings.compliant,
                partially_compliant: kura_ai_ajax.strings.partially_compliant,
                non_compliant: kura_ai_ajax.strings.non_compliant
            };
            return labels[status] || status;
        }

        exportPDF() {
            const standard = this.standardSelect.val();
            window.location.href = `${kura_ai_ajax.ajax_url}?action=kura_ai_export_compliance_pdf&standard=${standard}&nonce=${kura_ai_ajax.nonce}`;
        }

        exportCSV() {
            const standard = this.standardSelect.val();
            window.location.href = `${kura_ai_ajax.ajax_url}?action=kura_ai_export_compliance_csv&standard=${standard}&nonce=${kura_ai_ajax.nonce}`;
        }

        openScheduleModal() {
            this.scheduleModal.removeClass('hidden');
        }

        closeScheduleModal() {
            this.scheduleModal.addClass('hidden');
            $('#schedule-form')[0].reset();
        }

        handleScheduleSubmit(e) {
            e.preventDefault();
            const formData = new FormData(e.target);
            const data = {
                action: 'kura_ai_schedule_compliance_scan',
                nonce: kura_ai_ajax.nonce,
                standard: this.standardSelect.val(),
                frequency: formData.get('frequency'),
                time: formData.get('time'),
                email: formData.get('email')
            };

            $.ajax({
                url: kura_ai_ajax.ajax_url,
                type: 'POST',
                data: data,
                success: (response) => {
                    if (response.success) {
                        Swal.fire({
                            icon: 'success',
                            title: kura_ai_ajax.strings.success,
                            text: response.data.message,
                            allowOutsideClick: true,
                            allowEscapeKey: true
                        });
                        this.closeScheduleModal();
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: kura_ai_ajax.strings.error,
                            text: response.data.message || kura_ai_ajax.strings.general_error,
                            allowOutsideClick: true,
                            allowEscapeKey: true
                        });
                    }
                },
                error: () => {
                    Swal.fire({
                        icon: 'error',
                        title: kura_ai_ajax.strings.error,
                        text: kura_ai_ajax.strings.general_error,
                        allowOutsideClick: true,
                        allowEscapeKey: true
                    });
                }
            });
        }

        showLoader() {
            this.loader.removeClass('hidden');
        }

        hideLoader() {
            this.loader.addClass('hidden');
        }
    }

    // Initialize when document is ready
    $(document).ready(() => {
        new ComplianceReports();
    });

})(jQuery);