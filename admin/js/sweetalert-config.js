/**
 * SweetAlert2 Global Configuration for Kura AI Plugin
 * Ensures consistent dismissal behavior across all SweetAlert instances
 */

(function() {
    'use strict';
    
    // Wait for SweetAlert2 to be loaded
    function initSweetAlertConfig() {
        if (typeof Swal === 'undefined') {
            setTimeout(initSweetAlertConfig, 100);
            return;
        }
        
        // Set global defaults for all SweetAlert instances
        Swal.mixin({
            // Enable dismissal options
            allowOutsideClick: true,
            allowEscapeKey: true,
            allowEnterKey: true,
            
            // Ensure proper focus management
            focusConfirm: true,
            focusDeny: false,
            focusCancel: false,
            
            // Prevent conflicts with other modals
            backdrop: true,
            
            // Custom classes for WordPress admin styling
            customClass: {
                container: 'kura-ai-swal-container',
                popup: 'kura-ai-swal-popup',
                header: 'kura-ai-swal-header',
                title: 'kura-ai-swal-title',
                closeButton: 'kura-ai-swal-close',
                icon: 'kura-ai-swal-icon',
                content: 'kura-ai-swal-content',
                htmlContainer: 'kura-ai-swal-html',
                input: 'kura-ai-swal-input',
                actions: 'kura-ai-swal-actions',
                confirmButton: 'kura-ai-swal-confirm button button-primary',
                denyButton: 'kura-ai-swal-deny button',
                cancelButton: 'kura-ai-swal-cancel button'
            },
            
            // Button text defaults
            confirmButtonText: 'OK',
            cancelButtonText: 'Cancel',
            
            // Animation settings
            showClass: {
                popup: 'swal2-show',
                backdrop: 'swal2-backdrop-show'
            },
            hideClass: {
                popup: 'swal2-hide',
                backdrop: 'swal2-backdrop-hide'
            }
        });
        
        // Override the original Swal.fire to ensure our defaults are always applied
        const originalFire = Swal.fire;
        Swal.fire = function(options) {
            // Merge with our defaults
            const defaultOptions = {
                allowOutsideClick: true,
                allowEscapeKey: true,
                allowEnterKey: true,
                focusConfirm: true
            };
            
            // Handle different parameter formats
            if (typeof options === 'string') {
                // Swal.fire('title', 'text', 'icon')
                const mergedOptions = Object.assign({}, defaultOptions, {
                    title: arguments[0],
                    text: arguments[1] || '',
                    icon: arguments[2] || 'info'
                });
                return originalFire.call(this, mergedOptions);
            } else if (typeof options === 'object' && options !== null) {
                // Swal.fire({...})
                const mergedOptions = Object.assign({}, defaultOptions, options);
                return originalFire.call(this, mergedOptions);
            } else {
                // Fallback to original behavior
                return originalFire.apply(this, arguments);
            }
        };
        
        // Add keyboard event listeners for better accessibility
        document.addEventListener('keydown', function(e) {
            // Check if a SweetAlert is currently open
            const swalContainer = document.querySelector('.swal2-container');
            if (swalContainer && swalContainer.style.display !== 'none') {
                // ESC key - close modal
                if (e.key === 'Escape' || e.keyCode === 27) {
                    Swal.close();
                }
                // Enter key - trigger confirm button if focused
                else if (e.key === 'Enter' || e.keyCode === 13) {
                    const confirmButton = document.querySelector('.swal2-confirm');
                    if (confirmButton && document.activeElement !== confirmButton) {
                        // Only auto-confirm if not already focused on confirm button
                        // This prevents double-triggering
                        const focusedElement = document.activeElement;
                        if (!focusedElement || !focusedElement.classList.contains('swal2-input')) {
                            confirmButton.click();
                        }
                    }
                }
            }
        });
        
        // Add click event listener for backdrop dismissal
        document.addEventListener('click', function(e) {
            if (e.target && e.target.classList.contains('swal2-container')) {
                Swal.close();
            }
        });
        
        console.log('Kura AI: SweetAlert2 configuration initialized');
        
        // Add a global test function for debugging (can be removed in production)
        window.testKuraAISweetAlert = function() {
            Swal.fire({
                title: 'SweetAlert Test',
                text: 'This is a test to verify dismissal functionality. Try clicking outside, pressing ESC, or using the close button.',
                icon: 'info',
                showCancelButton: true,
                confirmButtonText: 'Confirm',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                console.log('SweetAlert dismissed:', result);
            });
        };
    }
    
    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initSweetAlertConfig);
    } else {
        initSweetAlertConfig();
    }
})();