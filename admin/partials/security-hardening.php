<?php
/**
 * Provide a admin area view for the security hardening page
 *
 * @link       https://yourdomain.com
 * @since      1.0.0
 *
 * @package    Kura_AI
 * @subpackage Kura_AI/admin/partials
 */

// Initialize the hardening class
$hardening = new Kura_AI_Hardening();
$status = $hardening->get_security_status();
?>

<div class="wrap kura-ai-hardening">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <div class="kura-ai-card kura-ai-htaccess-section">
        <div class="card-header">
            <h2><i class="fas fa-shield-alt"></i> <?php _e('Apache Security Rules', 'kura-ai'); ?></h2>
        </div>
        <div class="card-body">
            <div class="status-grid">
                <div class="status-item <?php echo $status['htaccess']['exists'] ? 'success' : 'warning'; ?>">
                    <i class="fas fa-file-code"></i>
                    <span class="label"><?php _e('.htaccess File', 'kura-ai'); ?></span>
                    <span class="status">
                        <?php echo $status['htaccess']['exists'] ? 
                            __('Found', 'kura-ai') : 
                            __('Not Found', 'kura-ai'); ?>
                    </span>
                </div>
                <div class="status-item <?php echo $status['htaccess']['writable'] ? 'success' : 'warning'; ?>">
                    <i class="fas fa-edit"></i>
                    <span class="label"><?php _e('Write Permission', 'kura-ai'); ?></span>
                    <span class="status">
                        <?php echo $status['htaccess']['writable'] ? 
                            __('Writable', 'kura-ai') : 
                            __('Not Writable', 'kura-ai'); ?>
                    </span>
                </div>
                <div class="status-item <?php echo $status['htaccess']['has_security_rules'] ? 'success' : 'warning'; ?>">
                    <i class="fas fa-lock"></i>
                    <span class="label"><?php _e('Security Rules', 'kura-ai'); ?></span>
                    <span class="status">
                        <?php echo $status['htaccess']['has_security_rules'] ? 
                            __('Applied', 'kura-ai') : 
                            __('Not Applied', 'kura-ai'); ?>
                    </span>
                </div>
            </div>
            <div class="actions">
                <button type="button" id="apply-htaccess-rules" class="button button-primary">
                    <i class="fas fa-shield-alt"></i> <?php _e('Apply Security Rules', 'kura-ai'); ?>
                </button>
            </div>
        </div>
    </div>

    <div class="kura-ai-card kura-ai-database-section">
        <div class="card-header">
            <h2><i class="fas fa-database"></i> <?php _e('Database Optimization', 'kura-ai'); ?></h2>
        </div>
        <div class="card-body">
            <div class="status-grid">
                <div class="status-item <?php echo $status['database']['post_revisions'] > 0 ? 'warning' : 'success'; ?>">
                    <i class="fas fa-history"></i>
                    <span class="label"><?php _e('Post Revisions', 'kura-ai'); ?></span>
                    <span class="status"><?php echo $status['database']['post_revisions']; ?></span>
                </div>
                <div class="status-item <?php echo $status['database']['auto_drafts'] > 0 ? 'warning' : 'success'; ?>">
                    <i class="fas fa-file-alt"></i>
                    <span class="label"><?php _e('Auto Drafts', 'kura-ai'); ?></span>
                    <span class="status"><?php echo $status['database']['auto_drafts']; ?></span>
                </div>
                <div class="status-item <?php echo $status['database']['trashed_posts'] > 0 ? 'warning' : 'success'; ?>">
                    <i class="fas fa-trash-alt"></i>
                    <span class="label"><?php _e('Trashed Posts', 'kura-ai'); ?></span>
                    <span class="status"><?php echo $status['database']['trashed_posts']; ?></span>
                </div>
                <div class="status-item <?php echo $status['database']['spam_comments'] > 0 ? 'warning' : 'success'; ?>">
                    <i class="fas fa-comment-slash"></i>
                    <span class="label"><?php _e('Spam Comments', 'kura-ai'); ?></span>
                    <span class="status"><?php echo $status['database']['spam_comments']; ?></span>
                </div>
                <div class="status-item <?php echo $status['database']['trashed_comments'] > 0 ? 'warning' : 'success'; ?>">
                    <i class="fas fa-comments"></i>
                    <span class="label"><?php _e('Trashed Comments', 'kura-ai'); ?></span>
                    <span class="status"><?php echo $status['database']['trashed_comments']; ?></span>
                </div>
            </div>
            <div class="actions">
                <button type="button" id="optimize-database" class="button button-primary">
                    <i class="fas fa-broom"></i> <?php _e('Optimize Database', 'kura-ai'); ?>
                </button>
            </div>
        </div>
    </div>

    <div class="kura-ai-card kura-ai-recommendations-section">
        <div class="card-header">
            <h2><i class="fas fa-lightbulb"></i> <?php _e('Security Recommendations', 'kura-ai'); ?></h2>
        </div>
        <div class="card-body">
            <div class="recommendation-list">
                <?php
                $recommendations = array(
                    array(
                        'icon' => 'fas fa-key',
                        'title' => __('Strong Passwords', 'kura-ai'),
                        'description' => __('Enforce strong password policy for all users.', 'kura-ai'),
                        'link' => admin_url('options-general.php')
                    ),
                    array(
                        'icon' => 'fas fa-lock',
                        'title' => __('SSL Certificate', 'kura-ai'),
                        'description' => __('Enable HTTPS for secure communication.', 'kura-ai'),
                        'link' => admin_url('options-general.php')
                    ),
                    array(
                        'icon' => 'fas fa-user-shield',
                        'title' => __('Login Security', 'kura-ai'),
                        'description' => __('Implement login attempt limits and 2FA.', 'kura-ai'),
                        'link' => admin_url('options-general.php')
                    ),
                    array(
                        'icon' => 'fas fa-code-branch',
                        'title' => __('Keep Updated', 'kura-ai'),
                        'description' => __('Regularly update WordPress core, themes, and plugins.', 'kura-ai'),
                        'link' => admin_url('update-core.php')
                    )
                );

                foreach ($recommendations as $rec) :
                ?>
                <div class="recommendation-item">
                    <i class="<?php echo esc_attr($rec['icon']); ?>"></i>
                    <div class="content">
                        <h3><?php echo esc_html($rec['title']); ?></h3>
                        <p><?php echo esc_html($rec['description']); ?></p>
                    </div>
                    <a href="<?php echo esc_url($rec['link']); ?>" class="button button-secondary">
                        <?php _e('Configure', 'kura-ai'); ?>
                    </a>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>