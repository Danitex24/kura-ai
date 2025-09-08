<?php
/**
 * AI Analysis page template
 */

if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap kura-ai-wrap">
    <?php 
     // Display any settings errors - skip if function doesn't exist
     // settings_errors() is not available in this context
     ?>
    <?php if (function_exists('wp_nonce_field')) wp_nonce_field('kura_ai_nonce', '_wpnonce'); ?>

    <!-- Header -->
    <div class="kura-ai-header">
        <h1><?php echo function_exists('esc_html__') ? esc_html__('AI Security Analysis', 'kura-ai') : 'AI Security Analysis'; ?></h1>
    </div>
    <!-- Analysis Form Card -->
    <div class="kura-ai-card">
        <div class="kura-ai-card-header">
            <h2><?php echo function_exists('esc_html__') ? esc_html__('Code Analysis', 'kura-ai') : 'Code Analysis'; ?></h2>
        </div>
        <div class="kura-ai-card-body">
            <div class="kura-ai-form-group">
                <label for="kura-ai-code"><?php echo function_exists('esc_html__') ? esc_html__('Code to Analyze:', 'kura-ai') : 'Code to Analyze:'; ?></label>
                <textarea id="code-input" name="code" rows="10" cols="50" placeholder="Paste your code here..."></textarea>
            </div>

            <div class="kura-ai-form-group">
                <label for="kura-ai-context"><?php echo function_exists('esc_html__') ? esc_html__('Additional Context:', 'kura-ai') : 'Additional Context:'; ?></label>
                <input type="text" id="context-input" name="context" placeholder="Optional context about the code...">
            </div>

            <div class="kura-ai-form-group">
                <button type="button" id="kura-ai-analyze" class="kura-ai-analyze-btn">
                    <span class="dashicons dashicons-search"></span>
                    <?php echo function_exists('esc_html__') ? esc_html__('Analyze Code', 'kura-ai') : 'Analyze Code'; ?>
                </button>
            </div>
        </div>
    </div>

    <!-- Analysis Results Card -->
    <div id="kura-ai-results" class="kura-ai-card kura-ai-results-card" style="display: none;">
        <div class="kura-ai-card-header">
            <h2><?php echo function_exists('esc_html__') ? esc_html__('Analysis Results', 'kura-ai') : 'Analysis Results'; ?></h2>
            <div class="kura-ai-results-meta">
                <span class="kura-ai-timestamp"></span>
            </div>
        </div>
        <div class="kura-ai-card-body">
            <div class="kura-ai-results-content"></div>
        </div>
    </div>

    <!-- Feedback Card -->
    <div id="kura-ai-feedback-card" class="kura-ai-card kura-ai-feedback-card" style="display: none;">
        <div class="kura-ai-card-header">
            <h2><?php echo function_exists('esc_html__') ? esc_html__('Feedback', 'kura-ai') : 'Feedback'; ?></h2>
        </div>
        <div class="kura-ai-card-body">
            <div class="kura-ai-feedback">
                <h3><?php echo function_exists('esc_html__') ? esc_html__('Was this analysis helpful?', 'kura-ai') : 'Was this analysis helpful?'; ?></h3>
                <div class="kura-ai-feedback-actions">
                    <button type="button" class="button kura-ai-feedback-btn" data-feedback="helpful">
                        <span class="dashicons dashicons-yes"></span>
                        <?php echo function_exists('esc_html__') ? esc_html__('Yes', 'kura-ai') : 'Yes'; ?>
                    </button>
                    <button type="button" class="button kura-ai-feedback-btn" data-feedback="not_helpful">
                        <span class="dashicons dashicons-no"></span>
                        <?php echo function_exists('esc_html__') ? esc_html__('No', 'kura-ai') : 'No'; ?>
                    </button>
                </div>
                <div class="kura-ai-feedback-comment" style="display: none;">
                    <label for="kura-ai-feedback-text"><?php echo function_exists('esc_html__') ? esc_html__('Additional Comments:', 'kura-ai') : 'Additional Comments:'; ?></label>
                    <textarea id="kura-ai-feedback-text" rows="3" class="large-text"></textarea>
                    <button type="button" class="button button-primary kura-ai-submit-feedback">
                        <?php echo function_exists('esc_html__') ? esc_html__('Submit Feedback', 'kura-ai') : 'Submit Feedback'; ?>
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Analytics Dashboard Section -->
    <div class="kura-ai-analytics-dashboard">
        <div class="kura-ai-card">
            <div class="kura-ai-card-header">
                <h2><?php echo function_exists('esc_html__') ? esc_html__('Analytics Dashboard', 'kura-ai') : 'Analytics Dashboard'; ?></h2>
            </div>
            <div class="kura-ai-card-body">
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
                        <canvas id="trendsChart"></canvas>
                    </div>
                    
                    <div class="chart-container">
                        <div class="chart-header">
                            <h2>Health Score Distribution</h2>
                        </div>
                        <canvas id="healthScoreChart"></canvas>
                    </div>
                </div>
                
                <div class="analytics-charts">
                    <div class="chart-container">
                        <div class="chart-header">
                            <h2>Pass/Fail Ratio</h2>
                        </div>
                        <canvas id="passFailChart"></canvas>
                    </div>
                    
                    <div class="chart-container">
                        <div class="chart-header">
                            <h2>Analysis Performance</h2>
                        </div>
                        <canvas id="performanceChart"></canvas>
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
    </div>
</div>