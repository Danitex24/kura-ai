<?php
/**
 * Provide a admin area view for the brute force protection settings
 *
 * @package    Kura_AI
 * @subpackage Kura_AI/admin/partials
 * @author     Daniel Abughdyer <daniel@danovatesolutions.org>
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Make sure WordPress functions are available
if (!function_exists('wp_nonce_field') || !function_exists('esc_html__')) {
    require_once ABSPATH . 'wp-includes/pluggable.php';
}

// Make sure WordPress formatting functions are available
if (!function_exists('esc_attr__')) {
    require_once ABSPATH . 'wp-includes/formatting.php';
}

// Make sure WordPress date functions are available
if (!function_exists('date_i18n')) {
    require_once ABSPATH . 'wp-includes/functions.php';
}

// Make sure WordPress admin functions are available
if (!function_exists('check_admin_referer')) {
    require_once ABSPATH . 'wp-admin/includes/admin.php';
}

// Get current settings
$brute_force = new Kura_AI\Kura_AI_Brute_Force();
$settings = $brute_force->get_settings();
$lockouts = $brute_force->get_lockouts();
$attempts = $brute_force->get_failed_attempts();

// Process form submission
if (isset($_POST['kura_ai_save_brute_force_settings']) && check_admin_referer('kura_ai_brute_force_settings')) {
    $new_settings = [
        'allowed_attempts' => isset($_POST['allowed_attempts']) ? intval($_POST['allowed_attempts']) : 5,
        'lockout_duration' => isset($_POST['lockout_duration']) ? intval($_POST['lockout_duration']) : 30,
        'retry_time' => isset($_POST['retry_time']) ? intval($_POST['retry_time']) : 15,
    ];
    
    if ($brute_force->update_settings($new_settings)) {
        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Settings saved successfully.', 'kura-ai') . '</p></div>';
        $settings = $new_settings;
    } else {
        echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__('Failed to save settings.', 'kura-ai') . '</p></div>';
    }
}

// Process lockout reset
if (isset($_POST['kura_ai_reset_lockouts']) && check_admin_referer('kura_ai_reset_lockouts')) {
    if ($brute_force->reset_lockouts()) {
        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('All lockouts have been cleared.', 'kura-ai') . '</p></div>';
        $lockouts = [];
        $attempts = [];
    } else {
        echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__('Failed to clear lockouts.', 'kura-ai') . '</p></div>';
    }
}
?>

<div class="wrap kura-ai-wrap">
    <h1><?php echo esc_html__('Brute Force Protection', 'kura-ai'); ?></h1>
    
    <div class="kura-ai-card">
        <h2><?php echo esc_html__('Settings', 'kura-ai'); ?></h2>
        
        <form method="post" action="">
            <?php wp_nonce_field('kura_ai_brute_force_settings'); ?>
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="allowed_attempts"><?php echo esc_html__('Allowed Failed Attempts', 'kura-ai'); ?></label>
                    </th>
                    <td>
                        <input type="number" id="allowed_attempts" name="allowed_attempts" 
                               value="<?php echo esc_attr($settings['allowed_attempts']); ?>" min="1" max="20" required>
                        <p class="description">
                            <?php echo esc_html__('Number of failed login attempts allowed before lockout.', 'kura-ai'); ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="lockout_duration"><?php echo esc_html__('Lockout Duration', 'kura-ai'); ?></label>
                    </th>
                    <td>
                        <input type="number" id="lockout_duration" name="lockout_duration" 
                               value="<?php echo esc_attr($settings['lockout_duration']); ?>" min="1" max="1440" required>
                        <p class="description">
                            <?php echo esc_html__('Duration in minutes to lock out an IP after too many failed attempts.', 'kura-ai'); ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="retry_time"><?php echo esc_html__('Reset Time', 'kura-ai'); ?></label>
                    </th>
                    <td>
                        <input type="number" id="retry_time" name="retry_time" 
                               value="<?php echo esc_attr($settings['retry_time']); ?>" min="1" max="1440" required>
                        <p class="description">
                            <?php echo esc_html__('Time in minutes after which the failed login counter is reset.', 'kura-ai'); ?>
                        </p>
                    </td>
                </tr>
            </table>
            
            <p class="submit">
                <input type="submit" name="kura_ai_save_brute_force_settings" class="button button-primary" 
                       value="<?php echo esc_attr__('Save Settings', 'kura-ai'); ?>">
            </p>
        </form>
    </div>
    
    <div class="kura-ai-card">
        <h2><?php echo esc_html__('Current Lockouts', 'kura-ai'); ?></h2>
        
        <?php if (empty($lockouts)) : ?>
            <p><?php echo esc_html__('No IPs are currently locked out.', 'kura-ai'); ?></p>
        <?php else : ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php echo esc_html__('IP Address', 'kura-ai'); ?></th>
                        <th><?php echo esc_html__('Expires', 'kura-ai'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($lockouts as $ip => $expiration) : ?>
                        <tr>
                            <td><?php echo esc_html($ip); ?></td>
                            <td>
                                <?php 
                                $remaining = ceil(($expiration - time()) / 60);
                                echo sprintf(
                                    esc_html__('In %d minutes', 'kura-ai'),
                                    $remaining
                                );
                                ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
        
        <h2><?php echo esc_html__('Failed Login Attempts', 'kura-ai'); ?></h2>
        
        <?php if (empty($attempts)) : ?>
            <p><?php echo esc_html__('No failed login attempts recorded.', 'kura-ai'); ?></p>
        <?php else : ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php echo esc_html__('IP Address', 'kura-ai'); ?></th>
                        <th><?php echo esc_html__('Attempts', 'kura-ai'); ?></th>
                        <th><?php echo esc_html__('Last Attempt', 'kura-ai'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($attempts as $ip => $data) : ?>
                        <tr>
                            <td><?php echo esc_html($ip); ?></td>
                            <td><?php echo esc_html($data['attempts']); ?></td>
                            <td>
                                <?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $data['last_attempt'])); ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
        
        <form method="post" action="">
            <?php wp_nonce_field('kura_ai_reset_lockouts'); ?>
            <p class="submit">
                <input type="submit" name="kura_ai_reset_lockouts" class="button button-secondary" 
                       value="<?php echo esc_attr__('Reset All Lockouts', 'kura-ai'); ?>">
            </p>
        </form>
    </div>
</div>