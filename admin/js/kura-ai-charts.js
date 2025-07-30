jQuery(document).ready(function ($) {
    $.ajax({
        url: ajaxurl,
        type: 'POST',
        data: {
            action: 'kura_ai_get_chart_data',
            nonce: kura_ai_woocommerce_admin.nonce
        },
        success: function (response) {
            if (response.success) {
                create_sales_trend_chart(response.data.sales_trend);
                create_top_selling_products_chart(response.data.top_selling_products);
                create_category_distribution_chart(response.data.category_distribution);
                create_store_health_chart(response.data.store_health_evolution);
                create_suggestion_frequency_chart(response.data.suggestion_frequency);
            }
        }
    });

    function create_sales_trend_chart(data) {
        var ctx = document.getElementById('kura-ai-sales-trend-chart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: data.labels,
                datasets: [{
                    label: 'Sales',
                    data: data.data,
                    backgroundColor: 'rgba(54, 162, 235, 0.2)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1
                }]
            }
        });
    }

    function create_top_selling_products_chart(data) {
        var ctx = document.getElementById('kura-ai-top-selling-products-chart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: data.labels,
                datasets: [{
                    label: 'Sales',
                    data: data.data,
                    backgroundColor: 'rgba(255, 99, 132, 0.2)',
                    borderColor: 'rgba(255, 99, 132, 1)',
                    borderWidth: 1
                }]
            }
        });
    }

    function create_category_distribution_chart(data) {
        var ctx = document.getElementById('kura-ai-category-distribution-chart').getContext('2d');
        new Chart(ctx, {
            type: 'pie',
            data: {
                labels: data.labels,
                datasets: [{
                    label: 'Categories',
                    data: data.data,
                    backgroundColor: [
                        'rgba(255, 99, 132, 0.2)',
                        'rgba(54, 162, 235, 0.2)',
                        'rgba(255, 206, 86, 0.2)'
                    ],
                    borderColor: [
                        'rgba(255, 99, 132, 1)',
                        'rgba(54, 162, 235, 1)',
                        'rgba(255, 206, 86, 1)'
                    ],
                    borderWidth: 1
                }]
            }
        });
    }

    function create_store_health_chart(data) {
        var ctx = document.getElementById('kura-ai-store-health-chart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: data.labels,
                datasets: [{
                    label: 'Store Health',
                    data: data.data,
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    borderColor: 'rgba(75, 192, 192, 1)',
                    borderWidth: 1
                }]
            }
        });
    }

    function create_suggestion_frequency_chart(data) {
        var ctx = document.getElementById('kura-ai-suggestion-frequency-chart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: data.labels,
                datasets: [{
                    label: 'Suggestion Frequency',
                    data: data.data,
                    backgroundColor: 'rgba(153, 102, 255, 0.2)',
                    borderColor: 'rgba(153, 102, 255, 1)',
                    borderWidth: 1
                }]
            }
        });
    }
});
