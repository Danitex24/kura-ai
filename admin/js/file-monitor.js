(function($) {
    'use strict';

    let statusChart, riskChart, timelineChart, sizeChart;

    class KuraAIFileMonitor {
        constructor() {
            this.addFileForm = $('#add-file-form');
            this.filesGrid = $('.files-grid');
            this.compareModal = $('#version-compare-modal');
            this.bindEvents();
            this.initCharts();
            this.loadChartData();
            
            // Auto-refresh charts every 5 minutes (only if page is visible)
        setInterval(() => {
            if (!document.hidden) {
                this.loadChartData();
            }
        }, 300000);
        }

        bindEvents() {
            this.addFileForm.on('submit', (e) => this.handleAddFile(e));
            this.filesGrid.on('click', '.create-version-btn', (e) => this.handleCreateVersion(e));
            this.filesGrid.on('click', '.view-versions-btn', (e) => this.handleToggleVersions(e));
            this.filesGrid.on('click', '.remove-file-btn', (e) => this.handleRemoveFile(e));
            this.filesGrid.on('click', '.compare-version-btn', (e) => this.handleCompareVersion(e));
            this.filesGrid.on('click', '.rollback-version-btn', (e) => this.handleRollback(e));
            this.compareModal.find('.close-modal').on('click', () => this.closeCompareModal());
            $('#version-from, #version-to').on('change', () => this.updateDiff());
            
            // Add file button
            $('#add-file-btn').on('click', () => this.openModal('addFileModal'));
            
            // Monitor file changes
            $('.monitor-file-btn').on('click', (e) => {
                const filePath = $(e.currentTarget).data('file-path');
                this.monitorFile(filePath);
            });

            // Stop monitoring
            $('.stop-monitor-btn').on('click', (e) => {
                const filePath = $(e.currentTarget).data('file-path');
                this.stopMonitoring(filePath);
            });
            
            // Close modal when clicking outside
            $(document).on('click', '.kura-ai-modal', function(e) {
                if (e.target === this) {
                    $(this).hide();
                }
            });
        }

        handleAddFile(e) {
            e.preventDefault();

            const filePath = $('#file-path').val().trim();
            if (!filePath) {
                this.showNotice('error', kura_ai_ajax.empty_path_error);
                return;
            }

            $.ajax({
                url: kura_ai_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'kura_ai_add_monitored_file',
                    nonce: kura_ai_ajax.nonce,
                    file_path: filePath
                },
                success: (response) => {
                    if (response.success) {
                        location.reload(); // Refresh to show new file
                    } else {
                        this.showNotice('error', response.data.message || kura_ai_ajax.add_file_error);
                    }
                },
                error: () => {
                    this.showNotice('error', kura_ai_ajax.add_file_error);
                }
            });
        }

        handleCreateVersion(e) {
            const button = $(e.currentTarget);
            const filePath = button.data('path');

            this.showLoading(button);

            $.ajax({
                url: kura_ai_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'kura_ai_create_version',
                    nonce: kura_ai_ajax.nonce,
                    file_path: filePath
                },
                success: (response) => {
                    if (response.success) {
                        location.reload(); // Refresh to show new version
                    } else {
                        this.showNotice('error', response.data.message || kura_ai_ajax.create_version_error);
                    }
                },
                error: () => {
                    this.showNotice('error', kura_ai_ajax.create_version_error);
                },
                complete: () => {
                    this.hideLoading(button);
                }
            });
        }

        handleToggleVersions(e) {
            const button = $(e.currentTarget);
            const fileItem = button.closest('.file-item');
            const versionsPanel = fileItem.find('.versions-panel');

            versionsPanel.slideToggle();
            button.toggleClass('active');
        }

        handleRemoveFile(e) {
            const button = $(e.currentTarget);
            const filePath = button.data('path');

            Swal.fire({
                title: kura_ai_ajax.confirm_remove_title,
                text: kura_ai_ajax.confirm_remove_text,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: kura_ai_ajax.yes_remove,
                cancelButtonText: kura_ai_ajax.no_cancel,
                allowOutsideClick: true,
                allowEscapeKey: true
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: kura_ai_ajax.ajax_url,
                        type: 'POST',
                        data: {
                            action: 'kura_ai_remove_monitored_file',
                            nonce: kura_ai_ajax.nonce,
                            file_path: filePath
                        },
                        success: (response) => {
                            if (response.success) {
                                location.reload(); // Refresh to remove file
                            } else {
                                this.showNotice('error', response.data.message || kura_ai_ajax.remove_file_error);
                            }
                        },
                        error: () => {
                            this.showNotice('error', kura_ai_ajax.remove_file_error);
                        }
                    });
                }
            });
        }

        handleCompareVersion(e) {
            const button = $(e.currentTarget);
            const fileItem = button.closest('.file-item');
            const versions = fileItem.find('.version-item').map(function() {
                return {
                    id: $(this).data('version-id'),
                    date: $(this).find('.version-date').text()
                };
            }).get();

            this.populateVersionSelects(versions);
            this.compareModal.show();
            this.updateDiff();
        }

        handleRollback(e) {
            const button = $(e.currentTarget);
            const versionId = button.data('version-id');
            const filePath = button.closest('.file-item').find('.remove-file-btn').data('path');

            Swal.fire({
                title: kura_ai_ajax.confirm_rollback_title,
                text: kura_ai_ajax.confirm_rollback_text,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: kura_ai_ajax.yes_rollback,
                cancelButtonText: kura_ai_ajax.no_cancel,
                allowOutsideClick: true,
                allowEscapeKey: true
            }).then((result) => {
                if (result.isConfirmed) {
                    this.showLoading(button);

                    $.ajax({
                        url: kura_ai_ajax.ajax_url,
                        type: 'POST',
                        data: {
                            action: 'kura_ai_rollback_version',
                            nonce: kura_ai_ajax.nonce,
                            file_path: filePath,
                            version_id: versionId
                        },
                        success: (response) => {
                            if (response.success) {
                                location.reload(); // Refresh to show rollback
                            } else {
                                this.showNotice('error', response.data.message || kura_ai_ajax.rollback_error);
                            }
                        },
                        error: () => {
                            this.showNotice('error', kura_ai_ajax.rollback_error);
                        },
                        complete: () => {
                            this.hideLoading(button);
                        }
                    });
                }
            });
        }

        populateVersionSelects(versions) {
            const fromSelect = $('#version-from');
            const toSelect = $('#version-to');

            fromSelect.empty();
            toSelect.empty();

            versions.forEach((version, index) => {
                const option = $('<option></option>')
                    .val(version.id)
                    .text(version.date);

                fromSelect.append(option.clone());
                toSelect.append(option.clone());
            });

            // Default to comparing latest with previous version
            if (versions.length >= 2) {
                fromSelect.val(versions[1].id); // Second newest
                toSelect.val(versions[0].id); // Newest
            }
        }

        updateDiff() {
            const fromVersion = $('#version-from').val();
            const toVersion = $('#version-to').val();

            if (!fromVersion || !toVersion) return;

            const diffViewer = this.compareModal.find('.diff-viewer');
            diffViewer.find('.diff-loading').show();
            diffViewer.find('.diff-content').empty();

            $.ajax({
                url: kura_ai_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'kura_ai_compare_versions',
                    nonce: kura_ai_ajax.nonce,
                    version_id_1: fromVersion,
                    version_id_2: toVersion
                },
                success: (response) => {
                    if (response.success) {
                        diffViewer.find('.diff-content').html(response.data.diff_html);
                    } else {
                        this.showNotice('error', response.data.message || kura_ai_ajax.compare_error);
                    }
                },
                error: () => {
                    this.showNotice('error', kura_ai_ajax.compare_error);
                },
                complete: () => {
                    diffViewer.find('.diff-loading').hide();
                }
            });
        }

        closeCompareModal() {
            this.compareModal.hide();
        }

        showLoading(button) {
            const originalText = button.html();
            button.data('original-text', originalText)
                .prop('disabled', true)
                .html('<i class="fas fa-spinner fa-spin"></i>');
        }

        hideLoading(button) {
            const originalText = button.data('original-text');
            button.prop('disabled', false)
                .html(originalText);
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
        
        initCharts() {
            // Initialize Chart.js charts
            const chartOptions = {
                responsive: true,
                maintainAspectRatio: false,
                devicePixelRatio: 1,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                },
                layout: {
                    padding: 10
                },
                elements: {
                    point: {
                        radius: 3
                    }
                }
            };

            // Status Distribution Chart (Pie)
            const statusCtx = document.getElementById('statusChart');
            if (statusCtx && this.validateCanvasSize(statusCtx)) {
                try {
                    statusChart = new Chart(statusCtx, {
                    type: 'pie',
                    data: {
                        labels: ['Normal', 'Changed', 'Suspicious'],
                        datasets: [{
                            data: [0, 0, 0],
                            backgroundColor: [
                                '#28a745',
                                '#ffc107', 
                                '#dc3545'
                            ],
                            borderWidth: 2,
                            borderColor: '#fff'
                        }]
                    },
                    options: chartOptions
                    });
                } catch (error) {
                    console.error('Error initializing status chart:', error);
                    this.hideChartContainer('statusChart');
                }
            }

            // Risk Level Chart (Doughnut)
            const riskCtx = document.getElementById('riskChart');
            if (riskCtx && this.validateCanvasSize(riskCtx)) {
                try {
                    riskChart = new Chart(riskCtx, {
                    type: 'doughnut',
                    data: {
                        labels: ['Low', 'Medium', 'High'],
                        datasets: [{
                            data: [0, 0, 0],
                            backgroundColor: [
                                '#28a745',
                                '#ffc107',
                                '#dc3545'
                            ],
                            borderWidth: 2,
                            borderColor: '#fff'
                        }]
                    },
                    options: chartOptions
                    });
                } catch (error) {
                    console.error('Error initializing risk chart:', error);
                    this.hideChartContainer('riskChart');
                }
            }

            // Timeline Chart (Line)
            const timelineCtx = document.getElementById('timelineChart');
            if (timelineCtx && this.validateCanvasSize(timelineCtx)) {
                try {
                    timelineChart = new Chart(timelineCtx, {
                    type: 'line',
                    data: {
                        labels: [],
                        datasets: [{
                            label: 'File Changes',
                            data: [],
                            borderColor: '#007cba',
                            backgroundColor: 'rgba(0, 124, 186, 0.1)',
                            borderWidth: 2,
                            fill: true,
                            tension: 0.4
                        }]
                    },
                    options: {
                        ...chartOptions,
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    stepSize: 1
                                }
                            }
                        }
                    }
                    });
                } catch (error) {
                    console.error('Error initializing timeline chart:', error);
                    this.hideChartContainer('timelineChart');
                }
            }

            // File Size Chart (Bar)
            const sizeCtx = document.getElementById('sizeChart');
            if (sizeCtx && this.validateCanvasSize(sizeCtx)) {
                try {
                    sizeChart = new Chart(sizeCtx, {
                    type: 'bar',
                    data: {
                        labels: [],
                        datasets: [{
                            label: 'File Size (bytes)',
                            data: [],
                            backgroundColor: '#17a2b8',
                            borderColor: '#138496',
                            borderWidth: 1
                        }]
                    },
                    options: {
                        ...chartOptions,
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    callback: function(value) {
                                        return this.formatBytes(value);
                                    }.bind(this)
                                }
                            }
                        }
                    }
                    });
                } catch (error) {
                    console.error('Error initializing size chart:', error);
                    this.hideChartContainer('sizeChart');
                }
            }
        }

        validateCanvasSize(canvas) {
            const container = canvas.parentElement;
            if (!container) return false;
            
            const containerRect = container.getBoundingClientRect();
            const maxSize = 32767; // Canvas max size limit
            
            // Check if container dimensions are reasonable
            if (containerRect.width <= 0 || containerRect.height <= 0 || 
                containerRect.width > maxSize || containerRect.height > maxSize) {
                console.warn('Invalid canvas container size:', containerRect);
                return false;
            }
            
            return true;
        }

        hideChartContainer(chartId) {
            const canvas = document.getElementById(chartId);
            if (canvas && canvas.parentElement) {
                canvas.parentElement.style.display = 'none';
            }
        }

        loadChartData() {
            // Prevent multiple simultaneous requests
            if (this.loadingChartData) {
                return;
            }
            
            this.loadingChartData = true;
            
            $.ajax({
                url: kura_ai_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'kura_ai_get_chart_data',
                    nonce: kura_ai_ajax.nonce
                },
                timeout: 10000, // 10 second timeout
                success: (response) => {
                    if (response && response.success && response.data) {
                        this.updateCharts(response.data);
                    } else {
                        console.log('Invalid chart data response:', response);
                    }
                },
                error: (xhr, status, error) => {
                    console.log('Failed to load chart data:', status, error);
                },
                complete: () => {
                    this.loadingChartData = false;
                }
            });
        }

        updateCharts(data) {
            try {
                // Update Status Chart
                if (statusChart && data.file_status_distribution && 
                    Array.isArray(data.file_status_distribution.labels) && 
                    Array.isArray(data.file_status_distribution.data)) {
                    try {
                        statusChart.data.labels = data.file_status_distribution.labels;
                        statusChart.data.datasets[0].data = data.file_status_distribution.data;
                        statusChart.update('none'); // Disable animation to prevent issues
                    } catch (error) {
                        console.error('Error updating status chart:', error);
                        this.hideChartContainer('statusChart');
                    }
                }

                // Update Risk Chart
                if (riskChart && data.risk_level_distribution && 
                    Array.isArray(data.risk_level_distribution.labels) && 
                    Array.isArray(data.risk_level_distribution.data)) {
                    try {
                        riskChart.data.labels = data.risk_level_distribution.labels;
                        riskChart.data.datasets[0].data = data.risk_level_distribution.data;
                        riskChart.update('none');
                    } catch (error) {
                        console.error('Error updating risk chart:', error);
                        this.hideChartContainer('riskChart');
                    }
                }

                // Update Timeline Chart
                if (timelineChart && data.file_changes_timeline && 
                    Array.isArray(data.file_changes_timeline.labels) && 
                    Array.isArray(data.file_changes_timeline.data)) {
                    try {
                        timelineChart.data.labels = data.file_changes_timeline.labels;
                        timelineChart.data.datasets[0].data = data.file_changes_timeline.data;
                        timelineChart.update('none');
                    } catch (error) {
                        console.error('Error updating timeline chart:', error);
                        this.hideChartContainer('timelineChart');
                    }
                }

                // Update Size Chart
                if (sizeChart && data.file_size_trends && 
                    Array.isArray(data.file_size_trends.labels) && 
                    Array.isArray(data.file_size_trends.data)) {
                    try {
                        sizeChart.data.labels = data.file_size_trends.labels;
                        sizeChart.data.datasets[0].data = data.file_size_trends.data;
                        sizeChart.update('none');
                    } catch (error) {
                        console.error('Error updating size chart:', error);
                        this.hideChartContainer('sizeChart');
                    }
                }
            } catch (error) {
                console.error('Error updating charts:', error);
            }
        }

        formatBytes(bytes, decimals = 2) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const dm = decimals < 0 ? 0 : decimals;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
        }
        
        monitorFile(filePath) {
            $.ajax({
                url: kura_ai_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'kura_ai_monitor_file',
                    file_path: filePath,
                    nonce: kura_ai_ajax.nonce
                },
                success: (response) => {
                    if (response.success) {
                        location.reload();
                    } else {
                        this.showNotice('error', response.data.message || 'Error monitoring file');
                    }
                }
            });
        }

        stopMonitoring(filePath) {
            $.ajax({
                url: kura_ai_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'kura_ai_stop_monitoring',
                    file_path: filePath,
                    nonce: kura_ai_ajax.nonce
                },
                success: (response) => {
                    if (response.success) {
                        location.reload();
                    } else {
                        this.showNotice('error', response.data.message || 'Error stopping monitoring');
                    }
                }
            });
        }
        
        openModal(modalId) {
            $('#' + modalId).show();
        }

        closeModal(modalId) {
            $('#' + modalId).hide();
        }
    }

    // Initialize when document is ready
    $(document).ready(() => {
        new KuraAIFileMonitor();
    });

})(jQuery);