<?php
/**
 * Provide a admin area view for the plugin's compliance reports
 *
 * @package    Kura_AI
 * @subpackage Kura_AI/admin/partials
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}
?>

<div class="wrap kura-ai-compliance">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <div class="compliance-header">
        <div class="compliance-actions">
            <select id="compliance-standard" class="compliance-standard-select">
                <option value="pci_dss"><?php esc_html_e('PCI DSS', 'kura-ai'); ?></option>
                <option value="gdpr"><?php esc_html_e('GDPR', 'kura-ai'); ?></option>
                <option value="hipaa"><?php esc_html_e('HIPAA', 'kura-ai'); ?></option>
            </select>
            <button type="button" id="generate-report" class="button button-primary">
                <i class="fas fa-sync-alt"></i>
                <?php esc_html_e('Generate Report', 'kura-ai'); ?>
            </button>
        </div>
    </div>

    <div id="compliance-report" class="compliance-report hidden">
        <div class="report-meta">
            <div class="meta-item">
                <span class="meta-label"><?php esc_html_e('Standard:', 'kura-ai'); ?></span>
                <span class="meta-value" id="report-standard"></span>
            </div>
            <div class="meta-item">
                <span class="meta-label"><?php esc_html_e('Version:', 'kura-ai'); ?></span>
                <span class="meta-value" id="report-version"></span>
            </div>
            <div class="meta-item">
                <span class="meta-label"><?php esc_html_e('Generated:', 'kura-ai'); ?></span>
                <span class="meta-value" id="report-timestamp"></span>
            </div>
        </div>

        <div class="report-summary">
            <div class="summary-grid">
                <div class="summary-item total">
                    <span class="summary-value" id="summary-total">0</span>
                    <span class="summary-label"><?php esc_html_e('Total Requirements', 'kura-ai'); ?></span>
                </div>
                <div class="summary-item compliant">
                    <span class="summary-value" id="summary-compliant">0</span>
                    <span class="summary-label"><?php esc_html_e('Compliant', 'kura-ai'); ?></span>
                </div>
                <div class="summary-item partially">
                    <span class="summary-value" id="summary-partially">0</span>
                    <span class="summary-label"><?php esc_html_e('Partially Compliant', 'kura-ai'); ?></span>
                </div>
                <div class="summary-item non-compliant">
                    <span class="summary-value" id="summary-non-compliant">0</span>
                    <span class="summary-label"><?php esc_html_e('Non-Compliant', 'kura-ai'); ?></span>
                </div>
            </div>
            <div class="compliance-chart">
                <canvas id="compliance-chart"></canvas>
            </div>
        </div>

        <div class="report-requirements">
            <h2><?php esc_html_e('Detailed Requirements', 'kura-ai'); ?></h2>
            <div id="requirements-accordion" class="requirements-accordion"></div>
        </div>

        <div class="report-actions">
            <button type="button" id="export-pdf" class="button button-secondary">
                <i class="fas fa-file-pdf"></i>
                <?php esc_html_e('Export PDF', 'kura-ai'); ?>
            </button>
            <button type="button" id="export-csv" class="button button-secondary">
                <i class="fas fa-file-csv"></i>
                <?php esc_html_e('Export CSV', 'kura-ai'); ?>
            </button>
            <button type="button" id="schedule-scan" class="button button-secondary">
                <i class="fas fa-calendar-alt"></i>
                <?php esc_html_e('Schedule Scan', 'kura-ai'); ?>
            </button>
        </div>
    </div>

    <div id="compliance-loader" class="compliance-loader hidden">
        <div class="loader-content">
            <div class="spinner"></div>
            <p><?php esc_html_e('Generating compliance report...', 'kura-ai'); ?></p>
        </div>
    </div>

    <!-- Schedule Scan Modal -->
    <div id="schedule-modal" class="kura-ai-modal hidden">
        <div class="modal-content">
            <div class="modal-header">
                <h2><?php esc_html_e('Schedule Compliance Scan', 'kura-ai'); ?></h2>
                <button type="button" class="close-modal">&times;</button>
            </div>
            <div class="modal-body">
                <form id="schedule-form">
                    <div class="form-group">
                        <label for="scan-frequency"><?php esc_html_e('Scan Frequency:', 'kura-ai'); ?></label>
                        <select id="scan-frequency" name="frequency" required>
                            <option value="daily"><?php esc_html_e('Daily', 'kura-ai'); ?></option>
                            <option value="weekly"><?php esc_html_e('Weekly', 'kura-ai'); ?></option>
                            <option value="monthly"><?php esc_html_e('Monthly', 'kura-ai'); ?></option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="scan-time"><?php esc_html_e('Scan Time:', 'kura-ai'); ?></label>
                        <input type="time" id="scan-time" name="time" required>
                    </div>
                    <div class="form-group">
                        <label for="notification-email"><?php esc_html_e('Notification Email:', 'kura-ai'); ?></label>
                        <input type="email" id="notification-email" name="email" required>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="button button-primary">
                            <?php esc_html_e('Schedule Scan', 'kura-ai'); ?>
                        </button>
                        <button type="button" class="button button-secondary close-modal">
                            <?php esc_html_e('Cancel', 'kura-ai'); ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>