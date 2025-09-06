jQuery(function($) {
    'use strict';

    // Initialize Chart.js
    let metricsChart = null;

    function initializeChart() {
        const ctx = document.getElementById('security-metrics-chart').getContext('2d');
        metricsChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: [],
                datasets: [
                    {
                        label: 'Failed Logins',
                        borderColor: '#d63638',
                        backgroundColor: 'rgba(214, 54, 56, 0.1)',
                        data: []
                    },
                    {
                        label: 'File Changes',
                        borderColor: '#2271b1',
                        backgroundColor: 'rgba(34, 113, 177, 0.1)',
                        data: []
                    },
                    {
                        label: 'Malware Detections',
                        borderColor: '#dba617',
                        backgroundColor: 'rgba(219, 166, 23, 0.1)',
                        data: []
                    },
                    {
                        label: 'User Activities',
                        borderColor: '#2e7c5f',
                        backgroundColor: 'rgba(46, 124, 95, 0.1)',
                        data: []
                    },
                    {
                        label: 'System Alerts',
                        borderColor: '#8250df',
                        backgroundColor: 'rgba(130, 80, 223, 0.1)',
                        data: []
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false
                    }
                },
                scales: {
                    x: {
                        display: true,
                        title: {
                            display: true,
                            text: 'Time'
                        }
                    },
                    y: {
                        display: true,
                        title: {
                            display: true,
                            text: 'Count'
                        },
                        beginAtZero: true
                    }
                }
            }
        });
    }

    // Update metrics and chart
    function updateMetrics() {
        $.ajax({
            url: kura_ai_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'kura_ai_get_metrics',
                nonce: kura_ai_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    updateMetricsDisplay(response.data.metrics);
                    updateChart(response.data.trend);
                }
            }
        });
    }

    // Update metrics display
    function updateMetricsDisplay(metrics) {
        Object.keys(metrics).forEach(function(key) {
            const $metricValue = $(`.${key} .metric-value`);
            const newValue = metrics[key];
            const oldValue = parseInt($metricValue.text());

            if (newValue !== oldValue) {
                // Animate value change
                $metricValue.prop('Counter', oldValue).animate({
                    Counter: newValue
                }, {
                    duration: 1000,
                    easing: 'swing',
                    step: function(now) {
                        $(this).text(Math.ceil(now));
                    }
                });

                // Highlight change
                $metricValue.addClass('highlight');
                setTimeout(function() {
                    $metricValue.removeClass('highlight');
                }, 1000);
            }
        });
    }

    // Update chart data
    function updateChart(trend) {
        if (!metricsChart) return;

        metricsChart.data.labels = trend.labels;
        metricsChart.data.datasets.forEach(function(dataset, index) {
            dataset.data = trend.datasets[index].data;
        });
        metricsChart.update();
    }

    // Update timeline
    function updateTimeline() {
        $.ajax({
            url: kura_ai_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'kura_ai_get_recent_events',
                nonce: kura_ai_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    updateTimelineDisplay(response.data.events);
                }
            }
        });
    }

    // Update timeline display
    function updateTimelineDisplay(events) {
        const $timeline = $('.timeline');
        let timelineHtml = '';

        events.forEach(function(event) {
            let iconClass = 'info-circle';
            switch (event.event_type) {
                case 'failed_login':
                    iconClass = 'user-lock';
                    break;
                case 'malware_detection':
                    iconClass = 'shield-virus';
                    break;
                case 'file_change':
                    iconClass = 'file-alt';
                    break;
                case 'user_activity':
                    iconClass = 'user';
                    break;
                case 'system_alert':
                    iconClass = 'exclamation-triangle';
                    break;
            }

            let contextHtml = '';
            if (event.context) {
                const context = JSON.parse(event.context);
                contextHtml = '<div class="event-details">';
                Object.keys(context).forEach(function(key) {
                    contextHtml += `
                        <div class="detail-item">
                            <span class="detail-label">${key.replace('_', ' ').toUpperCase()}:</span>
                            <span class="detail-value">${context[key]}</span>
                        </div>
                    `;
                });
                contextHtml += '</div>';
            }

            timelineHtml += `
                <div class="timeline-item">
                    <div class="timeline-icon">
                        <i class="fas fa-${iconClass}"></i>
                    </div>
                    <div class="timeline-content">
                        <h4>${event.message}</h4>
                        <div class="timeline-meta">
                            <span class="event-type">${event.event_type.replace('_', ' ').toUpperCase()}</span>
                            <span class="event-time">${event.time_ago} ago</span>
                        </div>
                        ${contextHtml}
                    </div>
                </div>
            `;
        });

        // Animate new events
        const $newTimeline = $(timelineHtml);
        $newTimeline.hide();
        $timeline.html($newTimeline);
        $newTimeline.fadeIn();
    }

    // Initialize dashboard
    function initializeDashboard() {
        if ($('#security-metrics-chart').length) {
            initializeChart();
            updateMetrics();
            updateTimeline();

            // Set up real-time updates
            setInterval(function() {
                updateMetrics();
                updateTimeline();
            }, 30000); // Update every 30 seconds
        }
    }

    // Initialize when on dashboard page
    if ($('#security-dashboard-page').length) {
        initializeDashboard();
    }
});