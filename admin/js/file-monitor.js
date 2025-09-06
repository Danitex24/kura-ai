(function($) {
    'use strict';

    class KuraAIFileMonitor {
        constructor() {
            this.addFileForm = $('#add-file-form');
            this.filesGrid = $('.files-grid');
            this.compareModal = $('#version-compare-modal');
            this.bindEvents();
        }

        bindEvents() {
            this.addFileForm.on('submit', (e) => this.handleAddFile(e));
            this.filesGrid.on('click', '.create-version-btn', (e) => this.handleCreateVersion(e));
            this.filesGrid.on('click', '.view-versions-btn', (e) => this.handleToggleVersions(e));
            this.filesGrid.on('click', '.remove-file-btn', (e) => this.handleRemoveFile(e));
            this.filesGrid.on('click', '.compare-version-btn', (e) => this.handleCompareVersion(e));
            this.filesGrid.on('click', '.rollback-version-btn', (e) => this.handleRollback(e));
            this.compareModal.find('.close-modal').on('click', () => this.closeCompareModal());
            $('#version-from, #version-to').on('change', () => this.updateDiff());
        }

        handleAddFile(e) {
            e.preventDefault();

            const filePath = $('#file-path').val().trim();
            if (!filePath) {
                this.showNotice('error', kura_ai_ajax.empty_path_error);
                return;
            }

            $.ajax({
                url: kura_ai_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'kura_ai_add_monitored_file',
                    nonce: kura_ai_ajax.nonce,
                    file_path: filePath
                },
                success: (response) => {
                    if (response.success) {
                        location.reload(); // Refresh to show new file
                    } else {
                        this.showNotice('error', response.data.message || kura_ai_ajax.add_file_error);
                    }
                },
                error: () => {
                    this.showNotice('error', kura_ai_ajax.add_file_error);
                }
            });
        }

        handleCreateVersion(e) {
            const button = $(e.currentTarget);
            const filePath = button.data('path');

            this.showLoading(button);

            $.ajax({
                url: kura_ai_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'kura_ai_create_version',
                    nonce: kura_ai_ajax.nonce,
                    file_path: filePath
                },
                success: (response) => {
                    if (response.success) {
                        location.reload(); // Refresh to show new version
                    } else {
                        this.showNotice('error', response.data.message || kura_ai_ajax.create_version_error);
                    }
                },
                error: () => {
                    this.showNotice('error', kura_ai_ajax.create_version_error);
                },
                complete: () => {
                    this.hideLoading(button);
                }
            });
        }

        handleToggleVersions(e) {
            const button = $(e.currentTarget);
            const fileItem = button.closest('.file-item');
            const versionsPanel = fileItem.find('.versions-panel');

            versionsPanel.slideToggle();
            button.toggleClass('active');
        }

        handleRemoveFile(e) {
            const button = $(e.currentTarget);
            const filePath = button.data('path');

            Swal.fire({
                title: kura_ai_ajax.confirm_remove_title,
                text: kura_ai_ajax.confirm_remove_text,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: kura_ai_ajax.yes_remove,
                cancelButtonText: kura_ai_ajax.no_cancel,
                allowOutsideClick: true,
                allowEscapeKey: true
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: kura_ai_ajax.ajax_url,
                        type: 'POST',
                        data: {
                            action: 'kura_ai_remove_monitored_file',
                            nonce: kura_ai_ajax.nonce,
                            file_path: filePath
                        },
                        success: (response) => {
                            if (response.success) {
                                location.reload(); // Refresh to remove file
                            } else {
                                this.showNotice('error', response.data.message || kura_ai_ajax.remove_file_error);
                            }
                        },
                        error: () => {
                            this.showNotice('error', kura_ai_ajax.remove_file_error);
                        }
                    });
                }
            });
        }

        handleCompareVersion(e) {
            const button = $(e.currentTarget);
            const fileItem = button.closest('.file-item');
            const versions = fileItem.find('.version-item').map(function() {
                return {
                    id: $(this).data('version-id'),
                    date: $(this).find('.version-date').text()
                };
            }).get();

            this.populateVersionSelects(versions);
            this.compareModal.show();
            this.updateDiff();
        }

        handleRollback(e) {
            const button = $(e.currentTarget);
            const versionId = button.data('version-id');
            const filePath = button.closest('.file-item').find('.remove-file-btn').data('path');

            Swal.fire({
                title: kura_ai_ajax.confirm_rollback_title,
                text: kura_ai_ajax.confirm_rollback_text,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: kura_ai_ajax.yes_rollback,
                cancelButtonText: kura_ai_ajax.no_cancel,
                allowOutsideClick: true,
                allowEscapeKey: true
            }).then((result) => {
                if (result.isConfirmed) {
                    this.showLoading(button);

                    $.ajax({
                        url: kura_ai_ajax.ajax_url,
                        type: 'POST',
                        data: {
                            action: 'kura_ai_rollback_version',
                            nonce: kura_ai_ajax.nonce,
                            file_path: filePath,
                            version_id: versionId
                        },
                        success: (response) => {
                            if (response.success) {
                                location.reload(); // Refresh to show rollback
                            } else {
                                this.showNotice('error', response.data.message || kura_ai_ajax.rollback_error);
                            }
                        },
                        error: () => {
                            this.showNotice('error', kura_ai_ajax.rollback_error);
                        },
                        complete: () => {
                            this.hideLoading(button);
                        }
                    });
                }
            });
        }

        populateVersionSelects(versions) {
            const fromSelect = $('#version-from');
            const toSelect = $('#version-to');

            fromSelect.empty();
            toSelect.empty();

            versions.forEach((version, index) => {
                const option = $('<option></option>')
                    .val(version.id)
                    .text(version.date);

                fromSelect.append(option.clone());
                toSelect.append(option.clone());
            });

            // Default to comparing latest with previous version
            if (versions.length >= 2) {
                fromSelect.val(versions[1].id); // Second newest
                toSelect.val(versions[0].id); // Newest
            }
        }

        updateDiff() {
            const fromVersion = $('#version-from').val();
            const toVersion = $('#version-to').val();

            if (!fromVersion || !toVersion) return;

            const diffViewer = this.compareModal.find('.diff-viewer');
            diffViewer.find('.diff-loading').show();
            diffViewer.find('.diff-content').empty();

            $.ajax({
                url: kura_ai_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'kura_ai_compare_versions',
                    nonce: kura_ai_ajax.nonce,
                    version_id_1: fromVersion,
                    version_id_2: toVersion
                },
                success: (response) => {
                    if (response.success) {
                        diffViewer.find('.diff-content').html(response.data.diff_html);
                    } else {
                        this.showNotice('error', response.data.message || kura_ai_ajax.compare_error);
                    }
                },
                error: () => {
                    this.showNotice('error', kura_ai_ajax.compare_error);
                },
                complete: () => {
                    diffViewer.find('.diff-loading').hide();
                }
            });
        }

        closeCompareModal() {
            this.compareModal.hide();
        }

        showLoading(button) {
            const originalText = button.html();
            button.data('original-text', originalText)
                .prop('disabled', true)
                .html('<i class="fas fa-spinner fa-spin"></i>');
        }

        hideLoading(button) {
            const originalText = button.data('original-text');
            button.prop('disabled', false)
                .html(originalText);
        }

        showNotice(type, message) {
            const notice = $(`
                <div class="notice notice-${type} is-dismissible">
                    <p>${this.escapeHtml(message)}</p>
                </div>
            `);

            // Remove existing notices
            $('.notice').remove();

            // Add new notice before the first card
            $('.kura-ai-card').first().before(notice);

            // Make notice dismissible
            notice.find('.notice-dismiss').on('click', () => {
                notice.fadeOut(300, function() { $(this).remove(); });
            });
        }

        escapeHtml(unsafe) {
            return unsafe
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");
        }
    }

    // Initialize when document is ready
    $(document).ready(() => {
        new KuraAIFileMonitor();
    });

})(jQuery);