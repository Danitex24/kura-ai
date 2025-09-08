<?php
/**
 * Provide an admin area view for the analytics dashboard.
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @package    Kura_AI
 * @subpackage Kura_AI/admin/partials
 * @author     Daniel Abughdyer <daniel@danovatesolutions.org>
 */

// This file should primarily consist of HTML with a little bit of PHP.
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <div class="kura-ai-analytics-dashboard">
        <!-- Summary Cards -->
        <div class="analytics-summary-cards">
            <div class="summary-card">
                <div class="card-icon">
                    <span class="dashicons dashicons-chart-line"></span>
                </div>
                <div class="card-content">
                    <h3 id="total-analyses">0</h3>
                    <p>Total Analyses</p>
                </div>
            </div>
            
            <div class="summary-card">
                <div class="card-icon">
                    <span class="dashicons dashicons-yes-alt"></span>
                </div>
                <div class="card-content">
                    <h3 id="passed-analyses">0</h3>
                    <p>Passed Analyses</p>
                </div>
            </div>
            
            <div class="summary-card">
                <div class="card-icon">
                    <span class="dashicons dashicons-warning"></span>
                </div>
                <div class="card-content">
                    <h3 id="failed-analyses">0</h3>
                    <p>Failed Analyses</p>
                </div>
            </div>
            
            <div class="summary-card">
                <div class="card-icon">
                    <span class="dashicons dashicons-performance"></span>
                </div>
                <div class="card-content">
                    <h3 id="avg-health-score">0</h3>
                    <p>Avg Health Score</p>
                </div>
            </div>
        </div>
        
        <!-- Charts Section -->
        <div class="analytics-charts">
            <div class="chart-container">
                <div class="chart-header">
                    <h2>Analysis Trends</h2>
                    <div class="chart-filters">
                        <select id="trend-period">
                            <option value="7">Last 7 Days</option>
                            <option value="30" selected>Last 30 Days</option>
                            <option value="90">Last 90 Days</option>
                        </select>
                    </div>
                </div>
                <canvas id="trendsChart" width="400" height="200"></canvas>
            </div>
            
            <div class="chart-container">
                <div class="chart-header">
                    <h2>Health Score Distribution</h2>
                </div>
                <canvas id="healthScoreChart" width="400" height="200"></canvas>
            </div>
        </div>
        
        <div class="analytics-charts">
            <div class="chart-container">
                <div class="chart-header">
                    <h2>Pass/Fail Ratio</h2>
                </div>
                <canvas id="passFailChart" width="400" height="200"></canvas>
            </div>
            
            <div class="chart-container">
                <div class="chart-header">
                    <h2>Analysis Performance</h2>
                </div>
                <canvas id="performanceChart" width="400" height="200"></canvas>
            </div>
        </div>
        
        <!-- Recent Analyses Table -->
        <div class="recent-analyses">
            <div class="recent-analyses-header">
                <h2>Recent Analyses</h2>
                <button type="button" id="reset-recent-analyses" class="button button-secondary">
                    <span class="dashicons dashicons-update"></span>
                    Reset Recent Analyses
                </button>
            </div>
            <div class="table-container">
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>User</th>
                            <th>Code Length</th>
                            <th>Analysis Time</th>
                            <th>Health Score</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody id="recent-analyses-tbody">
                        <tr>
                            <td colspan="6" class="loading">Loading recent analyses...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>