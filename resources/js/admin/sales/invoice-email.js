// Send Invoice Email (sales view header, partial sales/view/header.blade.php).
//
// The POST is synchronous: without this the button sits dead while SMTP works,
// which reads as a broken control (antislop R-27: a data action needs a
// loading state, not only success and error). On submit the button disables
// itself, takes aria-busy, and swaps its mail icon for a spinner; it is
// restored on turbo:submit-end so a request that fails without navigating
// never strands the customer on a disabled button.
//
// Bound defensively: the script ships on every admin page (script.blade.php),
// so it must do nothing anywhere the form is absent, and must survive Turbo's
// page swaps by rebinding on turbo:load without double-binding a form it has
// already claimed.

(function () {
    function bind(root) {
        root.querySelectorAll('form[data-invoice-email-send]').forEach(function (form) {
            if (form.dataset.invoiceEmailBound === '1') {
                return;
            }
            form.dataset.invoiceEmailBound = '1';

            form.addEventListener('submit', function () {
                var button = form.querySelector('button[type="submit"]');
                if (!button || button.disabled) {
                    return;
                }
                var icon = button.querySelector('i');

                button.disabled = true;
                button.setAttribute('aria-busy', 'true');
                if (icon) {
                    icon.className = 'spinner-border spinner-border-sm me-1';
                    icon.setAttribute('aria-hidden', 'true');
                }

                form.addEventListener('turbo:submit-end', function restore() {
                    button.disabled = false;
                    button.removeAttribute('aria-busy');
                    if (icon) {
                        icon.className = 'ti ti-mail me-1';
                    }
                }, { once: true });
            });
        });
    }

    bind(document);
    document.addEventListener('turbo:load', function () {
        bind(document);
    });
})();
