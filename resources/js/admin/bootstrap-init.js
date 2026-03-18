// bootstrap-init.js
// Unified Bootstrap initialization - waits for Bootstrap and initializes everything
// For Tabler compatibility, we let Tabler handle modals natively and only initialize other components

(function() {
    'use strict';

    // This flag tracks whether we've done initial setup (not initialization)
    // We allow initBootstrap to run multiple times for Turbo compatibility
    if (!window.bsBootstrapSetupDone) {
        window.bsBootstrapSetupDone = true;
    }

    function getBootstrap() {
        // First check standard Bootstrap global
        if (window.bootstrap && window.bootstrap.Modal) {
            return window.bootstrap;
        }
        // Fallback to Tabler's bundled Bootstrap
        if (window.tabler && window.tabler.bootstrap && window.tabler.bootstrap.Modal) {
            return window.tabler.bootstrap;
        }
        return null;
    }

    function initBootstrap() {
        // Get Bootstrap from our helper function
        var bs = getBootstrap();
        
        if (bs) {
            // Ensure bootstrap is exposed globally for Tabler compatibility
            window.bootstrap = bs;
            
            // Only initialize dropdowns, tooltips, and popovers
            // Let Tabler handle modals natively - it has its own initialization
            var dropdowns = document.querySelectorAll('[data-bs-toggle="dropdown"]:not(.bs-init-done)');
            var tooltips = document.querySelectorAll('[data-bs-toggle="tooltip"]:not(.bs-init-done)');
            var popovers = document.querySelectorAll('[data-bs-toggle="popover"]:not(.bs-init-done)');

            if (dropdowns.length === 0 && tooltips.length === 0 && popovers.length === 0) {
                return;
            }
            
            // Initialize all dropdowns
            dropdowns.forEach(function(toggle) {
                if (!bs.Dropdown.getInstance(toggle)) {
                    try {
                        new bs.Dropdown(toggle);
                        toggle.classList.add('bs-init-done');
                    } catch(e) {}
                }
            });
            
            // Initialize all tooltips
            tooltips.forEach(function(toggle) {
                if (!bs.Tooltip.getInstance(toggle)) {
                    try {
                        new bs.Tooltip(toggle);
                        toggle.classList.add('bs-init-done');
                    } catch(e) {}
                }
            });

            // Initialize all popovers
            popovers.forEach(function(toggle) {
                if (!bs.Popover.getInstance(toggle)) {
                    try {
                        new bs.Popover(toggle);
                        toggle.classList.add('bs-init-done');
                    } catch(e) {}
                }
            });
        }
    }

    function cleanupModals() {
        if (window.shortcutManager && typeof window.shortcutManager.clearPageShortcuts === 'function') {
            window.shortcutManager.clearPageShortcuts();
        }

        // Get Bootstrap using our helper function
        var bs = getBootstrap();
        
        if (bs) {
            // Ensure bootstrap is exposed globally for Tabler compatibility
            window.bootstrap = bs;
            
            // First, hide any visible modals to clean up properly
            document.querySelectorAll('.modal.show').forEach(function(modalEl) {
                try {
                    var instance = bs.Modal.getInstance(modalEl);
                    if (instance) {
                        instance.hide();
                    }
                } catch(e) {}
            });
            
            // Small delay to allow hide animations, then dispose
            setTimeout(function() {
                // Dispose of all modal instances completely
                document.querySelectorAll('.modal').forEach(function(modalEl) {
                    if (!modalEl) return;
                    
                    // Get instance and dispose
                    const instance = bs.Modal.getInstance(modalEl);
                    if (instance) {
                        try {
                            instance.dispose();
                        } catch (e) {
                            console.warn('Error disposing modal instance:', e);
                        }
                    }
                    
                    // Also try to dispose using jQuery if available (Tabler compatibility)
                    if (window.jQuery && window.jQuery.fn.modal) {
                        try {
                            window.jQuery(modalEl).modal('dispose');
                        } catch (e) {}
                    }
                });

                // Force cleanup of ALL backdrops
                var backdrops = document.querySelectorAll('.modal-backdrop');
                backdrops.forEach(function(backdrop) {
                    backdrop.remove();
                });

                // Thoroughly clean body state
                document.body.classList.remove('modal-open', 'modal-scroll_disabled');
                document.body.style.overflow = '';
                document.body.style.paddingRight = '';
                
                // Clean any wrapper/window that might have aria-hidden
                document.querySelectorAll('[aria-hidden="true"]').forEach(function(el) {
                    if (el.closest('.wrapper') || el.classList.contains('wrapper') || el.tagName === 'BODY') {
                        el.removeAttribute('aria-hidden');
                    }
                });
                
                const wrapper = document.querySelector('.wrapper');
                if (wrapper) {
                    wrapper.removeAttribute('aria-hidden');
                }
            }, 50);
        }
    }

    // Run on initial load and every Turbo navigation
    document.addEventListener('turbo:load', initBootstrap);

    // Ensure bootstrap is always exposed globally on Turbo navigation
    document.addEventListener('turbo:load', function() {
        var bs = getBootstrap();
        if (bs) {
            window.bootstrap = bs;
        }
    });

    // Cleanup before caching and rendering
    document.addEventListener('turbo:before-cache', cleanupModals);
    document.addEventListener('turbo:before-render', cleanupModals);
    document.addEventListener('turbo:before-frame-render', cleanupModals);

    // If script is loaded after turbo:load
    if (document.readyState === 'complete' || document.readyState === 'interactive') {
        initBootstrap();
    }

})();
