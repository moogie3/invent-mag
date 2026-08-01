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
 * Highlights labels for form fields with validation errors
 */
function highlightErrorLabels() {
    document.querySelectorAll('.is-invalid').forEach(function(input) {
        var label = findLabelForInput(input);
        if (label) label.classList.add('text-danger');
    });
}

/**
 * Finds the associated label for a form input using multiple strategies
 */
function findLabelForInput(input) {
    // Strategy 1: label[for="inputId"]
    if (input.id) {
        var linked = document.querySelector('label[for="' + CSS.escape(input.id) + '"]');
        if (linked) return linked;
    }

    // Strategy 2: Search ancestor wrappers for a form-label
    var wrapper = input.closest('.mb-1, .mb-2, .mb-3, .mb-4, .mb-5, [class*="col-"], .form-group, .row, .input-group');
    if (wrapper) {
        var labels = wrapper.querySelectorAll('.form-label, .form-check-label');
        if (labels.length === 1) return labels[0];
        if (labels.length > 1) {
            // Multiple labels in same wrapper — find the nearest preceding one
            var best = null;
            labels.forEach(function(lbl) {
                if (lbl.compareDocumentPosition(input) & Node.DOCUMENT_POSITION_FOLLOWING) {
                    if (!best || best.compareDocumentPosition(lbl) & Node.DOCUMENT_POSITION_FOLLOWING) {
                        best = lbl;
                    }
                }
            });
            if (best) return best;
        }
    }

    // Strategy 3: Direct previous sibling
    var prev = input.previousElementSibling;
    if (prev && (prev.classList.contains('form-label') || prev.classList.contains('form-check-label'))) return prev;

    // Strategy 4: Check parent's children — find the last label before this input
    if (input.parentElement) {
        var parentLabels = input.parentElement.querySelectorAll('.form-label, .form-check-label');
        var lastBefore = null;
        parentLabels.forEach(function(lbl) {
            if (lbl.compareDocumentPosition(input) & Node.DOCUMENT_POSITION_FOLLOWING) {
                lastBefore = lbl;
            }
        });
        if (lastBefore) return lastBefore;
    }

    return null;
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

    // 4. Intercept JSON 422/419 responses before Turbo renders them
    // Turbo sends X-Requested-With: XMLHttpRequest, causing Laravel to return
    // JSON for validation errors. Turbo would try to render JSON as a page,
    // breaking the app and logging the user out, so we prevent that here.
    document.addEventListener("turbo:before-fetch-response", function(e) {
        const response = e.detail.fetchResponse.response;
        if (response && (response.status === 422 || response.status === 419 || response.status === 403)) {
            const ct = response.headers.get('Content-Type') || '';
            if (ct.includes('application/json')) {
                e.preventDefault();
            }
        }
    });

    // 5. Completion events
    document.addEventListener("turbo:load", hideLoader);
    document.addEventListener("turbo:render", hideLoader);
    document.addEventListener("turbo:frame-load", hideLoader);
    document.addEventListener("turbo:before-frame-render", hideLoader);
    document.addEventListener("turbo:fetch-request-error", hideLoader);
    document.addEventListener("turbo:submit-end", function(e) {
        hideLoader();
        if (!e.detail || !e.detail.fetchResponse) return;
        var response = e.detail.fetchResponse.response;
        if (!response || response.status !== 422) return;

        var ct = response.headers.get('Content-Type') || '';
        if (!ct.includes('application/json')) return;

        // Read JSON errors and apply them client-side
        response.clone().json().then(function(data) {
            var form = e.detail.formSubmission && e.detail.formSubmission.formElement;
            if (form && data.errors) {
                for (var field in data.errors) {
                    if (!data.errors.hasOwnProperty(field)) continue;
                    var input = form.querySelector('[name="' + CSS.escape(field) + '"]');
                    if (!input) continue;
                    input.classList.add('is-invalid');
                    var wrapper = input.closest('.mb-1, .mb-2, .mb-3, .mb-4, .mb-5, [class*="col-"], .input-group');
                    if (!wrapper) wrapper = input.parentElement;
                    var feedback = wrapper.querySelector('.invalid-feedback');
                    if (feedback) {
                        feedback.textContent = data.errors[field][0];
                        feedback.classList.add('d-block');
                    }
                }
                highlightErrorLabels();
            }
            if (window.InventMagApp && typeof window.InventMagApp.showToast === 'function') {
                window.InventMagApp.showToast('Error', data.message || 'Please check the form for validation errors.', 'error');
            } else if (typeof window.showToast === 'function') {
                window.showToast('Error', data.message || 'Please check the form for validation errors.', 'error');
            } else {
                alert(data.message || 'Please check the form for validation errors.');
            }
        }).catch(function(err) {
            console.error("Turbo JSON error parsing failed", err);
            if (window.InventMagApp && typeof window.InventMagApp.showToast === 'function') {
                window.InventMagApp.showToast('Error', 'Please check the form for validation errors.', 'error');
            } else {
                alert('Please check the form for validation errors.');
            }
        });
    });

    window.turboLoaderInitialized = true;
}

// The turboLoaderInitialized flag is intentionally NOT reset on turbo:before-cache.
// All Turbo/document event listeners must persist for the lifetime of the JS session
// and must only be registered once ever. Resetting causes listener accumulation.

onPageLoad(function() {
    'use strict';
    
    cleanupModals();
    highlightErrorLabels();
    
    // For initial load or navigation complete
    // We give it a tiny delay to ensure the user sees the transition
    // and that the page is fully rendered
    setTimeout(hideLoader, 200);
});
