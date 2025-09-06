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
        
        // Store original Swal.fire method
        const originalFire = Swal.fire;
        
        // Override Swal.fire to ensure proper dismissal behavior
        Swal.fire = function() {
            let options = {};
            
            // Handle different parameter formats
            if (arguments.length === 1 && typeof arguments[0] === 'object') {
                // Swal.fire({...})
                options = arguments[0];
            } else if (arguments.length >= 1 && typeof arguments[0] === 'string') {
                // Swal.fire('title', 'text', 'icon')
                options = {
                    title: arguments[0],
                    text: arguments[1] || '',
                    icon: arguments[2] || 'info'
                };
            }
            
            // Apply our default dismissal settings
            const enhancedOptions = Object.assign({
                allowOutsideClick: true,
                allowEscapeKey: true,
                allowEnterKey: true,
                focusConfirm: true,
                buttonsStyling: false,
                reverseButtons: false,
                showClass: {
                    popup: 'swal2-show',
                    backdrop: 'swal2-backdrop-show'
                },
                hideClass: {
                    popup: 'swal2-hide',
                    backdrop: 'swal2-backdrop-hide'
                }
            }, options);
            
            // Call original method and ensure proper promise handling
            const swalPromise = originalFire.call(this, enhancedOptions);
            
            // Add additional cleanup to ensure modal dismisses properly
            swalPromise.then((result) => {
                // Force cleanup if modal is still visible
                setTimeout(() => {
                    const container = document.querySelector('.swal2-container');
                    if (container && container.style.display !== 'none') {
                        container.style.display = 'none';
                        document.body.classList.remove('swal2-shown', 'swal2-height-auto');
                    }
                }, 100);
                return result;
            }).catch((error) => {
                // Ensure cleanup on error as well
                setTimeout(() => {
                    const container = document.querySelector('.swal2-container');
                    if (container && container.style.display !== 'none') {
                        container.style.display = 'none';
                        document.body.classList.remove('swal2-shown', 'swal2-height-auto');
                    }
                }, 100);
                throw error;
            });
            
            return swalPromise;
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
        
        // Add mutation observer to handle button clicks and ensure proper dismissal
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.type === 'childList') {
                    const swalContainer = document.querySelector('.swal2-container');
                    if (swalContainer && swalContainer.style.display !== 'none') {
                        // Add event listeners to SweetAlert buttons
                        const confirmBtn = swalContainer.querySelector('.swal2-confirm');
                        const cancelBtn = swalContainer.querySelector('.swal2-cancel');
                        const denyBtn = swalContainer.querySelector('.swal2-deny');
                        const closeBtn = swalContainer.querySelector('.swal2-close');
                        
                        // Ensure confirm button properly dismisses modal
                        if (confirmBtn && !confirmBtn.hasAttribute('data-kura-ai-handled')) {
                            confirmBtn.setAttribute('data-kura-ai-handled', 'true');
                            confirmBtn.addEventListener('click', function(e) {
                                setTimeout(() => {
                                    const container = document.querySelector('.swal2-container');
                                    if (container) {
                                        container.style.display = 'none';
                                        document.body.classList.remove('swal2-shown', 'swal2-height-auto');
                                    }
                                }, 50);
                            });
                        }
                        
                        // Ensure cancel button properly dismisses modal
                        if (cancelBtn && !cancelBtn.hasAttribute('data-kura-ai-handled')) {
                            cancelBtn.setAttribute('data-kura-ai-handled', 'true');
                            cancelBtn.addEventListener('click', function(e) {
                                setTimeout(() => {
                                    const container = document.querySelector('.swal2-container');
                                    if (container) {
                                        container.style.display = 'none';
                                        document.body.classList.remove('swal2-shown', 'swal2-height-auto');
                                    }
                                }, 50);
                            });
                        }
                        
                        // Ensure deny button properly dismisses modal
                        if (denyBtn && !denyBtn.hasAttribute('data-kura-ai-handled')) {
                            denyBtn.setAttribute('data-kura-ai-handled', 'true');
                            denyBtn.addEventListener('click', function(e) {
                                setTimeout(() => {
                                    const container = document.querySelector('.swal2-container');
                                    if (container) {
                                        container.style.display = 'none';
                                        document.body.classList.remove('swal2-shown', 'swal2-height-auto');
                                    }
                                }, 50);
                            });
                        }
                        
                        // Ensure close button properly dismisses modal
                        if (closeBtn && !closeBtn.hasAttribute('data-kura-ai-handled')) {
                            closeBtn.setAttribute('data-kura-ai-handled', 'true');
                            closeBtn.addEventListener('click', function(e) {
                                setTimeout(() => {
                                    const container = document.querySelector('.swal2-container');
                                    if (container) {
                                        container.style.display = 'none';
                                        document.body.classList.remove('swal2-shown', 'swal2-height-auto');
                                    }
                                }, 50);
                            });
                        }
                    }
                }
            });
        });
        
        // Start observing DOM changes
        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
        
        console.log('Kura AI: SweetAlert2 configuration initialized');
        
        // Global debug function for testing
        window.testKuraAISweetAlert = function() {
            console.log('Testing Kura AI SweetAlert dismissal...');
            Swal.fire({
                title: 'Test Alert',
                text: 'Try dismissing this alert by clicking OK, outside, pressing ESC, or using the close button.',
                icon: 'info',
                confirmButtonText: 'OK',
                showCancelButton: true,
                cancelButtonText: 'Cancel'
            }).then((result) => {
                console.log('SweetAlert closed with result:', result);
                // Force cleanup if modal is still visible
                setTimeout(() => {
                    const container = document.querySelector('.swal2-container');
                    if (container && container.style.display !== 'none') {
                        console.log('Force closing lingering modal...');
                        container.style.display = 'none';
                        document.body.classList.remove('swal2-shown', 'swal2-height-auto');
                    }
                }, 100);
            }).catch((error) => {
                console.log('SweetAlert error:', error);
                // Force cleanup on error
                const container = document.querySelector('.swal2-container');
                if (container) {
                    container.style.display = 'none';
                    document.body.classList.remove('swal2-shown', 'swal2-height-auto');
                }
            });
        };
        
        // Additional function to force close any lingering modals
        window.forceCloseSweetAlert = function() {
            console.log('Force closing all SweetAlert modals...');
            const containers = document.querySelectorAll('.swal2-container');
            containers.forEach(container => {
                container.style.display = 'none';
                container.style.visibility = 'hidden';
                container.style.opacity = '0';
            });
            document.body.classList.remove('swal2-shown', 'swal2-height-auto');
            document.body.style.overflow = 'auto';
            console.log('All SweetAlert modals force closed.');
        };
    }
    
    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initSweetAlertConfig);
    } else {
        initSweetAlertConfig();
    }
})();