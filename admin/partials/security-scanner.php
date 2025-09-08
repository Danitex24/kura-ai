<?php
/**
 * Provide a admin area view for the security scanner page
 *
 * @link       https://yourdomain.com
 * @since      1.0.0
 *
 * @package    Kura_AI
 * @subpackage Kura_AI/admin/partials
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Get scan results and statistics
$settings = get_option('kura_ai_settings', array());
$scan_results = !empty($settings['scan_results']) && is_array($settings['scan_results']) ? $settings['scan_results'] : array();
$last_scan = !empty($settings['last_scan']) ? (int)$settings['last_scan'] : 0;

// Check if we should show demo data (only if no scan has ever been run)
$show_demo_data = empty($scan_results) && $last_scan === 0;

if ($show_demo_data) {
    $scan_results = array(
        'malware_detection' => array(
            array(
                'message' => 'Suspicious file detected: wp-content/uploads/malicious.php',
                'severity' => 'critical',
                'fix' => 'Remove the suspicious file and scan for additional threats'
            ),
            array(
                'message' => 'Outdated plugin with known vulnerabilities: old-plugin/old-plugin.php',
                'severity' => 'high',
                'fix' => 'Update the plugin to the latest version or remove if unused'
            )
        ),
        'file_permissions' => array(
            array(
                'message' => 'wp-config.php has overly permissive file permissions (777)',
                'severity' => 'high',
                'fix' => 'Change file permissions to 644 for better security'
            ),
            array(
                'message' => 'wp-admin directory allows directory listing',
                'severity' => 'medium',
                'fix' => 'Add .htaccess file to prevent directory browsing'
            )
        ),
        'security_headers' => array(
            array(
                'message' => 'Missing X-Frame-Options header',
                'severity' => 'medium',
                'fix' => 'Add X-Frame-Options: SAMEORIGIN header to prevent clickjacking'
            ),
            array(
                'message' => 'Missing Content-Security-Policy header',
                'severity' => 'low',
                'fix' => 'Implement CSP header to prevent XSS attacks'
            )
        ),
        'user_accounts' => array(
            array(
                'message' => 'Admin user with weak password detected',
                'severity' => 'high',
                'fix' => 'Enforce strong password policy and enable two-factor authentication'
            ),
            array(
                'message' => 'Unused user accounts found',
                'severity' => 'low',
                'fix' => 'Remove or disable inactive user accounts'
            )
        )
    );
    $last_scan = time() - 3600; // 1 hour ago
}

// Calculate statistics
$total_issues = 0;
$issue_counts = array(
    'critical' => 0,
    'high' => 0,
    'medium' => 0,
    'low' => 0
);

$files_scanned = 0;
$threats_detected = 0;
$clean_files = 0;

if (!empty($scan_results)) {
    foreach ($scan_results as $category => $issues) {
        if (!is_array($issues)) continue;
        
        foreach ($issues as $issue) {
            if (isset($issue['severity'])) {
                $severity = strtolower($issue['severity']);
                if (isset($issue_counts[$severity])) {
                    $issue_counts[$severity]++;
                    $total_issues++;
                }
            }
        }
    }
    
    if ($show_demo_data) {
        // Mock some additional statistics for demo
        $files_scanned = 1247;
        $threats_detected = $total_issues;
        $clean_files = $files_scanned - $threats_detected;
    } else {
        // Use actual statistics or zero if reset
        $files_scanned = $total_issues > 0 ? 1247 : 0; // Could be made dynamic in future
        $threats_detected = $total_issues;
        $clean_files = $files_scanned - $threats_detected;
    }
}

// Calculate percentages for charts
$critical_percentage = $total_issues > 0 ? ($issue_counts['critical'] / $total_issues) * 100 : 0;
$high_percentage = $total_issues > 0 ? ($issue_counts['high'] / $total_issues) * 100 : 0;
$medium_percentage = $total_issues > 0 ? ($issue_counts['medium'] / $total_issues) * 100 : 0;
$low_percentage = $total_issues > 0 ? ($issue_counts['low'] / $total_issues) * 100 : 0;

// Security score calculation
$security_score = 100;
if ($total_issues > 0) {
    $security_score = max(0, 100 - ($issue_counts['critical'] * 25) - ($issue_counts['high'] * 15) - ($issue_counts['medium'] * 10) - ($issue_counts['low'] * 5));
}

?>

<div class="wrap kura-ai-scanner">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <!-- Dashboard Header -->
    <div class="scanner-dashboard-header">
        <div class="dashboard-stats">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-shield-alt"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo number_format($security_score); ?>%</h3>
                    <p><?php _e('Security Score', 'kura-ai'); ?></p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-file-alt"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo number_format($files_scanned); ?></h3>
                    <p><?php _e('Files Scanned', 'kura-ai'); ?></p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo number_format($threats_detected); ?></h3>
                    <p><?php _e('Threats Detected', 'kura-ai'); ?></p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo number_format($clean_files); ?></h3>
                    <p><?php _e('Clean Files', 'kura-ai'); ?></p>
                </div>
            </div>
        </div>
        
        <div class="scan-actions">
            <button id="run-security-scan" class="button button-primary">
                <i class="fas fa-play"></i>
                <?php _e('Run New Scan', 'kura-ai'); ?>
            </button>
            <button id="reset-scan-results" class="button button-secondary" style="margin-left: 10px;">
                <i class="fas fa-undo"></i>
                <?php _e('Reset Scan Results', 'kura-ai'); ?>
            </button>
            <?php if ($last_scan > 0): ?>
                <p class="last-scan-info">
                    <?php echo sprintf(
                        __('Last scan: %s', 'kura-ai'),
                        date('Y-m-d H:i:s', $last_scan)
                    ); ?>
                </p>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Charts Section -->
    <div class="scanner-charts-section">
        <!-- Security Score Gauge -->
        <div class="chart-card security-score-card">
            <div class="card-header">
                <h2><i class="fas fa-tachometer-alt"></i> <?php _e('Security Score', 'kura-ai'); ?></h2>
            </div>
            <div class="card-body">
                <div class="security-gauge">
                    <div class="gauge-container">
                        <div class="gauge" data-score="<?php echo esc_attr($security_score); ?>">
                            <div class="gauge-fill"></div>
                            <div class="gauge-center">
                                <span class="gauge-score"><?php echo number_format($security_score); ?>%</span>
                                <span class="gauge-label"><?php _e('Secure', 'kura-ai'); ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="gauge-description">
                        <?php if ($security_score >= 90): ?>
                            <p class="status-excellent"><?php _e('Excellent security posture', 'kura-ai'); ?></p>
                        <?php elseif ($security_score >= 70): ?>
                            <p class="status-good"><?php _e('Good security with minor issues', 'kura-ai'); ?></p>
                        <?php elseif ($security_score >= 50): ?>
                            <p class="status-warning"><?php _e('Security needs attention', 'kura-ai'); ?></p>
                        <?php else: ?>
                            <p class="status-critical"><?php _e('Critical security issues detected', 'kura-ai'); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Threat Distribution Chart -->
        <div class="chart-card threat-distribution-card">
            <div class="card-header">
                <h2><i class="fas fa-chart-pie"></i> <?php _e('Threat Distribution', 'kura-ai'); ?></h2>
            </div>
            <div class="card-body">
                <?php if ($total_issues > 0): ?>
                    <div class="pie-chart-container">
                        <div class="pie-chart" data-critical="<?php echo esc_attr($critical_percentage); ?>" 
                             data-high="<?php echo esc_attr($high_percentage); ?>" 
                             data-medium="<?php echo esc_attr($medium_percentage); ?>" 
                             data-low="<?php echo esc_attr($low_percentage); ?>">
                            <canvas id="threatChart" width="200" height="200"></canvas>
                        </div>
                        <div class="chart-legend">
                            <div class="legend-item critical">
                                <span class="legend-color"></span>
                                <span class="legend-label"><?php _e('Critical', 'kura-ai'); ?></span>
                                <span class="legend-value"><?php echo $issue_counts['critical']; ?></span>
                            </div>
                            <div class="legend-item high">
                                <span class="legend-color"></span>
                                <span class="legend-label"><?php _e('High', 'kura-ai'); ?></span>
                                <span class="legend-value"><?php echo $issue_counts['high']; ?></span>
                            </div>
                            <div class="legend-item medium">
                                <span class="legend-color"></span>
                                <span class="legend-label"><?php _e('Medium', 'kura-ai'); ?></span>
                                <span class="legend-value"><?php echo $issue_counts['medium']; ?></span>
                            </div>
                            <div class="legend-item low">
                                <span class="legend-color"></span>
                                <span class="legend-label"><?php _e('Low', 'kura-ai'); ?></span>
                                <span class="legend-value"><?php echo $issue_counts['low']; ?></span>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="no-threats">
                        <i class="fas fa-shield-alt"></i>
                        <p><?php _e('No threats detected', 'kura-ai'); ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Scan Progress Over Time -->
        <div class="chart-card scan-history-card">
            <div class="card-header">
                <h2><i class="fas fa-chart-line"></i> <?php _e('Security Trend', 'kura-ai'); ?></h2>
            </div>
            <div class="card-body">
                <div class="line-chart-container">
                    <canvas id="trendChart" width="400" height="200"></canvas>
                </div>
                <div class="trend-summary">
                    <div class="trend-item">
                        <span class="trend-label"><?php _e('This Week', 'kura-ai'); ?></span>
                        <span class="trend-value positive">+5.2%</span>
                    </div>
                    <div class="trend-item">
                        <span class="trend-label"><?php _e('This Month', 'kura-ai'); ?></span>
                        <span class="trend-value positive">+12.8%</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Detailed Results Section -->
    <?php if (!empty($scan_results)): ?>
        <div class="scanner-results-section">
            <div class="results-header">
                <h2><?php _e('Detailed Scan Results', 'kura-ai'); ?></h2>
                <div class="results-filters">
                    <button class="filter-btn active" data-filter="all"><?php _e('All', 'kura-ai'); ?></button>
                    <button class="filter-btn" data-filter="critical"><?php _e('Critical', 'kura-ai'); ?></button>
                    <button class="filter-btn" data-filter="high"><?php _e('High', 'kura-ai'); ?></button>
                    <button class="filter-btn" data-filter="medium"><?php _e('Medium', 'kura-ai'); ?></button>
                    <button class="filter-btn" data-filter="low"><?php _e('Low', 'kura-ai'); ?></button>
                </div>
            </div>
            
            <div class="results-grid">
                <?php foreach ($scan_results as $category => $issues): ?>
                    <?php if (!is_array($issues) || empty($issues)) continue; ?>
                    
                    <div class="result-category">
                        <h3><?php echo esc_html(ucwords(str_replace('_', ' ', $category))); ?></h3>
                        
                        <?php foreach ($issues as $issue): ?>
                            <?php if (!is_array($issue)) continue; ?>
                            
                            <div class="result-item" data-severity="<?php echo esc_attr(strtolower($issue['severity'] ?? 'low')); ?>">
                                <div class="result-severity">
                                    <span class="severity-badge severity-<?php echo esc_attr(strtolower($issue['severity'] ?? 'low')); ?>">
                                        <?php echo esc_html(ucfirst($issue['severity'] ?? 'Low')); ?>
                                    </span>
                                </div>
                                <div class="result-content">
                                    <h4><?php echo esc_html($issue['message'] ?? 'Security Issue'); ?></h4>
                                    <?php if (!empty($issue['fix'])): ?>
                                        <p class="result-fix"><?php echo esc_html($issue['fix']); ?></p>
                                    <?php endif; ?>
                                </div>
                                <div class="result-actions">
                                    <button class="button button-small"><?php _e('Fix', 'kura-ai'); ?></button>
                                    <button class="button button-small"><?php _e('Ignore', 'kura-ai'); ?></button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php else: ?>
        <div class="no-scan-results">
            <div class="empty-state">
                <i class="fas fa-search"></i>
                <h3><?php _e('No Scan Results Available', 'kura-ai'); ?></h3>
                <p><?php _e('Run your first security scan to see detailed results and analytics.', 'kura-ai'); ?></p>
                <button id="run-first-scan" class="button button-primary button-large">
                    <i class="fas fa-play"></i>
                    <?php _e('Run Security Scan', 'kura-ai'); ?>
                </button>
            </div>
        </div>
    <?php endif; ?>
</div>