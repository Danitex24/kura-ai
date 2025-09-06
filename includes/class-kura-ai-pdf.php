<?php
namespace Kura_AI;

if (!defined('ABSPATH')) {
    exit;
}

require_once ABSPATH . 'wp-admin/includes/plugin.php';

// Import WordPress core files
require_once ABSPATH . 'wp-includes/pluggable.php';

// Import WordPress core classes
use \WP_Error;
use \Dompdf\Dompdf;
use \Exception;

// Import WordPress functions
use function \get_option;
use function \wp_upload_dir;
use function \sanitize_file_name;
use function \esc_html__;
use function \esc_html;
use function \__;
use function \wp_die;
use function \is_wp_error;
use function \sanitize_text_field;
use function \current_time;
use function \wp_remote_get;
use function \wp_remote_post;
use function \wp_remote_retrieve_body;
use function \wp_remote_retrieve_response_code;
use function \wp_json_encode;
use function \sprintf;

// Import WordPress constants
use const \ABSPATH;
use const \WP_DEBUG;

/**
 * Class for generating PDF reports
 *
 * @since      1.0.0
 * @package    Kura_AI
 * @subpackage Kura_AI/includes
 */
class Kura_AI_PDF {

    /**
     * Initialize the class
     *
     * @since    1.0.0
     */
    public function __construct() {
        if (!class_exists('\Dompdf\Dompdf')) {
            require_once plugin_dir_path(dirname(__FILE__)) . 'vendor/autoload.php';
        }
    }

    /**
     * Generate a compliance report PDF
     *
     * @since    1.0.0
     * @param    array    $report    The compliance report data
     * @return   string              The PDF file path or WP_Error on failure
     */
    public function generate_compliance_report($report) {
        try {
            $dompdf = new \Dompdf\Dompdf();
            $dompdf->setPaper('A4', 'portrait');

            $html = $this->get_report_html($report);
            $dompdf->loadHtml($html);
            $dompdf->render();

            $upload_dir = wp_upload_dir();
            $pdf_dir = $upload_dir['basedir'] . '/kura-ai/reports';
            wp_mkdir_p($pdf_dir);

            $filename = sanitize_file_name('compliance-report-' . current_time('Y-m-d-H-i-s') . '.pdf');
            $pdf_path = $pdf_dir . '/' . $filename;

            if (!file_put_contents($pdf_path, $dompdf->output())) {
                return new WP_Error('pdf_save_failed', __('Failed to save PDF file', 'kura-ai'));
            }

            return $pdf_path;
        } catch (Exception $e) {
            return new WP_Error('pdf_generation_failed', $e->getMessage());
        }
    }

    /**
     * Get HTML content for the report
     *
     * @since    1.0.0
     * @param    array    $report    The compliance report data
     * @return   string              The HTML content
     */
    private function get_report_html($report) {
        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
            <style>
                body { font-family: DejaVu Sans, sans-serif; line-height: 1.6; color: #333; }
                .header { text-align: center; margin-bottom: 30px; }
                .logo { max-width: 200px; margin-bottom: 20px; }
                h1 { color: #2271b1; font-size: 24px; margin-bottom: 20px; }
                .meta { margin-bottom: 30px; }
                .meta-item { margin-bottom: 10px; }
                .meta-label { font-weight: bold; }
                .summary { margin-bottom: 30px; }
                .summary-grid { display: table; width: 100%; margin-bottom: 20px; }
                .summary-item { display: table-row; }
                .summary-label, .summary-value { display: table-cell; padding: 8px; border-bottom: 1px solid #ddd; }
                .requirements { margin-bottom: 30px; }
                .requirement { margin-bottom: 15px; padding: 10px; border: 1px solid #ddd; }
                .requirement-header { margin-bottom: 10px; }
                .requirement-status { display: inline-block; padding: 4px 8px; border-radius: 3px; }
                .status-compliant { background: #d1e7dd; color: #0a3622; }
                .status-partially { background: #fff3cd; color: #664d03; }
                .status-non-compliant { background: #f8d7da; color: #842029; }
                .footer { text-align: center; margin-top: 50px; font-size: 12px; color: #666; }
            </style>
        </head>
        <body>
            <div class="header">
                <h1><?php echo esc_html__('Security Compliance Report', 'kura-ai'); ?></h1>
                <p><?php echo esc_html(sprintf(__('Generated on %s', 'kura-ai'), current_time('F j, Y, g:i a'))); ?></p>
            </div>

            <div class="meta">
                <div class="meta-item">
                    <span class="meta-label"><?php echo esc_html__('Standard:', 'kura-ai'); ?></span>
                    <?php echo esc_html($report['standard']); ?>
                </div>
                <div class="meta-item">
                    <span class="meta-label"><?php echo esc_html__('Website:', 'kura-ai'); ?></span>
                    <?php echo esc_html(get_bloginfo('name')); ?>
                </div>
                <div class="meta-item">
                    <span class="meta-label"><?php echo esc_html__('URL:', 'kura-ai'); ?></span>
                    <?php echo esc_html(get_site_url()); ?>
                </div>
            </div>

            <div class="summary">
                <h2><?php echo esc_html__('Summary', 'kura-ai'); ?></h2>
                <div class="summary-grid">
                    <div class="summary-item">
                        <div class="summary-label"><?php echo esc_html__('Total Requirements', 'kura-ai'); ?></div>
                        <div class="summary-value"><?php echo esc_html($report['total_requirements']); ?></div>
                    </div>
                    <div class="summary-item">
                        <div class="summary-label"><?php echo esc_html__('Compliant', 'kura-ai'); ?></div>
                        <div class="summary-value"><?php echo esc_html($report['compliant_count']); ?></div>
                    </div>
                    <div class="summary-item">
                        <div class="summary-label"><?php echo esc_html__('Partially Compliant', 'kura-ai'); ?></div>
                        <div class="summary-value"><?php echo esc_html($report['partially_count']); ?></div>
                    </div>
                    <div class="summary-item">
                        <div class="summary-label"><?php echo esc_html__('Non-Compliant', 'kura-ai'); ?></div>
                        <div class="summary-value"><?php echo esc_html($report['non_compliant_count']); ?></div>
                    </div>
                </div>
            </div>

            <div class="requirements">
                <h2><?php echo esc_html__('Detailed Requirements', 'kura-ai'); ?></h2>
                <?php foreach ($report['requirements'] as $requirement) : ?>
                    <div class="requirement">
                        <div class="requirement-header">
                            <h3><?php echo esc_html($requirement['name']); ?></h3>
                            <?php
                            $status_class = '';
                            $status_text = '';
                            switch ($requirement['status']) {
                                case 'compliant':
                                    $status_class = 'status-compliant';
                                    $status_text = __('Compliant', 'kura-ai');
                                    break;
                                case 'partially':
                                    $status_class = 'status-partially';
                                    $status_text = __('Partially Compliant', 'kura-ai');
                                    break;
                                case 'non-compliant':
                                    $status_class = 'status-non-compliant';
                                    $status_text = __('Non-Compliant', 'kura-ai');
                                    break;
                            }
                            ?>
                            <span class="requirement-status <?php echo esc_attr($status_class); ?>">
                                <?php echo esc_html($status_text); ?>
                            </span>
                        </div>
                        <p><?php echo esc_html($requirement['description']); ?></p>
                        <?php if (!empty($requirement['recommendation'])) : ?>
                            <p><strong><?php echo esc_html__('Recommendation:', 'kura-ai'); ?></strong></p>
                            <p><?php echo esc_html($requirement['recommendation']); ?></p>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="footer">
                <p><?php echo esc_html(sprintf(__('Generated by %s', 'kura-ai'), 'Kura AI Security')); ?></p>
            </div>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }
}