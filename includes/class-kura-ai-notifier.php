<?php
/**
 * The notification functionality of the plugin.
 *
 * @package    Kura_AI
 * @subpackage Kura_AI/includes
 * @author     Daniel Abughdyer <daniel@danovatesolutions.org>
 */
class Kura_AI_Notifier
{

    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $plugin_name    The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $version    The current version of this plugin.
     */
    private $version;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param    string    $plugin_name       The name of this plugin.
     * @param    string    $version    The version of this plugin.
     */
    public function __construct($plugin_name, $version)
    {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    /**
     * Send a notification email.
     *
     * @since    1.0.0
     * @param    string    $subject    The email subject
     * @param    string    $message    The email message
     * @param    string    $type       The notification type
     * @return   bool                  True if email was sent successfully
     */
    public function send_notification($subject, $message, $type = 'alert')
    {
        $settings = get_option('kura_ai_settings');

        // Check if notifications are enabled
        if (empty($settings['email_notifications'])) {
            return false;
        }

        $to = $settings['notification_email'] ?? get_option('admin_email');
        $headers = array('Content-Type: text/html; charset=UTF-8');

        // Add plugin info to footer
        $message .= $this->get_email_footer();

        return wp_mail($to, $subject, $message, $headers);
    }

    /**
     * Send scan results notification.
     *
     * @since    1.0.0
     * @param    array     $scan_results    The scan results
     * @return   bool                       True if email was sent successfully
     */
    public function send_scan_results($scan_results)
    {
        $settings = get_option('kura_ai_settings');

        // Count issues by severity
        $issue_counts = array(
            'critical' => 0,
            'high' => 0,
            'medium' => 0,
            'low' => 0
        );

        foreach ($scan_results as $category => $issues) {
            foreach ($issues as $issue) {
                $severity = $issue['severity'] ?? 'medium';
                $issue_counts[$severity]++;
            }
        }

        $total_issues = array_sum($issue_counts);

        if ($total_issues === 0) {
            $subject = __('[KuraAI] Security Scan: No issues found', 'kura-ai');
            $message = __('Your WordPress site security scan completed successfully with no issues detected.', 'kura-ai');
        } else {
            $subject = sprintf(__('[KuraAI] Security Scan: %d issues found', 'kura-ai'), $total_issues);

            $message = '<h2>' . __('Security Scan Results', 'kura-ai') . '</h2>';
            $message .= '<p>' . sprintf(__('Scan completed on %s with the following results:', 'kura-ai'), date_i18n(get_option('date_format') . ' ' . get_option('time_format'))) . '</p>';

            $message .= '<ul>';
            $message .= '<li>' . sprintf(__('Critical issues: %d', 'kura-ai'), $issue_counts['critical']) . '</li>';
            $message .= '<li>' . sprintf(__('High severity issues: %d', 'kura-ai'), $issue_counts['high']) . '</li>';
            $message .= '<li>' . sprintf(__('Medium severity issues: %d', 'kura-ai'), $issue_counts['medium']) . '</li>';
            $message .= '<li>' . sprintf(__('Low severity issues: %d', 'kura-ai'), $issue_counts['low']) . '</li>';
            $message .= '</ul>';

            $message .= '<p>' . __('Please log in to your WordPress dashboard to view detailed scan results and apply fixes.', 'kura-ai') . '</p>';
            $message .= '<p><a href="' . admin_url('admin.php?page=kura-ai-reports') . '">' . __('View Full Report', 'kura-ai') . '</a></p>';
        }

        return $this->send_notification($subject, $message, 'scan_results');
    }

    /**
     * Send critical alert notification.
     *
     * @since    1.0.0
     * @param    array     $issue    The security issue details
     * @return   bool                True if email was sent successfully
     */
    public function send_critical_alert($issue)
    {
        $subject = sprintf(__('[KuraAI] Critical Security Alert: %s', 'kura-ai'), $issue['type']);

        $message = '<h2>' . __('Critical Security Alert', 'kura-ai') . '</h2>';
        $message .= '<p>' . __('A critical security issue has been detected on your WordPress site:', 'kura-ai') . '</p>';

        $message .= '<ul>';
        $message .= '<li><strong>' . __('Issue Type', 'kura-ai') . ':</strong> ' . esc_html($issue['type']) . '</li>';
        $message .= '<li><strong>' . __('Severity', 'kura-ai') . ':</strong> ' . esc_html($issue['severity']) . '</li>';
        $message .= '<li><strong>' . __('Message', 'kura-ai') . ':</strong> ' . esc_html($issue['message']) . '</li>';

        if (!empty($issue['fix'])) {
            $message .= '<li><strong>' . __('Recommended Fix', 'kura-ai') . ':</strong> ' . esc_html($issue['fix']) . '</li>';
        }
        $message .= '</ul>';

        $message .= '<p>' . __('Immediate action is recommended to secure your website.', 'kura-ai') . '</p>';
        $message .= '<p><a href="' . admin_url('admin.php?page=kura-ai-suggestions') . '">' . __('View Details and Apply Fixes', 'kura-ai') . '</a></p>';

        return $this->send_notification($subject, $message, 'critical_alert');
    }

    /**
     * Get the standard email footer.
     *
     * @since    1.0.0
     * @return   string    The email footer HTML
     */
    private function get_email_footer()
    {
        $footer = '<hr>';
        $footer .= '<p><small>' . sprintf(
            __('This email was sent by the %s plugin on %s.', 'kura-ai'),
            '<a href="https://www.danovatesolutions.org/kura-ai">KuraAI</a>',
            get_bloginfo('name'),
        ) . '</small></p>';
        $footer .= '<p><small>' . __('To change your notification settings, visit the KuraAI settings page in your WordPress dashboard.', 'kura-ai') . '</small></p>';

        return $footer;
    }
}