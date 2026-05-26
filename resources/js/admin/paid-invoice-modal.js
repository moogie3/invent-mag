import { onPageLoad } from './utils/onPageLoad.js';

/**
 * Paid Invoice Modal
 *
 * Shared between Purchase Order (edit) and Sales Order (edit) pages.
 * When the server sets $isPaid = true, a #paidInvoiceModal element is
 * rendered in the page. This script detects it and:
 *   1. Shows the Bootstrap warning modal automatically.
 *   2. Makes all form inputs read-only so the paid record cannot be edited.
 *   3. Hides all submit buttons.
 *
 * No config div needed — the presence of #paidInvoiceModal IS the signal.
 */

function initPaidModal() {
    const modalEl = document.getElementById('paidInvoiceModal');
    if (!modalEl) return;

    // Wait for Bootstrap to be available (Turbo may evaluate this before
    // tabler.min.js has run its module initialisation).
    if (typeof window.bootstrap === 'undefined') {
        setTimeout(initPaidModal, 50);
        return;
    }

    // Add slight delay so the DOM is fully painted by Turbo before Bootstrap
    // tries to animate the modal.
    setTimeout(() => {
        const el = document.getElementById('paidInvoiceModal');
        if (!el) return;

        // Avoid double-showing if Turbo restores a cached page where the
        // modal was already open.
        if (!el.classList.contains('show')) {
            window.bootstrap.Modal.getOrCreateInstance(el).show();
        }

        // Make all form fields read-only so the paid invoice cannot be edited.
        document.querySelectorAll('input, select, textarea').forEach(field => {
            field.setAttribute('readonly', true);
            if (field.tagName === 'SELECT') {
                field.setAttribute('disabled', true);
            }
        });

        // Hide submit buttons — the form should not be submittable.
        document.querySelectorAll('button[type="submit"], input[type="submit"]').forEach(btn => {
            btn.style.display = 'none';
        });
    }, 100);
}

onPageLoad(function () {
    initPaidModal();
});
