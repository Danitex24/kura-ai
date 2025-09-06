<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap kura-ai-monitor">
    <h1><?php echo esc_html__('File Integrity Monitor', 'kura-ai'); ?></h1>

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
            $monitor = new Kura_AI_File_Monitor();
            $files = $monitor->get_monitored_files();
            
            if (empty($files)) :
            ?>
            <div class="kura-ai-no-files">
                <?php echo esc_html__('No files are currently being monitored.', 'kura-ai'); ?>
            </div>
            <?php
            else :
                foreach ($files as $file) :
                    $versions = $monitor->get_file_versions($file);
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
                    <?php echo esc_html__('Cancel', 'kura-ai'); ?>
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
</div>