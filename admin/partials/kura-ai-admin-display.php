<div class="wrap kura-ai-dashboard">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <div class="kura-ai-stats">
        <div class="kura-ai-stat-card">
            <h3><?php _e('Last Scan', 'kura-ai'); ?></h3>
            <p class="stat-value">
                <?php 
                $settings = get_option('kura_ai_settings');
                if (!empty($settings['last_scan'])) {
                    echo date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $settings['last_scan']);
                } else {
                    _e('Never', 'kura-ai');
                }
                ?>
            </p>
            <button id="kura-ai-run-scan" class="button button-primary">
                <?php _e('Run Scan Now', 'kura-ai'); ?>
            </button>
        </div>
        
        <div class="kura-ai-stat-card">
            <h3><?php _e('Security Status', 'kura-ai'); ?></h3>
            <p class="stat-value">
                <?php
                if (empty($settings['scan_results'])) {
                    _e('Not scanned', 'kura-ai');
                } else {
                    $issues = 0;
                    foreach ($settings['scan_results'] as $category) {
                        $issues += count($category);
                    }
                    
                    if ($issues === 0) {
                        echo '<span class="status-good">' . __('Secure', 'kura-ai') . '</span>';
                    } else {
                        echo '<span class="status-warning">' . sprintf(_n('%d issue', '%d issues', $issues, 'kura-ai'), $issues) . '</span>';
                    }
                }
                ?>
            </p>
            <a href="<?php echo admin_url('admin.php?page=kura-ai-reports'); ?>" class="button">
                <?php _e('View Details', 'kura-ai'); ?>
            </a>
        </div>
        
        <div class="kura-ai-stat-card">
            <h3><?php _e('AI Suggestions', 'kura-ai'); ?></h3>
            <p class="stat-value">
                <?php 
                if (empty($settings['enable_ai']) || empty($settings['api_key'])) {
                    _e('Disabled', 'kura-ai');
                } else {
                    _e('Enabled', 'kura-ai');
                }
                ?>
            </p>
            <a href="<?php echo admin_url('admin.php?page=kura-ai-settings'); ?>" class="button">
                <?php _e('Configure', 'kura-ai'); ?>
            </a>
        </div>
    </div>
    
    <div class="kura-ai-scan-progress" style="display: none;">
        <div class="kura-ai-progress-bar">
            <div class="kura-ai-progress-bar-fill"></div>
        </div>
        <p class="kura-ai-progress-message"><?php _e('Preparing scan...', 'kura-ai'); ?></p>
    </div>
    
    <div class="kura-ai-scan-results" style="display: none;">
        <h2><?php _e('Scan Results', 'kura-ai'); ?></h2>
        <div id="kura-ai-results-container"></div>
    </div>
    
    <div class="kura-ai-quick-links">
    <h2><?php _e('Quick Links', 'kura-ai'); ?></h2>
        <p>
            <a href="<?php echo admin_url('admin.php?page=kura-ai-reports'); ?>" class="button button-primary">
                <?php _e('View Vulnerability Reports', 'kura-ai'); ?>
            </a>
            <a href="<?php echo admin_url('admin.php?page=kura-ai-suggestions'); ?>" class="button">
                <?php _e('Get AI Fix Suggestions', 'kura-ai'); ?>
            </a>
            <a href="<?php echo admin_url('admin.php?page=kura-ai-logs'); ?>" class="button">
                <?php _e('View Activity Logs', 'kura-ai'); ?>
            </a>
            <a href="<?php echo admin_url('admin.php?page=kura-ai-settings'); ?>" class="button">
                <?php _e('Plugin Settings', 'kura-ai'); ?>
            </a>
        </p>
    </div>
</div>