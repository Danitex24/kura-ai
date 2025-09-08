/**
 * Analytics Dashboard JavaScript
 *
 * @package    Kura_AI
 * @subpackage Kura_AI/admin/js
 * @author     Daniel Abughdyer <daniel@danovatesolutions.org>
 */

(function($) {
    'use strict';

    class KuraAIAnalyticsDashboard {
        constructor() {
            this.charts = {};
            this.init();
        }

        init() {
            $(document).ready(() => {
                // Check if Chart.js is loaded
                if (typeof Chart === 'undefined') {
                    console.error('Chart.js is not loaded');
                    return;
                }
                
                // Check if canvas elements exist and set up resize handling
                const canvasElements = ['trendsChart', 'healthScoreChart', 'passFailChart', 'performanceChart'];
                const missingElements = canvasElements.filter(id => !document.getElementById(id));
                
                if (missingElements.length > 0) {
                    console.error('Missing canvas elements:', missingElements);
                    return;
                }
                
                // Set up canvas dimensions and resize observer
                this.setupCanvasResize();
                
                this.loadSummaryData();
                this.initializeCharts();
                this.loadRecentAnalyses();
                this.bindEvents();
            });
        }

        setupCanvasResize() {
            const canvasElements = ['trendsChart', 'healthScoreChart', 'passFailChart', 'performanceChart'];
            
            canvasElements.forEach(canvasId => {
                const canvas = document.getElementById(canvasId);
                if (canvas) {
                    // Set explicit canvas dimensions to prevent size issues
                    const container = canvas.parentElement;
                    const maxWidth = 600; // Reduced from 800
                    const maxHeight = 300; // Reduced from 400
                    const containerWidth = Math.min(container.offsetWidth || 400, maxWidth);
                    const containerHeight = Math.min(container.offsetHeight || 280, maxHeight);
                    
                    // Force canvas dimensions
                    canvas.style.width = containerWidth + 'px';
                    canvas.style.height = containerHeight + 'px';
                    canvas.width = containerWidth;
                    canvas.height = containerHeight;
                    canvas.style.maxWidth = maxWidth + 'px';
                    canvas.style.maxHeight = maxHeight + 'px';
                    
                    // Set up resize observer if available
                    if (window.ResizeObserver) {
                        const resizeObserver = new ResizeObserver(entries => {
                             for (let entry of entries) {
                                 const { width, height } = entry.contentRect;
                                 const maxWidth = Math.min(width, 500); // Strict size limit
                                 const maxHeight = Math.min(height, 250); // Strict size limit
                                 
                                 canvas.style.width = maxWidth + 'px';
                                 canvas.style.height = maxHeight + 'px';
                                 canvas.width = maxWidth;
                                 canvas.height = maxHeight;
                                 canvas.style.maxWidth = '500px';
                                 canvas.style.maxHeight = '250px';
                                
                                // Update chart if it exists
                                 let chartKey;
                                 switch(canvasId) {
                                     case 'trendsChart':
                                         chartKey = 'trends';
                                         break;
                                     case 'healthScoreChart':
                                         chartKey = 'healthScore';
                                         break;
                                     case 'passFailChart':
                                         chartKey = 'passFail';
                                         break;
                                     case 'performanceChart':
                                         chartKey = 'performance';
                                         break;
                                 }
                                 
                                 if (chartKey && this.charts[chartKey]) {
                                     this.charts[chartKey].resize();
                                 }
                            }
                        });
                        resizeObserver.observe(container);
                    }
                }
            });
        }

        bindEvents() {
            $('#trend-period').on('change', () => {
                this.updateTrendsChart();
            });
            
            // Reset recent analyses button
            $(document).on('click', '#reset-recent-analyses', (e) => {
                e.preventDefault();
                this.resetRecentAnalyses();
            });
        }

        loadSummaryData() {
            $.ajax({
                url: kuraAIAnalytics.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'kura_ai_get_analytics_summary',
                    _wpnonce: kuraAIAnalytics.nonce
                },
                success: (response) => {
                    if (response.success) {
                        this.updateSummaryCards(response.data);
                    }
                },
                error: (xhr, status, error) => {
                    console.error('Failed to load summary data:', error);
                }
            });
        }

        updateSummaryCards(data) {
            $('#total-analyses').text(data.total_analyses || 0);
            $('#passed-analyses').text(data.passed_analyses || 0);
            $('#failed-analyses').text(data.failed_analyses || 0);
            $('#avg-health-score').text(Math.round(data.avg_health_score || 0));
        }

        initializeCharts() {
            this.initTrendsChart();
            this.initHealthScoreChart();
            this.initPassFailChart();
            this.initPerformanceChart();
        }

        initTrendsChart() {
            try {
                const canvas = document.getElementById('trendsChart');
                if (!canvas) {
                    console.error('trendsChart canvas not found');
                    return;
                }
                // Enforce strict canvas size limits to prevent browser errors
                const maxWidth = 500;
                const maxHeight = 250;
                
                canvas.width = Math.min(canvas.offsetWidth || maxWidth, maxWidth);
                canvas.height = Math.min(canvas.offsetHeight || maxHeight, maxHeight);
                canvas.style.width = canvas.width + 'px';
                canvas.style.height = canvas.height + 'px';
                canvas.style.maxWidth = maxWidth + 'px';
                canvas.style.maxHeight = maxHeight + 'px';
                
                const ctx = canvas.getContext('2d');
                this.charts.trends = new Chart(ctx, {
                    type: 'line',
                data: {
                    labels: [],
                    datasets: [{
                        label: 'Analyses',
                        data: [],
                        borderColor: '#0073aa',
                        backgroundColor: 'rgba(0, 115, 170, 0.1)',
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        devicePixelRatio: 1,
                        animation: false,
                        resizeDelay: 0,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    layout: {
                        padding: {
                            top: 10,
                            right: 10,
                            bottom: 10,
                            left: 10
                        }
                    }
                }
            });
                this.updateTrendsChart();
            } catch (error) {
                console.error('Error initializing trends chart:', error);
            }
        }

        initHealthScoreChart() {
            try {
                const canvas = document.getElementById('healthScoreChart');
                if (!canvas) {
                    console.error('healthScoreChart canvas not found');
                    return;
                }
                // Enforce strict canvas size limits to prevent browser errors
                const maxWidth = 500;
                const maxHeight = 250;
                
                canvas.width = Math.min(canvas.offsetWidth || maxWidth, maxWidth);
                canvas.height = Math.min(canvas.offsetHeight || maxHeight, maxHeight);
                canvas.style.width = canvas.width + 'px';
                canvas.style.height = canvas.height + 'px';
                canvas.style.maxWidth = maxWidth + 'px';
                canvas.style.maxHeight = maxHeight + 'px';
                
                const ctx = canvas.getContext('2d');
                this.charts.healthScore = new Chart(ctx, {
                    type: 'bar',
                data: {
                    labels: ['0-20', '21-40', '41-60', '61-80', '81-100'],
                    datasets: [{
                        label: 'Count',
                        data: [0, 0, 0, 0, 0],
                        backgroundColor: [
                            '#dc3545',
                            '#fd7e14',
                            '#ffc107',
                            '#20c997',
                            '#28a745'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                        maintainAspectRatio: false,
                        devicePixelRatio: 1,
                        animation: false,
                        resizeDelay: 0,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    layout: {
                        padding: {
                            top: 10,
                            right: 10,
                            bottom: 10,
                            left: 10
                        }
                    }
                }
            });
                this.updateHealthScoreChart();
            } catch (error) {
                console.error('Error initializing health score chart:', error);
            }
        }

        initPassFailChart() {
            try {
                const canvas = document.getElementById('passFailChart');
                if (!canvas) {
                    console.error('passFailChart canvas not found');
                    return;
                }
                // Enforce strict canvas size limits to prevent browser errors
                const maxWidth = 500;
                const maxHeight = 250;
                
                canvas.width = Math.min(canvas.offsetWidth || maxWidth, maxWidth);
                canvas.height = Math.min(canvas.offsetHeight || maxHeight, maxHeight);
                canvas.style.width = canvas.width + 'px';
                canvas.style.height = canvas.height + 'px';
                canvas.style.maxWidth = maxWidth + 'px';
                canvas.style.maxHeight = maxHeight + 'px';
                
                const ctx = canvas.getContext('2d');
                this.charts.passFail = new Chart(ctx, {
                    type: 'doughnut',
                data: {
                    labels: ['Pass', 'Fail'],
                    datasets: [{
                        data: [0, 0],
                        backgroundColor: ['#28a745', '#dc3545'],
                        borderWidth: 2,
                        borderColor: '#fff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    devicePixelRatio: 1,
                        animation: false,
                        resizeDelay: 0,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    },
                    layout: {
                        padding: {
                            top: 10,
                            right: 10,
                            bottom: 10,
                            left: 10
                        }
                    }
                }
            });
                this.updatePassFailChart();
            } catch (error) {
                console.error('Error initializing pass/fail chart:', error);
            }
        }

        initPerformanceChart() {
            try {
                const canvas = document.getElementById('performanceChart');
                if (!canvas) {
                    console.error('performanceChart canvas not found');
                    return;
                }
                // Enforce strict canvas size limits to prevent browser errors
                const maxWidth = 500;
                const maxHeight = 250;
                
                canvas.width = Math.min(canvas.offsetWidth || maxWidth, maxWidth);
                canvas.height = Math.min(canvas.offsetHeight || maxHeight, maxHeight);
                canvas.style.width = canvas.width + 'px';
                canvas.style.height = canvas.height + 'px';
                canvas.style.maxWidth = maxWidth + 'px';
                canvas.style.maxHeight = maxHeight + 'px';
                
                const ctx = canvas.getContext('2d');
                this.charts.performance = new Chart(ctx, {
                    type: 'line',
                data: {
                    labels: [],
                    datasets: [{
                        label: 'Avg Analysis Time (s)',
                        data: [],
                        borderColor: '#17a2b8',
                        backgroundColor: 'rgba(23, 162, 184, 0.1)',
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    devicePixelRatio: 1,
                     animation: false,
                     resizeDelay: 0,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    layout: {
                        padding: {
                            top: 10,
                            right: 10,
                            bottom: 10,
                            left: 10
                        }
                    }
                }
            });
                this.updatePerformanceChart();
            } catch (error) {
                console.error('Error initializing performance chart:', error);
            }
        }

        updateTrendsChart() {
            const period = $('#trend-period').val() || 30;
            
            $.ajax({
                url: kuraAIAnalytics.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'kura_ai_get_trends_data',
                    period: period,
                    _wpnonce: kuraAIAnalytics.nonce
                },
                success: (response) => {
                    if (response.success && this.charts.trends) {
                        this.charts.trends.data.labels = response.data.labels;
                        this.charts.trends.data.datasets[0].data = response.data.values;
                        this.charts.trends.update();
                    }
                }
            });
        }

        updateHealthScoreChart() {
            $.ajax({
                url: kuraAIAnalytics.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'kura_ai_get_health_score_distribution',
                    _wpnonce: kuraAIAnalytics.nonce
                },
                success: (response) => {
                    if (response.success && this.charts.healthScore) {
                        this.charts.healthScore.data.datasets[0].data = response.data;
                        this.charts.healthScore.update();
                    }
                }
            });
        }

        updatePassFailChart() {
            $.ajax({
                url: kuraAIAnalytics.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'kura_ai_get_pass_fail_data',
                    _wpnonce: kuraAIAnalytics.nonce
                },
                success: (response) => {
                    if (response.success && this.charts.passFail) {
                        this.charts.passFail.data.datasets[0].data = [response.data.pass, response.data.fail];
                        this.charts.passFail.update();
                    }
                }
            });
        }

        updatePerformanceChart() {
            $.ajax({
                url: kuraAIAnalytics.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'kura_ai_get_performance_data',
                    _wpnonce: kuraAIAnalytics.nonce
                },
                success: (response) => {
                    if (response.success && this.charts.performance) {
                        this.charts.performance.data.labels = response.data.labels;
                        this.charts.performance.data.datasets[0].data = response.data.values;
                        this.charts.performance.update();
                    }
                }
            });
        }

        loadRecentAnalyses() {
            $.ajax({
                url: kuraAIAnalytics.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'kura_ai_get_recent_analyses',
                    _wpnonce: kuraAIAnalytics.nonce
                },
                success: (response) => {
                    if (response.success) {
                        this.updateRecentAnalysesTable(response.data);
                    }
                },
                error: () => {
                    $('#recent-analyses-tbody').html('<tr><td colspan="6">Failed to load recent analyses.</td></tr>');
                }
            });
        }

        updateRecentAnalysesTable(analyses) {
            const tbody = $('#recent-analyses-tbody');
            tbody.empty();

            if (analyses.length === 0) {
                tbody.append('<tr><td colspan="6">No analyses found.</td></tr>');
                return;
            }

            analyses.forEach(analysis => {
                const passStatus = analysis.pass_status || 'unknown';
                const statusClass = passStatus === 'pass' ? 'success' : 'error';
                const statusIcon = passStatus === 'pass' ? '✓' : '✗';
                
                const row = `
                    <tr>
                        <td>${this.formatDate(analysis.created_at)}</td>
                        <td>${analysis.user_name || 'Unknown'}</td>
                        <td>${analysis.code_length} chars</td>
                        <td>${parseFloat(analysis.analysis_time).toFixed(2)}s</td>
                        <td>${Math.round(analysis.health_score)}/100</td>
                        <td><span class="status-${statusClass}">${statusIcon} ${passStatus.toUpperCase()}</span></td>
                    </tr>
                `;
                tbody.append(row);
            });
        }

        formatDate(dateString) {
            const date = new Date(dateString);
            return date.toLocaleDateString() + ' ' + date.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
        }

        resetRecentAnalyses() {
            Swal.fire({
                title: 'Reset Recent Analyses?',
                text: 'Are you sure you want to reset all recent analyses? This action cannot be undone.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, reset it!',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: kuraAIAnalytics.ajaxUrl,
                        type: 'POST',
                        data: {
                            action: 'kura_ai_reset_recent_analyses',
                            _wpnonce: kuraAIAnalytics.nonce
                        },
                        success: (response) => {
                            if (response.success) {
                                // Refresh the dashboard data
                                this.loadSummaryData();
                                this.loadRecentAnalyses();
                                this.updateTrendsChart();
                                this.updateHealthScoreChart();
                                this.updatePassFailChart();
                                this.updatePerformanceChart();
                                
                                // Show success message
                                Swal.fire({
                                    title: 'Reset Complete!',
                                    text: 'Recent analyses have been reset successfully.',
                                    icon: 'success',
                                    timer: 3000,
                                    showConfirmButton: false
                                });
                            } else {
                                Swal.fire({
                                    title: 'Error!',
                                    text: 'Failed to reset recent analyses: ' + (response.data || 'Unknown error'),
                                    icon: 'error'
                                });
                            }
                        },
                        error: (xhr, status, error) => {
                            console.error('Failed to reset recent analyses:', error);
                            Swal.fire({
                                title: 'Error!',
                                text: 'Failed to reset recent analyses. Please try again.',
                                icon: 'error'
                            });
                        }
                    });
                }
            });
        }
    }

    // Initialize the dashboard when the page loads
    const dashboard = new KuraAIAnalyticsDashboard();
    
    // Make refresh function globally available
    window.refreshAnalyticsDashboard = function() {
        console.log('Refreshing analytics dashboard...');
        dashboard.loadSummaryData();
        dashboard.updateTrendsChart();
        dashboard.updateHealthScoreChart();
        dashboard.updatePassFailChart();
        dashboard.updatePerformanceChart();
        dashboard.loadRecentAnalyses();
    };

})(jQuery);

// Add status styling only (chart styling is handled by CSS file)
const style = document.createElement('style');
style.textContent = `
    .status-success {
        color: #28a745;
        font-weight: bold;
    }
    .status-error {
        color: #dc3545;
        font-weight: bold;
    }
`;
document.head.appendChild(style);