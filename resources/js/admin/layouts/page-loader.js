// page-loader.js
// Optimized Turbo handler - Instant, click-only, and robust cleanup
import { onPageLoad } from "../utils/onPageLoad.js";

let safetyTimeout;

/**
 * Shows the global page loader immediately
 */
function showLoader() {
    clearTimeout(safetyTimeout);
    
    const loader = document.getElementById('global-loader');
    if (loader) {
        // Ensure display:flex is set if it was hidden by previous hideLoader
        loader.style.display = 'flex';
        
        // Force a reflow for transition
        loader.offsetHeight;
        
        document.body.classList.add('is-loading-page');
        
        // Safety timeout: hide after 10 seconds to prevent getting stuck
        safetyTimeout = setTimeout(hideLoader, 10000);
    }
}

/**
 * Hides the global page loader with a smooth fade
 */
function hideLoader() {
    clearTimeout(safetyTimeout);
    
    const loader = document.getElementById('global-loader');
    if (loader) {
        document.body.classList.remove('is-loading-page');
        
        // After transition finishes, set display:none to completely remove from layout
        // matches transition: opacity 0.3s ease-in-out in CSS
        setTimeout(() => {
            if (!document.body.classList.contains('is-loading-page')) {
                loader.style.display = 'none';
            }
        }, 400);
    }
}

/**
 * Ensures Bootstrap modals and backdrops are cleaned up during navigation
 */
function cleanupModals() {
    if (window.bootstrap && window.bootstrap.Modal) {
        const modals = document.querySelectorAll('.modal');
        modals.forEach(modalEl => {
            try {
                const modalInstance = bootstrap.Modal.getInstance(modalEl);
                if (modalInstance) {
                    modalInstance.hide();
                }
            } catch (e) {}
            
            // Fallback cleanup for DOM elements to prevent dark screen
            modalEl.classList.remove('show');
            modalEl.style.display = 'none';
        });
    }

    const backdrops = document.querySelectorAll('.modal-backdrop');
    backdrops.forEach(backdrop => backdrop.remove());
    
    document.body.classList.remove('modal-open');
    document.body.style.overflow = '';
    document.body.style.paddingRight = '';
}

// Global Turbo Event Listeners (init once)
if (!window.turboLoaderInitialized) {
    if (window.Turbo && window.Turbo.config && window.Turbo.config.drive) {
        window.Turbo.config.drive.progressBarDelay = 0;
    }

    // 1. Navigation Start via Turbo Visit
    document.addEventListener("turbo:visit", function() {
        cleanupModals();
        showLoader();
    });

    // 2. Click handler for broad coverage (links, buttons that redirect)
    document.addEventListener("click", function(event) {
        const target = event.target.closest('a[href], button[onclick*="location.href"]');
        if (target) {
            // If it's a link to a different page (or same page but not hash)
            const href = target.getAttribute('href') || (target.getAttribute('onclick') ? target.getAttribute('onclick').match(/href=['"]([^'"]+)['"]/)?.[1] : null);
            
            if (href && !href.startsWith('#') && !href.includes('javascript:') && !target.hasAttribute('data-turbo-off') && target.target !== '_blank') {
                if (target.closest('.modal')) {
                    cleanupModals();
                }
                showLoader();
            }
        }
    });

    // 3. Form submissions (standard and Turbo)
    document.addEventListener("turbo:submit-start", showLoader);
    document.addEventListener("submit", function(event) {
        // If it's not handled by Turbo, we show the loader manually
        if (!event.defaultPrevented) {
            showLoader();
        }
    });

    // 4. Completion events
    document.addEventListener("turbo:load", hideLoader);
    document.addEventListener("turbo:render", hideLoader);
    document.addEventListener("turbo:frame-load", hideLoader);
    document.addEventListener("turbo:before-frame-render", hideLoader);
    document.addEventListener("turbo:fetch-request-error", hideLoader);
    document.addEventListener("turbo:submit-end", hideLoader);

    window.turboLoaderInitialized = true;
}

onPageLoad(function() {
    'use strict';
    
    cleanupModals();
    
    // For initial load or navigation complete
    // We give it a tiny delay to ensure the user sees the transition
    // and that the page is fully rendered
    setTimeout(hideLoader, 200);
});
