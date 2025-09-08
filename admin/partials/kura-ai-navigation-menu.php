<?php
/**
 * Navigation menu for Kura AI admin pages
 *
 * @link       https://kura.ai
 * @since      1.0.0
 *
 * @package    Kura_Ai
 * @subpackage Kura_Ai/admin/partials
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Get current page slug
$current_page = isset($_GET['page']) ? sanitize_text_field($_GET['page']) : 'kura-ai';

// Define menu items
$menu_items = array(
    'kura-ai' => array(
        'title' => __('Dashboard', 'kura-ai'),
        'icon' => 'dashicons-dashboard',
    ),
    'kura-ai-scanner' => array(
        'title' => __('Scanner', 'kura-ai'),
        'icon' => 'dashicons-shield',
    ),
    'kura-ai-malware-detection' => array(
        'title' => __('Malware Detection', 'kura-ai'),
        'icon' => 'dashicons-warning',
    ),
    'kura-ai-file-monitor' => array(
        'title' => __('File Monitor', 'kura-ai'),
        'icon' => 'dashicons-media-text',
    ),
    'kura-ai-compliance' => array(
        'title' => __('Compliance', 'kura-ai'),
        'icon' => 'dashicons-clipboard',
    ),
    'kura-ai-hardening' => array(
        'title' => __('Hardening', 'kura-ai'),
        'icon' => 'dashicons-shield-alt',
    ),
    'kura-ai-reports' => array(
        'title' => __('Reports', 'kura-ai'),
        'icon' => 'dashicons-chart-bar',
    ),
    'kura-ai-suggestions' => array(
        'title' => __('AI Suggestions', 'kura-ai'),
        'icon' => 'dashicons-lightbulb',
    ),
    'kura-ai-analysis' => array(
        'title' => __('AI Analysis', 'kura-ai'),
        'icon' => 'dashicons-analytics',
    ),
    'kura-ai-logs' => array(
        'title' => __('Logs', 'kura-ai'),
        'icon' => 'dashicons-list-view',
    ),
    'kura-ai-settings' => array(
        'title' => __('Settings', 'kura-ai'),
        'icon' => 'dashicons-admin-settings',
    ),
);

// Allow other plugins to modify the menu items
// No need to check if function exists as apply_filters is a WordPress core function
$menu_items = apply_filters('kura_ai_admin_menu_items', $menu_items);
?>

<div class="kura-ai-navigation-menu">
    <nav class="kura-ai-nav">
        <ul>
            <?php foreach ($menu_items as $slug => $item) : ?>
                <li class="<?php echo $current_page === $slug ? 'active' : ''; ?>">
                    <a href="<?php echo esc_url(admin_url('admin.php?page=' . $slug)); ?>">
                        <?php if (!empty($item['icon'])) : ?>
                            <span class="dashicons <?php echo esc_attr($item['icon']); ?>"></span>
                        <?php endif; ?>
                        <span class="nav-text"><?php echo esc_html($item['title']); ?></span>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </nav>
</div>