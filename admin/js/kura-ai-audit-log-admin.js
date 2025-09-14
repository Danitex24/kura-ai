/**
 * Admin JavaScript for the Kura AI Security Audit Log.
 *
 * Handles all the interactive functionality for the audit log page including:
 * - Filtering and settings toggle
 * - Log details modal
 * - Export functionality
 * - Clear logs functionality
 */
(function($) {
    'use strict';

    /**
     * Initialize all audit log functionality when the document is ready
     */
    $(document).ready(function() {
        // Initialize tooltips
        initTooltips();
        
        // Initialize settings toggle
        initSettingsToggle();
        
        // Initialize log details modal
        initLogDetailsModal();
        
        // Initialize export functionality
        initExportLogs();
        
        // Initialize clear logs functionality
        initClearLogs();
    });

    /**
     * Initialize tooltips
     */
    function initTooltips() {
        // Show tooltip on hover
        $('.kura-ai-tooltip').hover(
            function() {
                $(this).find('.kura-ai-tooltiptext').fadeIn(200);
            },
            function() {
                $(this).find('.kura-ai-tooltiptext').fadeOut(200);
            }
        );
    }

    /**
     * Initialize settings toggle functionality
     */
    function initSettingsToggle() {
        // Toggle settings panel
        $('#kura-ai-toggle-audit-settings').on('click', function() {
            $('.kura-ai-audit-settings').slideToggle(300);
            
            // Change button text based on visibility
            if ($('.kura-ai-audit-settings').is(':visible')) {
                $(this).html('<span class="dashicons dashicons-dismiss"></span> ' + 
                    kura_ai_audit_log.hide_filters_text);
            } else {
                $(this).html('<span class="dashicons dashicons-filter"></span> ' + 
                    kura_ai_audit_log.show_filters_text);
            }
        });
        
        // Show settings panel if there are active filters
        if (window.location.search.match(/[?&](event_type|action|severity|user_id|search|date_from|date_to)=/)) {
            $('.kura-ai-audit-settings').show();
            $('#kura-ai-toggle-audit-settings').html('<span class="dashicons dashicons-dismiss"></span> ' + 
                kura_ai_audit_log.hide_filters_text);
        }
    }

    /**
     * Initialize log details modal functionality
     */
    function initLogDetailsModal() {
        // Show log details in modal when view button is clicked
        $(document).on('click', '.kura-ai-view-details', function() {
            var logId = $(this).data('log-id');
            var detailsContent = $('#kura-ai-log-details-' + logId).html();
            
            $('#kura-ai-modal-content').html(detailsContent);
            $('#kura-ai-log-details-modal').fadeIn(300);
        });
        
        // Close modal when X is clicked
        $(document).on('click', '.kura-ai-modal-close', function() {
            $('#kura-ai-log-details-modal').fadeOut(300);
        });
        
        // Close modal when clicking outside of it
        $(document).on('click', function(event) {
            var modal = $('#kura-ai-log-details-modal');
            if (modal.is(':visible') && !$(event.target).closest('.kura-ai-modal-content, .kura-ai-view-details').length) {
                modal.fadeOut(300);
            }
        });
    }

    /**
     * Initialize export logs functionality
     */
    function initExportLogs() {
        $('.kura-ai-export-logs').on('click', function() {
            var format = $(this).data('format');
            
            // Confirm export
            if (confirm(kura_ai_audit_log.confirm_export)) {
                // Get current filter values
                var eventType = $('#event_type').val() || '';
                var action = $('#action').val() || '';
                var severity = $('#severity').val() || '';
                var userId = $('#user_id').val() || '';
                var dateFrom = $('#date_from').val() || '';
                var dateTo = $('#date_to').val() || '';
                var search = $('#search').val() || '';
                
                // Create form for POST submission
                var $form = $('<form>', {
                    'action': kura_ai_audit_log.ajax_url,
                    'method': 'post',
                    'target': '_blank'
                });
                
                // Add form fields
                $form.append($('<input>', {
                    'type': 'hidden',
                    'name': 'action',
                    'value': 'kura_ai_export_audit_logs'
                }));
                
                $form.append($('<input>', {
                    'type': 'hidden',
                    'name': 'nonce',
                    'value': kura_ai_audit_log.nonce
                }));
                
                $form.append($('<input>', {
                    'type': 'hidden',
                    'name': 'format',
                    'value': format
                }));
                
                // Add filter fields
                if (eventType) {
                    $form.append($('<input>', {
                        'type': 'hidden',
                        'name': 'event_type',
                        'value': eventType
                    }));
                }
                
                if (action) {
                    $form.append($('<input>', {
                        'type': 'hidden',
                        'name': 'action_filter',
                        'value': action
                    }));
                }
                
                if (severity) {
                    $form.append($('<input>', {
                        'type': 'hidden',
                        'name': 'severity',
                        'value': severity
                    }));
                }
                
                if (userId) {
                    $form.append($('<input>', {
                        'type': 'hidden',
                        'name': 'user_id',
                        'value': userId
                    }));
                }
                
                if (dateFrom) {
                    $form.append($('<input>', {
                        'type': 'hidden',
                        'name': 'date_from',
                        'value': dateFrom
                    }));
                }
                
                if (dateTo) {
                    $form.append($('<input>', {
                        'type': 'hidden',
                        'name': 'date_to',
                        'value': dateTo
                    }));
                }
                
                if (search) {
                    $form.append($('<input>', {
                        'type': 'hidden',
                        'name': 'search',
                        'value': search
                    }));
                }
                
                // Submit the form
                $('body').append($form);
                $form.submit();
                $form.remove();
            }
        });
    }

    /**
     * Initialize clear logs functionality
     */
    function initClearLogs() {
        $('.kura-ai-clear-logs').on('click', function() {
            // Confirm clear
            if (confirm(kura_ai_audit_log.confirm_clear)) {
                // Get current filter values
                var eventType = $('#event_type').val() || '';
                var severity = $('#severity').val() || '';
                var dateFrom = $('#date_from').val() || '';
                
                // Calculate older_than from date_from
                var olderThan = '';
                if (dateFrom) {
                    olderThan = dateFrom + ' 00:00:00';
                }
                
                // Send AJAX request
                $.ajax({
                    url: kura_ai_audit_log.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'kura_ai_clear_audit_logs',
                        nonce: kura_ai_audit_log.nonce,
                        event_type: eventType,
                        severity: severity,
                        older_than: olderThan
                    },
                    beforeSend: function() {
                        // Show loading indicator
                        $('.kura-ai-clear-logs').prop('disabled', true).html(
                            '<span class="dashicons dashicons-update kura-ai-spin"></span> ' + 
                            kura_ai_audit_log.clearing_text
                        );
                    },
                    success: function(response) {
                        if (response.success) {
                            // Show success message
                            alert(response.data.message);
                            
                            // Reload the page
                            window.location.reload();
                        } else {
                            // Show error message
                            alert(response.data.message || kura_ai_audit_log.error_text);
                            
                            // Reset button
                            $('.kura-ai-clear-logs').prop('disabled', false).html(
                                '<span class="dashicons dashicons-trash"></span> ' + 
                                kura_ai_audit_log.clear_logs_text
                            );
                        }
                    },
                    error: function() {
                        // Show error message
                        alert(kura_ai_audit_log.error_text);
                        
                        // Reset button
                        $('.kura-ai-clear-logs').prop('disabled', false).html(
                            '<span class="dashicons dashicons-trash"></span> ' + 
                            kura_ai_audit_log.clear_logs_text
                        );
                    }
                });
            }
        });
    }

})(jQuery);