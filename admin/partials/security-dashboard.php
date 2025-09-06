<?php
/**
 * Security Dashboard template.
 *
 * @package    Kura_AI
 * @subpackage Kura_AI/admin/partials
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Ensure WordPress functions are available
if (!function_exists('human_time_diff')) {
    require_once ABSPATH . 'wp-includes/formatting.php';
}
if (!function_exists('current_time')) {
    require_once ABSPATH . 'wp-includes/functions.php';
}
if (!function_exists('esc_html__')) {
    require_once ABSPATH . 'wp-includes/l10n.php';
}

// Initialize the monitor
$monitor = new \Kura_AI\Kura_AI_Monitor();
$metrics = $monitor->get_security_metrics();
$recent_events = $monitor->get_recent_events();
?>

<div class="wrap" id="security-dashboard-page">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <div class="dashboard-container">
        <!-- Metrics Overview -->
        <div class="metrics-grid">
            <!-- Failed Logins -->
            <div class="metric-card failed-logins">
                <div class="metric-icon">
                    <i class="fas fa-user-lock"></i>
                </div>
                <div class="metric-content">
                    <h3><?php _e('Failed Logins', 'kura-ai'); ?></h3>
                    <div class="metric-value"><?php echo esc_html($metrics['failed_logins']); ?></div>
                    <div class="metric-label"><?php _e('Last 24 hours', 'kura-ai'); ?></div>
                </div>
            </div>

            <!-- File Changes -->
            <div class="metric-card file-changes">
                <div class="metric-icon">
                    <i class="fas fa-file-alt"></i>
                </div>
                <div class="metric-content">
                    <h3><?php _e('File Changes', 'kura-ai'); ?></h3>
                    <div class="metric-value"><?php echo esc_html($metrics['file_changes']); ?></div>
                    <div class="metric-label"><?php _e('Last 24 hours', 'kura-ai'); ?></div>
                </div>
            </div>

            <!-- Malware Detections -->
            <div class="metric-card malware-detections">
                <div class="metric-icon">
                    <i class="fas fa-shield-virus"></i>
                </div>
                <div class="metric-content">
                    <h3><?php _e('Malware Detections', 'kura-ai'); ?></h3>
                    <div class="metric-value"><?php echo esc_html($metrics['malware_detections']); ?></div>
                    <div class="metric-label"><?php _e('Last 24 hours', 'kura-ai'); ?></div>
                </div>
            </div>

            <!-- User Activities -->
            <div class="metric-card user-activities">
                <div class="metric-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="metric-content">
                    <h3><?php _e('User Activities', 'kura-ai'); ?></h3>
                    <div class="metric-value"><?php echo esc_html($metrics['user_activities']); ?></div>
                    <div class="metric-label"><?php _e('Last 24 hours', 'kura-ai'); ?></div>
                </div>
            </div>

            <!-- System Alerts -->
            <div class="metric-card system-alerts">
                <div class="metric-icon">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div class="metric-content">
                    <h3><?php _e('System Alerts', 'kura-ai'); ?></h3>
                    <div class="metric-value"><?php echo esc_html($metrics['system_alerts']); ?></div>
                    <div class="metric-label"><?php _e('Last 24 hours', 'kura-ai'); ?></div>
                </div>
            </div>
        </div>

        <!-- Activity Timeline -->
        <div class="activity-timeline">
            <h2><?php _e('Recent Security Events', 'kura-ai'); ?></h2>
            
            <?php if (!empty($recent_events)) : ?>
                <div class="timeline">
                    <?php foreach ($recent_events as $event) : ?>
                        <div class="timeline-item">
                            <div class="timeline-icon">
                                <?php
                                $icon_class = 'info-circle';
                                switch ($event->event_type) {
                                    case 'failed_login':
                                        $icon_class = 'user-lock';
                                        break;
                                    case 'malware_detection':
                                        $icon_class = 'shield-virus';
                                        break;
                                    case 'file_change':
                                        $icon_class = 'file-alt';
                                        break;
                                    case 'user_activity':
                                        $icon_class = 'user';
                                        break;
                                    case 'system_alert':
                                        $icon_class = 'exclamation-triangle';
                                        break;
                                }
                                ?>
                                <i class="fas fa-<?php echo esc_attr($icon_class); ?>"></i>
                            </div>
                            <div class="timeline-content">
                                <h4><?php echo esc_html($event->message); ?></h4>
                                <div class="timeline-meta">
                                    <span class="event-type"><?php echo esc_html(ucfirst(str_replace('_', ' ', $event->event_type))); ?></span>
                                    <span class="event-time"><?php echo esc_html(human_time_diff(strtotime($event->created_at), current_time('timestamp'))); ?> ago</span>
                                </div>
                                <?php if (!empty($event->context)) : 
                                    $context = json_decode($event->context, true);
                                    if ($context) : ?>
                                        <div class="event-details">
                                            <?php foreach ($context as $key => $value) : ?>
                                                <div class="detail-item">
                                                    <span class="detail-label"><?php echo esc_html(ucfirst(str_replace('_', ' ', $key))); ?>:</span>
                                                    <span class="detail-value"><?php echo esc_html($value); ?></span>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; 
                                endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else : ?>
                <div class="no-events">
                    <p><?php _e('No recent security events.', 'kura-ai'); ?></p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Real-time Chart Container -->
        <div class="chart-container">
            <h2><?php _e('Security Metrics Trend', 'kura-ai'); ?></h2>
            <div class="chart-wrapper">
                <canvas id="security-metrics-chart"></canvas>
            </div>
        </div>
    </div>
</div>