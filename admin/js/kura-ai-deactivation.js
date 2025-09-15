/**
 * KuraAI Deactivation Script
 *
 * Handles the deactivation confirmation process using SweetAlert.
 *
 * @package    Kura_AI
 * @subpackage Kura_AI/admin/js
 * @author     Daniel Abughdyer <daniel@danovatesolutions.org>
 */

(function($) {
    'use strict';

    /**
     * KuraAI Deactivation class
     */
    class KuraAIDeactivation {
        /**
         * Initialize the deactivation handler
         */
        constructor() {
            this.deactivationLinks = [];
            this.pluginSlug = 'kura-ai/kura-ai.php';
            
            // Initialize when document is ready
            $(document).ready(() => {
                console.log('Document ready, initializing KuraAIDeactivation');
                this.init();
            });
        }

        /**
         * Initialize the deactivation handler
         */
        init() {
            // Use the plugin slug from localized data if available
            if (typeof kura_ai_deactivation !== 'undefined' && kura_ai_deactivation.plugin_slug) {
                this.pluginSlug = kura_ai_deactivation.plugin_slug;
            }
            
            console.log('Initializing deactivation handler for:', this.pluginSlug);
            
            // Find all deactivation links for our plugin
            $('a[href*="action=deactivate"]').each((index, link) => {
                const $link = $(link);
                const href = $link.attr('href');
                
                console.log('Found deactivation link:', href);

                if (href && href.indexOf(this.pluginSlug) !== -1) {
                    console.log('Found matching deactivation link for our plugin');
                    
                    // Store the original link
                    this.deactivationLinks.push({
                        element: $link,
                        href: href
                    });

                    // Replace the click event
                    $link.on('click', (e) => this.handleDeactivationClick(e, $link));
                }
            });
            
            // Alternative selector for finding the deactivation link
            if (this.deactivationLinks.length === 0) {
                console.log('Trying alternative selector');
                $('tr[data-plugin="' + this.pluginSlug + '"] .deactivate a').each((index, link) => {
                    const $link = $(link);
                    const href = $link.attr('href');
                    
                    console.log('Found alternative deactivation link:', href);
                    
                    // Store the original link
                    this.deactivationLinks.push({
                        element: $link,
                        href: href
                    });

                    // Replace the click event
                    $link.on('click', (e) => this.handleDeactivationClick(e, $link));
                });
            }
            
            console.log('Total deactivation links found:', this.deactivationLinks.length);
        }

        /**
         * Handle deactivation link click
         * @param {Event} e Click event
         * @param {jQuery} $link The clicked link
         */
        handleDeactivationClick(e, $link) {
            // Prevent default action
            e.preventDefault();
            console.log('Deactivation link clicked');

            // Get the href attribute
            const originalHref = $link.attr('href');
            console.log('Deactivation URL:', originalHref);
            
            // Make sure we have the localized data
            if (!kura_ai_deactivation) {
                console.error('Missing localized data');
                // Fallback to default values
                window.kura_ai_deactivation = {
                    title: 'Deactivate KuraAI?',
                    message: 'Do you want to reset all rules and clean up database tables created by KuraAI?',
                    confirm_text: 'Yes, delete all data',
                    cancel_text: 'No, keep my data',
                    ajax_url: ajaxurl || '/wp-admin/admin-ajax.php'
                };
            }
            
            // If SweetAlert is available, show confirmation dialog
            if (typeof Swal !== 'undefined') {
                console.log('Using SweetAlert for confirmation');
                Swal.fire({
                    title: kura_ai_deactivation.title || 'Deactivate Plugin?',
                    text: kura_ai_deactivation.message || 'Do you want to clean up data?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: kura_ai_deactivation.confirm_text || 'Yes',
                    cancelButtonText: kura_ai_deactivation.cancel_text || 'No'
                }).then((result) => {
                    console.log('SweetAlert result:', result);
                    if (result.isConfirmed) {
                        // User confirmed cleanup
                        console.log('User confirmed cleanup');
                        this.setCleanupFlag(true, originalHref);
                    } else {
                        // User wants to keep data
                        console.log('User declined cleanup');
                        this.setCleanupFlag(false, originalHref);
                    }
                }).catch(error => {
                    console.error('SweetAlert error:', error);
                    // Fallback to default behavior
                    window.location.href = originalHref;
                });
            } else {
                // Fallback if SweetAlert is not available
                console.log('SweetAlert not available, using confirm dialog');
                if (confirm(kura_ai_deactivation.message || 'Do you want to clean up data?')) {
                    console.log('User confirmed cleanup (native confirm)');
                    this.setCleanupFlag(true, originalHref);
                } else {
                    console.log('User declined cleanup (native confirm)');
                    this.setCleanupFlag(false, originalHref);
                }
            }
        }

        /**
         * Set the cleanup flag via AJAX
         * 
         * @param {boolean} cleanup Whether to clean up data
         * @param {string} redirectUrl URL to redirect to after setting the flag
         */
        setCleanupFlag(cleanup, redirectUrl) {
            console.log('Setting cleanup flag:', cleanup ? 1 : 0);
            
            // Show processing dialog if SweetAlert is available
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: kura_ai_deactivation.processing_title,
                    text: kura_ai_deactivation.processing_text,
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    allowEnterKey: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });
            }

            // Log the AJAX request details
            console.log('AJAX Request:', {
                url: kura_ai_deactivation.ajax_url,
                action: 'kura_ai_set_cleanup_flag',
                nonce: kura_ai_deactivation.nonce ? 'Valid nonce' : 'Missing nonce'
            });
            
            // Store if we're preserving data (not cleaning up)
            const isPreservingData = !cleanup;
            
            // Send AJAX request
            $.ajax({
                url: kura_ai_deactivation.ajax_url,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'kura_ai_set_cleanup_flag',
                    nonce: kura_ai_deactivation.nonce,
                    cleanup: cleanup ? 1 : 0
                },
                success: (response) => {
                    console.log('AJAX Response:', response);
                    // Always redirect if user chose to preserve data or if request was successful
                    if ((response && response.success) || isPreservingData) {
                        // For successful responses, show a brief message before redirecting
                        if (response && response.success && typeof Swal !== 'undefined') {
                            const message = cleanup ? 
                                'Data will be cleaned up on deactivation' : 
                                'Your data will be preserved';
                            
                            Swal.fire({
                                title: 'Success',
                                text: message,
                                icon: 'success',
                                timer: 2000,
                                showConfirmButton: false
                            }).then(() => {
                                window.location.href = redirectUrl;
                            });
                        } else {
                            // Redirect immediately
                            window.location.href = redirectUrl;
                        }
                    } else {
                        console.error('AJAX Error:', response);
                        // Show error
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                title: kura_ai_deactivation.error_title || 'Warning',
                                text: (response && response.data && response.data.message) || 'Could not save your preference, but deactivation will continue',
                                icon: 'warning',
                                confirmButtonText: kura_ai_deactivation.ok_text || 'Continue'
                            }).then(() => {
                                // Redirect anyway
                                window.location.href = redirectUrl;
                            });
                        } else {
                            alert((response && response.data && response.data.message) || kura_ai_deactivation.error_text);
                            window.location.href = redirectUrl;
                        }
                    }
                },
                error: (xhr, status, error) => {
                    console.error('AJAX Request Failed:', status, error);
                    console.log('Response Text:', xhr.responseText);
                    
                    // For data preservation, continue with deactivation even if AJAX fails
                    if (isPreservingData) {
                        window.location.href = redirectUrl;
                        return;
                    }
                    
                    // Show error
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            title: kura_ai_deactivation.error_title || 'Error',
                            text: kura_ai_deactivation.error_text || 'Failed to set cleanup preference. Continuing with deactivation.',
                            icon: 'error',
                            confirmButtonText: kura_ai_deactivation.ok_text || 'OK'
                        }).then(() => {
                            // Redirect anyway
                            window.location.href = redirectUrl;
                        });
                    } else {
                        alert(kura_ai_deactivation.error_text || 'Failed to set cleanup preference');
                        window.location.href = redirectUrl;
                    }
                }
            });
        }
    }

    // Initialize when document is ready
    $(document).ready(() => {
        new KuraAIDeactivation();
    });

})(jQuery);