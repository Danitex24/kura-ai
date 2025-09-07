<?php
// Get settings and calculate statistics
$settings = get_option('kura_ai_settings', array());
$scan_results = !empty($settings['scan_results']) && is_array($settings['scan_results']) ? $settings['scan_results'] : array();
$last_scan = !empty($settings['last_scan']) ? (int)$settings['last_scan'] : 0;

// Calculate total issues
$total_issues = 0;
if (!empty($scan_results)) {
    foreach ($scan_results as $category => $issues) {
        if (is_array($issues)) {
            $total_issues += count($issues);
        }
    }
}

// Mock some statistics for demo
$files_monitored = 1247;
$security_score = $total_issues > 0 ? max(0, 100 - ($total_issues * 5)) : 100;
?>

<div class="wrap kura-ai-dashboard">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <!-- Dashboard Header -->
    <div class="dashboard-header">
        <div class="dashboard-stats">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-content">
                    <h3>
                        <?php 
                        if ($last_scan > 0) {
                            echo date('M j, Y', $last_scan);
                        } else {
                            _e('Never', 'kura-ai');
                        }
                        ?>
                    </h3>
                    <p><?php _e('Last Scan', 'kura-ai'); ?></p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-shield-alt"></i>
                </div>
                <div class="stat-content">
                    <h3>
                        <?php
                        if ($total_issues === 0) {
                             echo '<span class="status-good">' . __('Secure', 'kura-ai') . '</span>';
                         } else {
                             $issue_text = $total_issues === 1 ? __('issue', 'kura-ai') : __('issues', 'kura-ai');
                             echo '<span class="status-warning">' . sprintf('%d %s', $total_issues, $issue_text) . '</span>';
                         }
                        ?>
                    </h3>
                    <p><?php _e('Security Status', 'kura-ai'); ?></p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-robot"></i>
                </div>
                <div class="stat-content">
                    <h3>
                        <?php 
                        if (empty($settings['enable_ai']) || empty($settings['api_key'])) {
                            echo '<span class="status-disabled">' . __('Disabled', 'kura-ai') . '</span>';
                        } else {
                            echo '<span class="status-enabled">' . __('Enabled', 'kura-ai') . '</span>';
                        }
                        ?>
                    </h3>
                    <p><?php _e('AI Suggestions', 'kura-ai'); ?></p>
                </div>
            </div>
        </div>
        
        <div class="scan-actions">
            <button id="kura-ai-run-scan" class="button button-primary">
                <i class="fas fa-play"></i>
                <?php _e('Run Scan Now', 'kura-ai'); ?>
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
    
    <!-- Scan Progress Modal -->
    <div class="scan-progress-modal" id="scan-progress-modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-shield-alt"></i> <?php _e('Security Scan in Progress', 'kura-ai'); ?></h3>
            </div>
            <div class="modal-body">
                <div class="scan-steps">
                    <div class="scan-step active" data-step="1">
                        <i class="fas fa-search"></i>
                        <span><?php _e('Scanning Files', 'kura-ai'); ?></span>
                    </div>
                    <div class="scan-step" data-step="2">
                        <i class="fas fa-bug"></i>
                        <span><?php _e('Detecting Malware', 'kura-ai'); ?></span>
                    </div>
                    <div class="scan-step" data-step="3">
                        <i class="fas fa-shield-alt"></i>
                        <span><?php _e('Checking Security', 'kura-ai'); ?></span>
                    </div>
                    <div class="scan-step" data-step="4">
                        <i class="fas fa-check"></i>
                        <span><?php _e('Generating Report', 'kura-ai'); ?></span>
                    </div>
                </div>
                <div class="progress-bar">
                    <div class="progress-fill" id="scan-progress-fill"></div>
                </div>
                <p class="progress-text" id="scan-progress-text"><?php _e('Initializing scan...', 'kura-ai'); ?></p>
            </div>
        </div>
    </div>
    
    <!-- Scan Results Section -->
    <div class="scan-results-section" id="scan-results-section" style="display: none;">
        <div class="results-header">
            <h2><i class="fas fa-clipboard-list"></i> <?php _e('Latest Scan Results', 'kura-ai'); ?></h2>
            <button class="button" onclick="document.getElementById('scan-results-section').style.display='none'">
                <i class="fas fa-times"></i> <?php _e('Close', 'kura-ai'); ?>
            </button>
        </div>
        <div id="kura-ai-results-container" class="results-container"></div>
    </div>
    
    <!-- Quick Actions Grid -->
    <div class="quick-actions-grid">
        <div class="action-card">
            <div class="action-icon">
                <i class="fas fa-search"></i>
            </div>
            <div class="action-content">
                <h3><?php _e('Security Scanner', 'kura-ai'); ?></h3>
                <p><?php _e('Run comprehensive security scans and view detailed reports', 'kura-ai'); ?></p>
                <a href="<?php echo admin_url('admin.php?page=kura-ai-scanner'); ?>" class="action-button">
                    <?php _e('Open Scanner', 'kura-ai'); ?>
                </a>
            </div>
        </div>
        
        <div class="action-card">
            <div class="action-icon">
                <i class="fas fa-chart-line"></i>
            </div>
            <div class="action-content">
                <h3><?php _e('Vulnerability Reports', 'kura-ai'); ?></h3>
                <p><?php _e('View detailed vulnerability reports and security metrics', 'kura-ai'); ?></p>
                <a href="<?php echo admin_url('admin.php?page=kura-ai-reports'); ?>" class="action-button">
                    <?php _e('View Reports', 'kura-ai'); ?>
                </a>
            </div>
        </div>
        
        <div class="action-card">
            <div class="action-icon">
                <i class="fas fa-robot"></i>
            </div>
            <div class="action-content">
                <h3><?php _e('AI Suggestions', 'kura-ai'); ?></h3>
                <p><?php _e('Get intelligent recommendations for security improvements', 'kura-ai'); ?></p>
                <a href="<?php echo admin_url('admin.php?page=kura-ai-suggestions'); ?>" class="action-button">
                    <?php _e('Get Suggestions', 'kura-ai'); ?>
                </a>
            </div>
        </div>
        
        <div class="action-card">
            <div class="action-icon">
                <i class="fas fa-file-alt"></i>
            </div>
            <div class="action-content">
                <h3><?php _e('Activity Logs', 'kura-ai'); ?></h3>
                <p><?php _e('Monitor system activity and security events in real-time', 'kura-ai'); ?></p>
                <a href="<?php echo admin_url('admin.php?page=kura-ai-logs'); ?>" class="action-button">
                    <?php _e('View Logs', 'kura-ai'); ?>
                </a>
            </div>
        </div>
        
        <div class="action-card">
            <div class="action-icon">
                <i class="fas fa-cog"></i>
            </div>
            <div class="action-content">
                <h3><?php _e('Plugin Settings', 'kura-ai'); ?></h3>
                <p><?php _e('Configure AI settings, API keys, and security preferences', 'kura-ai'); ?></p>
                <a href="<?php echo admin_url('admin.php?page=kura-ai-settings'); ?>" class="action-button">
                    <?php _e('Open Settings', 'kura-ai'); ?>
                </a>
            </div>
        </div>
        
        <div class="action-card">
            <div class="action-icon">
                <i class="fas fa-shield-alt"></i>
            </div>
            <div class="action-content">
                <h3><?php _e('Security Hardening', 'kura-ai'); ?></h3>
                <p><?php _e('Apply security hardening measures and best practices', 'kura-ai'); ?></p>
                <a href="<?php echo admin_url('admin.php?page=kura-ai-hardening'); ?>" class="action-button">
                    <?php _e('Harden Security', 'kura-ai'); ?>
                </a>
            </div>
        </div>
    </div>
</div>