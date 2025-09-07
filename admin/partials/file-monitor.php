<?php
/**
 * File Monitor Admin Page
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

$file_monitor = new \Kura_AI\Kura_AI_File_Monitor();
$monitored_files = $file_monitor->get_monitored_files();
$critical_files = $file_monitor->get_critical_wordpress_files();
$chart_data = $file_monitor->get_chart_data();
$recent_scans = $file_monitor->get_recent_scan_results(5);

// Ensure we have arrays to work with
$critical_files = \is_array($critical_files) ? $critical_files : [];
$monitored_files = \is_array($monitored_files) ? $monitored_files : [];
$chart_data = \is_array($chart_data) ? $chart_data : [];
$recent_scans = \is_array($recent_scans) ? $recent_scans : [];

// Handle form submissions
if ($_POST) {
    if (isset($_POST['run_scan']) && \wp_verify_nonce($_POST['_wpnonce'], 'run_scan')) {
        $scan_results = $file_monitor->run_file_scan();
        echo '<div class="notice notice-success"><p>File scan completed. ' . count($scan_results) . ' files scanned.</p></div>';
        $chart_data = $file_monitor->get_chart_data(); // Refresh chart data
        $recent_scans = $file_monitor->get_recent_scan_results(5); // Refresh recent scans
    }
    
    if (isset($_POST['add_file']) && \wp_verify_nonce($_POST['_wpnonce'], 'add_file')) {
        $file_path = \sanitize_text_field($_POST['file_path']);
        $result = $file_monitor->add_monitored_file($file_path);
        
        if (is_wp_error($result)) {
            echo '<div class="notice notice-error"><p>' . esc_html($result->get_error_message()) . '</p></div>';
        } else {
            echo '<div class="notice notice-success"><p>File added to monitoring successfully.</p></div>';
            $monitored_files = $file_monitor->get_monitored_files(); // Refresh list
        }
    }
    
    if (isset($_POST['create_version']) && \wp_verify_nonce($_POST['_wpnonce'], 'create_version')) {
        $file_path = \sanitize_text_field($_POST['file_path']);
        $description = \sanitize_text_field($_POST['description']);
        $result = $file_monitor->create_version($file_path, $description);
        
        if (is_wp_error($result)) {
            echo '<div class="notice notice-error"><p>' . esc_html($result->get_error_message()) . '</p></div>';
        } else {
            echo '<div class="notice notice-success"><p>Version created successfully.</p></div>';
        }
    }
    
    if (isset($_POST['rollback_version']) && \wp_verify_nonce($_POST['_wpnonce'], 'rollback_version')) {
        $version_id = \intval($_POST['version_id']);
        $result = $file_monitor->rollback_version($version_id);
        
        if (is_wp_error($result)) {
            echo '<div class="notice notice-error"><p>' . esc_html($result->get_error_message()) . '</p></div>';
        } else {
            echo '<div class="notice notice-success"><p>Version restored successfully.</p></div>';
        }
    }
}
?>

<div class="wrap">
    <h1>WordPress File Monitor</h1>
    
    <div class="kura-ai-admin-header">
        <form method="post" style="display: inline-block;">
            <?php wp_nonce_field('run_scan'); ?>
            <button type="submit" name="run_scan" class="button button-primary">Run Security Scan</button>
        </form>
        <button type="button" class="button button-secondary" onclick="openModal('addFileModal')">Add Custom File</button>
    </div>
    
    <!-- Dashboard Stats -->
    <div class="kura-ai-stats-grid">
        <div class="kura-ai-stat-box">
            <h3>Critical Files</h3>
            <div class="kura-ai-stat-number"><?php echo is_countable($critical_files) ? count($critical_files) : 0; ?></div>
            <p>WordPress core files monitored</p>
        </div>
        <div class="kura-ai-stat-box">
            <h3>Custom Files</h3>
            <div class="kura-ai-stat-number"><?php echo is_countable($monitored_files) ? count($monitored_files) : 0; ?></div>
            <p>Additional files monitored</p>
        </div>
        <div class="kura-ai-stat-box">
            <h3>Recent Scans</h3>
            <div class="kura-ai-stat-number"><?php echo is_countable($recent_scans) ? count($recent_scans) : 0; ?></div>
            <p>Scans in last 24 hours</p>
        </div>
    </div>
    
    <!-- Charts Section -->
    <div class="kura-ai-charts-section">
        <h2>Security Overview</h2>
        <div class="kura-ai-charts-grid">
            <div class="kura-ai-chart-container">
                <h3>File Status Distribution</h3>
                <canvas id="statusChart" width="400" height="200"></canvas>
            </div>
            <div class="kura-ai-chart-container">
                <h3>Risk Level Distribution</h3>
                <canvas id="riskChart" width="400" height="200"></canvas>
            </div>
            <div class="kura-ai-chart-container">
                <h3>File Changes Timeline</h3>
                <canvas id="timelineChart" width="400" height="200"></canvas>
            </div>
            <div class="kura-ai-chart-container">
                <h3>File Size Trends</h3>
                <canvas id="sizeChart" width="400" height="200"></canvas>
            </div>
        </div>
    </div>
    
    <!-- Recent Scan Results -->
    <div class="kura-ai-recent-scans">
        <h2>Recent Scan Results</h2>
        <?php if (!empty($recent_scans)): ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>File Name</th>
                        <th>Status</th>
                        <th>Risk Level</th>
                        <th>Last Modified</th>
                        <th>Changes Detected</th>
                        <th>Scan Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_scans as $scan): ?>
                        <tr class="scan-row scan-<?php echo \esc_attr($scan['status'] ?? ''); ?>">
                            <td><strong><?php echo esc_html($scan['file_name'] ?? ''); ?></strong></td>
                            <td>
                                <span class="status-badge status-<?php echo esc_attr($scan['status'] ?? ''); ?>">
                                    <?php echo esc_html(ucfirst($scan['status'] ?? '')); ?>
                                </span>
                            </td>
                            <td>
                                <span class="risk-badge risk-<?php echo esc_attr($scan['risk_level'] ?? ''); ?>">
                                    <?php echo esc_html(ucfirst($scan['risk_level'] ?? '')); ?>
                                </span>
                            </td>
                            <td><?php echo esc_html($scan['last_modified'] ?? ''); ?></td>
                            <td><?php echo esc_html($scan['changes_detected'] ?? 'None'); ?></td>
                            <td><?php echo esc_html($scan['scan_date'] ?? ''); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="kura-ai-empty-state">
                <p>No scan results available. Run your first security scan to see results here.</p>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Critical Files List -->
    <div class="kura-ai-critical-files">
        <h2>Critical WordPress Files</h2>
        <div class="kura-ai-files-grid">
            <?php if (is_array($critical_files) && !empty($critical_files)): foreach ($critical_files as $file_name => $file_path): ?>
                <div class="kura-ai-file-card">
                    <div class="file-icon">
                        <span class="dashicons dashicons-media-code"></span>
                    </div>
                    <div class="file-info">
                        <h4><?php echo esc_html($file_name); ?></h4>
                        <p><?php echo esc_html($file_path); ?></p>
                        <span class="file-status">
                            <?php echo file_exists($file_path) ? '<span class="status-ok">✓ Found</span>' : '<span class="status-missing">✗ Missing</span>'; ?>
                        </span>
                    </div>
                </div>
            <?php endforeach; else: ?>
                <div class="kura-ai-empty-state">
                    <p>No critical files found. This might indicate an issue with the file monitoring system.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="kura-ai-monitor-actions">
        <button type="button" id="kura-ai-add-file" class="button button-primary">
            <span class="dashicons dashicons-plus"></span>
            <?php echo esc_html__('Add File to Monitor', 'kura-ai'); ?>
        </button>
    </div>

    <div class="kura-ai-monitored-files">
        <h2><?php echo esc_html__('Monitored Files', 'kura-ai'); ?></h2>
        <div id="kura-ai-file-list" class="kura-ai-file-list">
            <?php
            $monitor = new \Kura_AI\Kura_AI_File_Monitor();
            $files = $monitor->get_monitored_files();
            $files = is_array($files) ? $files : [];
            
            if (empty($files)) :
            ?>
            <div class="kura-ai-no-files">
                <?php echo esc_html__('No files are currently being monitored.', 'kura-ai'); ?>
            </div>
            <?php
            else :
                foreach ($files as $file) :
                    $versions = $monitor->get_file_versions($file);
                    $versions = is_array($versions) ? $versions : [];
            ?>
            <div class="kura-ai-file-item" data-file="<?php echo esc_attr($file); ?>">
                <div class="kura-ai-file-header">
                    <h3><?php echo esc_html(basename($file)); ?></h3>
                    <div class="kura-ai-file-actions">
                        <button type="button" class="button kura-ai-create-version">
                            <span class="dashicons dashicons-backup"></span>
                            <?php echo esc_html__('Create Version', 'kura-ai'); ?>
                        </button>
                        <button type="button" class="button kura-ai-remove-file">
                            <span class="dashicons dashicons-trash"></span>
                            <?php echo esc_html__('Remove', 'kura-ai'); ?>
                        </button>
                    </div>
                </div>
                <div class="kura-ai-file-versions">
                    <?php if (!empty($versions)) : ?>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php echo esc_html__('Version', 'kura-ai'); ?></th>
                                <th><?php echo esc_html__('Created', 'kura-ai'); ?></th>
                                <th><?php echo esc_html__('Description', 'kura-ai'); ?></th>
                                <th><?php echo esc_html__('Actions', 'kura-ai'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($versions as $version) : ?>
                            <tr data-version="<?php echo esc_attr($version->id); ?>">
                                <td><?php echo esc_html(substr($version->hash, 0, 8)); ?></td>
                                <td><?php echo esc_html($version->created_at); ?></td>
                                <td><?php echo esc_html($version->description); ?></td>
                                <td>
                                    <button type="button" class="button button-small kura-ai-rollback-version">
                                        <?php echo esc_html__('Rollback', 'kura-ai'); ?>
                                    </button>
                                    <button type="button" class="button button-small kura-ai-compare-version">
                                        <?php echo esc_html__('Compare', 'kura-ai'); ?>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php else : ?>
                    <p class="kura-ai-no-versions">
                        <?php echo esc_html__('No versions available.', 'kura-ai'); ?>
                    </p>
                    <?php endif; ?>
                </div>
            </div>
            <?php
                endforeach;
            endif;
            ?>
        </div>
    </div>

    <!-- Add File Modal -->
    <div id="kura-ai-add-file-modal" class="kura-ai-modal" style="display: none;">
        <div class="kura-ai-modal-content">
            <h2><?php echo esc_html__('Add File to Monitor', 'kura-ai'); ?></h2>
            <div class="kura-ai-modal-body">
                <div class="kura-ai-form-group">
                    <label for="kura-ai-file-path"><?php echo esc_html__('File Path:', 'kura-ai'); ?></label>
                    <input type="text" id="kura-ai-file-path" class="regular-text" />
                </div>
            </div>
            <div class="kura-ai-modal-footer">
                <button type="button" class="button kura-ai-modal-close">
                    <?php echo esc_html__('Cancel', 'kura-ai'); ?>
                </button>
                <button type="button" class="button button-primary kura-ai-modal-submit">
                    <?php echo esc_html__('Add File', 'kura-ai'); ?>
                </button>
            </div>
        </div>
    </div>

    <!-- Create Version Modal -->
    <div id="kura-ai-create-version-modal" class="kura-ai-modal" style="display: none;">
        <div class="kura-ai-modal-content">
            <h2><?php echo esc_html__('Create New Version', 'kura-ai'); ?></h2>
            <div class="kura-ai-modal-body">
                <div class="kura-ai-form-group">
                    <label for="kura-ai-version-description"><?php echo esc_html__('Description:', 'kura-ai'); ?></label>
                    <textarea id="kura-ai-version-description" rows="3"></textarea>
                </div>
            </div>
            <div class="kura-ai-modal-footer">
                <button type="button" class="button kura-ai-modal-close">
                    <?php echo \esc_html__('Cancel', 'kura-ai'); ?>
                </button>
                <button type="button" class="button button-primary kura-ai-modal-submit">
                    <?php echo esc_html__('Create Version', 'kura-ai'); ?>
                </button>
            </div>
        </div>
    </div>

    <!-- Compare Versions Modal -->
    <div id="kura-ai-compare-modal" class="kura-ai-modal" style="display: none;">
        <div class="kura-ai-modal-content kura-ai-modal-large">
            <h2><?php echo esc_html__('Compare Versions', 'kura-ai'); ?></h2>
            <div class="kura-ai-modal-body">
                <div class="kura-ai-version-select">
                    <div class="kura-ai-form-group">
                        <label for="kura-ai-version-1"><?php echo esc_html__('Version 1:', 'kura-ai'); ?></label>
                        <select id="kura-ai-version-1"></select>
                    </div>
                    <div class="kura-ai-form-group">
                        <label for="kura-ai-version-2"><?php echo esc_html__('Version 2:', 'kura-ai'); ?></label>
                        <select id="kura-ai-version-2"></select>
                    </div>
                </div>
                <div id="kura-ai-diff-viewer" class="kura-ai-diff-viewer"></div>
            </div>
            <div class="kura-ai-modal-footer">
                <button type="button" class="button kura-ai-modal-close">
                    <?php echo esc_html__('Close', 'kura-ai'); ?>
                </button>
            </div>
        </div>
    </div>

    <!-- Add Custom File Modal -->
    <div id="addFileModal" class="kura-ai-modal" style="display: none;">
        <div class="kura-ai-modal-content">
            <div class="modal-header">
                <h3><?php echo esc_html__('Add Custom File to Monitor', 'kura-ai'); ?></h3>
                <button type="button" class="close-modal" onclick="closeModal('addFileModal')">&times;</button>
            </div>
            <div class="modal-body">
                <form id="add-file-form" method="post">
                    <?php wp_nonce_field('add_file'); ?>
                    <div class="form-group">
                        <label for="file_path"><?php echo esc_html__('File Path:', 'kura-ai'); ?></label>
                        <input type="text" id="file_path" name="file_path" class="regular-text" placeholder="/path/to/your/file.php" required>
                        <p class="description"><?php echo esc_html__('Enter the full path to the file you want to monitor for changes.', 'kura-ai'); ?></p>
                    </div>
                    <div class="form-actions">
                        <button type="button" class="button" onclick="closeModal('addFileModal')"><?php echo esc_html__('Cancel', 'kura-ai'); ?></button>
                        <button type="submit" name="add_file" class="button button-primary"><?php echo esc_html__('Add File', 'kura-ai'); ?></button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>