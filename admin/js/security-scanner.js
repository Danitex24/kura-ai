/**
 * Security Scanner Dashboard JavaScript
 * 
 * @package Kura_AI
 * @since 1.0.0
 */

(function($) {
    'use strict';

    /**
     * Security Scanner Dashboard Class
     */
    class SecurityScannerDashboard {
        constructor() {
            this.init();
        }

        init() {
            this.bindEvents();
            this.initCharts();
            this.initFilters();
        }

        bindEvents() {
            // Run security scan buttons
            $('#run-security-scan, #run-first-scan').on('click', (e) => {
                e.preventDefault();
                this.runSecurityScan();
            });

            // Reset scan results button
            $('#reset-scan-results').on('click', (e) => {
                e.preventDefault();
                this.resetScanResults();
            });

            // Result item actions
            $('.result-actions .button').on('click', (e) => {
                e.preventDefault();
                const action = $(e.target).text().toLowerCase();
                const resultItem = $(e.target).closest('.result-item');
                this.handleResultAction(action, resultItem);
            });
        }

        initCharts() {
            this.initThreatDistributionChart();
            this.initSecurityTrendChart();
        }

        initThreatDistributionChart() {
            const canvas = document.getElementById('threatChart');
            if (!canvas) return;

            const ctx = canvas.getContext('2d');
            const pieChart = canvas.closest('.pie-chart');
            
            if (!pieChart) return;

            const critical = parseFloat(pieChart.dataset.critical) || 0;
            const high = parseFloat(pieChart.dataset.high) || 0;
            const medium = parseFloat(pieChart.dataset.medium) || 0;
            const low = parseFloat(pieChart.dataset.low) || 0;

            // Only draw if there's data
            if (critical + high + medium + low === 0) {
                return;
            }

            const data = [
                { label: 'Critical', value: critical, color: '#dc3545' },
                { label: 'High', value: high, color: '#fd7e14' },
                { label: 'Medium', value: medium, color: '#ffc107' },
                { label: 'Low', value: low, color: '#28a745' }
            ].filter(item => item.value > 0);

            this.drawPieChart(ctx, data, 100, 100, 80);
        }

        drawPieChart(ctx, data, centerX, centerY, radius) {
            const total = data.reduce((sum, item) => sum + item.value, 0);
            let currentAngle = -Math.PI / 2; // Start from top

            data.forEach(item => {
                const sliceAngle = (item.value / total) * 2 * Math.PI;
                
                // Draw slice
                ctx.beginPath();
                ctx.moveTo(centerX, centerY);
                ctx.arc(centerX, centerY, radius, currentAngle, currentAngle + sliceAngle);
                ctx.closePath();
                ctx.fillStyle = item.color;
                ctx.fill();
                
                // Add stroke
                ctx.strokeStyle = '#fff';
                ctx.lineWidth = 2;
                ctx.stroke();
                
                currentAngle += sliceAngle;
            });
        }

        initSecurityTrendChart() {
            const canvas = document.getElementById('trendChart');
            if (!canvas) return;

            const ctx = canvas.getContext('2d');
            
            // Sample trend data (in a real implementation, this would come from the server)
            const trendData = [
                { date: '7 days ago', score: 75 },
                { date: '6 days ago', score: 78 },
                { date: '5 days ago', score: 82 },
                { date: '4 days ago', score: 79 },
                { date: '3 days ago', score: 85 },
                { date: '2 days ago', score: 88 },
                { date: 'Yesterday', score: 92 },
                { date: 'Today', score: 95 }
            ];

            this.drawLineChart(ctx, trendData, canvas.width, canvas.height);
        }

        drawLineChart(ctx, data, width, height) {
            const padding = 40;
            const chartWidth = width - (padding * 2);
            const chartHeight = height - (padding * 2);
            
            // Clear canvas
            ctx.clearRect(0, 0, width, height);
            
            // Draw grid
            ctx.strokeStyle = '#e9ecef';
            ctx.lineWidth = 1;
            
            // Horizontal grid lines
            for (let i = 0; i <= 5; i++) {
                const y = padding + (chartHeight / 5) * i;
                ctx.beginPath();
                ctx.moveTo(padding, y);
                ctx.lineTo(width - padding, y);
                ctx.stroke();
            }
            
            // Vertical grid lines
            for (let i = 0; i <= 7; i++) {
                const x = padding + (chartWidth / 7) * i;
                ctx.beginPath();
                ctx.moveTo(x, padding);
                ctx.lineTo(x, height - padding);
                ctx.stroke();
            }
            
            // Draw line
            ctx.strokeStyle = '#667eea';
            ctx.lineWidth = 3;
            ctx.beginPath();
            
            data.forEach((point, index) => {
                const x = padding + (chartWidth / (data.length - 1)) * index;
                const y = height - padding - ((point.score / 100) * chartHeight);
                
                if (index === 0) {
                    ctx.moveTo(x, y);
                } else {
                    ctx.lineTo(x, y);
                }
            });
            
            ctx.stroke();
            
            // Draw points
            ctx.fillStyle = '#667eea';
            data.forEach((point, index) => {
                const x = padding + (chartWidth / (data.length - 1)) * index;
                const y = height - padding - ((point.score / 100) * chartHeight);
                
                ctx.beginPath();
                ctx.arc(x, y, 4, 0, 2 * Math.PI);
                ctx.fill();
                
                // Add white border
                ctx.strokeStyle = '#fff';
                ctx.lineWidth = 2;
                ctx.stroke();
            });
        }

        initFilters() {
            $('.filter-btn').on('click', (e) => {
                e.preventDefault();
                const filter = $(e.target).data('filter');
                
                // Update active state
                $('.filter-btn').removeClass('active');
                $(e.target).addClass('active');
                
                // Filter results
                this.filterResults(filter);
            });
        }

        filterResults(filter) {
            $('.result-item').each(function() {
                const severity = $(this).data('severity');
                
                if (filter === 'all' || severity === filter) {
                    $(this).show();
                } else {
                    $(this).hide();
                }
            });
        }

        runSecurityScan() {
            // Show loading state
            const button = $('#run-security-scan, #run-first-scan');
            const originalText = button.html();
            
            button.prop('disabled', true)
                  .html('<i class="fas fa-spinner fa-spin"></i> Running Scan...');

            // Show scan progress modal
            this.showScanProgressModal();

            // Simulate scan process
            this.simulateScanProcess().then(() => {
                // Reset button
                button.prop('disabled', false).html(originalText);
                
                // Show completion message
                this.showScanComplete();
                
                // Reload page to show new results
                setTimeout(() => {
                    window.location.reload();
                }, 2000);
            });
        }

        showScanProgressModal() {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Security Scan in Progress',
                    html: `
                        <div class="scan-progress-container">
                            <div class="scan-step active" id="step-files">
                                <i class="fas fa-file-alt"></i>
                                <span>Scanning Files</span>
                                <div class="step-status"><i class="fas fa-spinner fa-spin"></i></div>
                            </div>
                            <div class="scan-step" id="step-malware">
                                <i class="fas fa-bug"></i>
                                <span>Malware Detection</span>
                                <div class="step-status"></div>
                            </div>
                            <div class="scan-step" id="step-vulnerabilities">
                                <i class="fas fa-shield-alt"></i>
                                <span>Vulnerability Check</span>
                                <div class="step-status"></div>
                            </div>
                            <div class="scan-step" id="step-permissions">
                                <i class="fas fa-lock"></i>
                                <span>Permission Analysis</span>
                                <div class="step-status"></div>
                            </div>
                            <div class="modal-progress-bar">
                                <div class="modal-progress-fill" style="width: 0%"></div>
                                <div class="modal-progress-text">0%</div>
                            </div>
                        </div>
                    `,
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    showConfirmButton: false,
                    customClass: {
                        popup: 'scan-progress-modal'
                    }
                });
            }
        }

        simulateScanProcess() {
            return new Promise((resolve) => {
                const steps = [
                    { id: 'step-files', duration: 2000 },
                    { id: 'step-malware', duration: 3000 },
                    { id: 'step-vulnerabilities', duration: 2500 },
                    { id: 'step-permissions', duration: 1500 }
                ];

                let currentStep = 0;
                let totalProgress = 0;

                const processStep = () => {
                    if (currentStep < steps.length) {
                        const step = steps[currentStep];
                        
                        // Mark current step as active
                        $(`#${step.id}`).addClass('active');
                        $(`#${step.id} .step-status`).html('<i class="fas fa-spinner fa-spin"></i>');
                        
                        setTimeout(() => {
                            // Mark step as complete
                            $(`#${step.id} .step-status`).html('<i class="fas fa-check" style="color: #28a745;"></i>');
                            $(`#${step.id}`).removeClass('active').addClass('completed');
                            
                            currentStep++;
                            totalProgress = (currentStep / steps.length) * 100;
                            
                            // Update progress bar
                            this.updateModalProgressBar(totalProgress);
                            
                            if (currentStep < steps.length) {
                                processStep();
                            } else {
                                resolve();
                            }
                        }, step.duration);
                    }
                };

                processStep();
            });
        }

        updateModalProgressBar(percentage) {
            $('.modal-progress-fill').css('width', percentage + '%');
            $('.modal-progress-text').text(Math.round(percentage) + '%');
        }

        showScanComplete() {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Scan Complete!',
                    text: 'Security scan has been completed successfully.',
                    icon: 'success',
                    confirmButtonText: 'View Results',
                    customClass: {
                        confirmButton: 'button button-primary'
                    }
                });
            }
        }

        handleResultAction(action, resultItem) {
            const issueTitle = resultItem.find('h4').text();
            
            if (action === 'fix') {
                this.showFixModal(issueTitle, resultItem);
            } else if (action === 'ignore') {
                this.showIgnoreModal(issueTitle, resultItem);
            }
        }

        showFixModal(issueTitle, resultItem) {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Fix Security Issue',
                    text: `Do you want to automatically fix: ${issueTitle}?`,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Fix Now',
                    cancelButtonText: 'Cancel',
                    customClass: {
                        confirmButton: 'button button-primary',
                        cancelButton: 'button'
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        this.applyFix(resultItem);
                    }
                });
            }
        }

        showIgnoreModal(issueTitle, resultItem) {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Ignore Security Issue',
                    text: `Are you sure you want to ignore: ${issueTitle}?`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Ignore',
                    cancelButtonText: 'Cancel',
                    customClass: {
                        confirmButton: 'button button-secondary',
                        cancelButton: 'button'
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        this.ignoreIssue(resultItem);
                    }
                });
            }
        }

        applyFix(resultItem) {
            // Simulate fix process
            resultItem.addClass('fixing');
            
            setTimeout(() => {
                resultItem.removeClass('fixing').addClass('fixed');
                resultItem.find('.severity-badge').removeClass().addClass('severity-badge severity-fixed').text('FIXED');
                
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        title: 'Fixed!',
                        text: 'The security issue has been resolved.',
                        icon: 'success',
                        timer: 2000,
                        showConfirmButton: false
                    });
                }
            }, 2000);
        }

        ignoreIssue(resultItem) {
            resultItem.fadeOut(300, function() {
                $(this).remove();
            });
            
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Ignored',
                    text: 'The security issue has been ignored.',
                    icon: 'info',
                    timer: 2000,
                    showConfirmButton: false
                });
            }
        }

        resetScanResults() {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Reset Scan Results',
                    text: 'Are you sure you want to reset all scan results? This will clear all security findings and statistics.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Reset Results',
                    cancelButtonText: 'Cancel',
                    confirmButtonColor: '#d33',
                    customClass: {
                        confirmButton: 'button button-primary',
                        cancelButton: 'button'
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        this.performReset();
                    }
                });
            } else {
                if (confirm('Are you sure you want to reset all scan results?')) {
                    this.performReset();
                }
            }
        }

        performReset() {
            // Show loading state
            const resetButton = $('#reset-scan-results');
            const originalText = resetButton.html();
            resetButton.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Resetting...');

            // Make AJAX request to reset scan results
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'kura_ai_reset_scan_results',
                    nonce: kura_ai_ajax.nonce
                },
                success: (response) => {
                    if (response.success) {
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                title: 'Reset Complete',
                                text: 'All scan results have been cleared successfully.',
                                icon: 'success',
                                timer: 2000,
                                showConfirmButton: false
                            }).then(() => {
                                location.reload();
                            });
                        } else {
                            alert('Scan results have been reset successfully.');
                            location.reload();
                        }
                    } else {
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                title: 'Error',
                                text: response.data || 'Failed to reset scan results.',
                                icon: 'error'
                            });
                        } else {
                            alert('Error: ' + (response.data || 'Failed to reset scan results.'));
                        }
                    }
                },
                error: () => {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            title: 'Error',
                            text: 'Network error occurred while resetting scan results.',
                            icon: 'error'
                        });
                    } else {
                        alert('Network error occurred while resetting scan results.');
                    }
                },
                complete: () => {
                    resetButton.prop('disabled', false).html(originalText);
                }
            });
        }
    }

    // Initialize when document is ready
    $(document).ready(function() {
        new SecurityScannerDashboard();
    });

})(jQuery);