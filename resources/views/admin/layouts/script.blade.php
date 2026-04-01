{{-- Load jQuery FIRST --}}
<script src="https://code.jquery.com/jquery-3.7.1.min.js"
    integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>

{{-- Load Tabler JS (remove defer since jQuery is already loaded) --}}
<script src="{{ asset('tabler/dist/js/tabler.min.js?1692870487') }}"></script>
<script src="{{ asset('tabler/dist/js/demo.min.js?1692870487') }}"></script>

<script>
    // CRITICAL: Tabler doesn't expose bootstrap globally, so we need to do it manually
    // This must run immediately after tabler.min.js loads
    (function() {
        var checkCount = 0;
        var maxChecks = 10;
        
        function exposeBootstrap() {
            checkCount++;
            
            // Check if Tabler is available
            if (window.tabler && window.tabler.bootstrap) {
                window.bootstrap = window.tabler.bootstrap;
                return true;
            }
            
            // If not found yet, try a few more times
            if (checkCount < maxChecks) {
                setTimeout(exposeBootstrap, 50);
            }
            return false;
        }
        
        // Start checking
        exposeBootstrap();
    })();
    
    // Also ensure bootstrap is available on every turbo:load
    document.addEventListener('turbo:load', function() {
        if (window.tabler && window.tabler.bootstrap && !window.bootstrap) {
            window.bootstrap = window.tabler.bootstrap;
        }
    });
</script>


{{-- Load other external libraries --}}
<script src="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/list.js/2.3.1/list.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns/dist/chartjs-adapter-date-fns.bundle.min.js"></script>

{{-- Global Notification Helper --}}
@vite(['resources/js/admin/utils/sound.js', 'resources/js/admin/helpers/notification.js'])

{{-- Global Keyboard Shortcuts - MUST load FIRST before other scripts use it --}}
@vite('resources/js/admin/layouts/global-keyboard-shortcuts.js')

{{--
    NOTE: All page-specific scripts are loaded unconditionally so that
    Turbo Drive navigation (which reuses the same JS bundle from the initial
    page load) can re-initialize the correct JS on every page visit.
    Each script guards itself via window.location.pathname checks.
--}}

{{-- SCRIPT FOR ADMIN POS --}}
@vite('resources/js/admin/pos.js')

{{-- SCRIPT FOR RECENT TRANSACTIONS --}}
@vite('resources/js/admin/recentts.js')

{{-- SCRIPT FOR USER MANAGEMENT --}}
@vite('resources/js/admin/user.js')

{{-- SCRIPT FOR PROFILE --}}
@vite('resources/js/admin/profile.js')

{{-- SCRIPT FOR ADMIN SALES CREATE & EDIT --}}
@vite('resources/js/admin/sales-order.js')

{{-- SCRIPT FOR ADMIN SALES RETURN --}}
@vite('resources/js/admin/sales-return.js')

{{-- SCRIPT FOR ADMIN PO --}}
@vite('resources/js/admin/purchase-order.js')

{{-- SCRIPT FOR ADMIN PURCHASE RETURN --}}
@vite('resources/js/admin/purchase-return.js')

{{-- SCRIPT FOR PRODUCT --}}
@vite('resources/js/admin/product.js')

{{-- SCRIPT FOR WAREHOUSE --}}
@vite('resources/js/admin/warehouse.js')

{{-- SCRIPT FOR UNIT --}}
@vite('resources/js/admin/unit.js')

{{-- SCRIPT FOR CATEGORY --}}
@vite('resources/js/admin/category.js')

{{-- SCRIPT FOR SUPPLIER --}}
@vite('resources/js/admin/supplier.js')

{{-- SCRIPT FOR CUSTOMER --}}
@vite('resources/js/admin/customer.js')

{{-- SCRIPT FOR SORTING TABLE --}}
@vite('resources/js/admin/sorting.js')

{{-- SCRIPT FOR CURRENCY settings --}}
@vite('resources/js/admin/currency.js')

{{-- GLOBAL FALLBACK FUNCTIONS FOR JOURNAL ENTRIES - Load before journal-entry.js --}}
<script>
// These fallbacks ensure functions exist before journal-entry.js loads
// They will be overwritten by the actual functions when the page has the journal form
(function() {
    var funcs = [
        'updateTotals', 'validateAndSubmit', 'handleAccountChange', 
        'handleInput', 'addTransactionRow', 'removeTransactionRow',
        'searchAccounts', 'confirmAction', 'reverseEntry', 'voidEntry'
    ];
    funcs.forEach(function(name) {
        if (typeof window[name] !== 'function') {
            window[name] = function() {};
        }
    });
})();
</script>

{{-- SCRIPT FOR ACCOUNTING settings --}}
@vite('resources/js/admin/accounting.js')

{{-- SCRIPT FOR JOURNAL ENTRIES --}}
@vite('resources/js/admin/journal-entry.js')

{{-- SCRIPT FOR OPENING BALANCE --}}
@vite('resources/js/admin/opening-balance.js')

{{-- SCRIPT FOR ADJUSTMENT LOG --}}
@vite('resources/js/admin/adjustment-log.js')

{{-- SCRIPT FOR AUDIT LOG --}}
@vite('resources/js/admin/audit-log.js')

{{-- SCRIPT FOR STOCK TRANSFER --}}
@vite('resources/js/admin/reports/stock-transfer.js')

{{-- SCRIPT FOR TAX settings --}}
@vite('resources/js/admin/tax.js')

{{-- SCRIPT FOR SALES PIPELINE (SortableJS only loaded when needed) --}}
<script>
    // Lazily load SortableJS only when navigating to the sales-pipeline page
    document.addEventListener('turbo:load', function () {
        if (window.location.pathname.includes('/admin/sales-pipeline')) {
            if (typeof Sortable === 'undefined') {
                var s = document.createElement('script');
                s.src = 'https://cdn.jsdelivr.net/npm/sortablejs@1.14.0/Sortable.min.js';
                document.head.appendChild(s);
            }
        }
    });
</script>
@vite('resources/js/admin/sales-pipeline.js')

{{-- SCRIPT FOR ADMIN LOGIN & PASSWORD RESET --}}
@vite('resources/js/admin/auth.js')
@vite('resources/js/admin/reset-password.js')

{{-- SCRIPT FOR SETTINGS --}}
@vite('resources/js/admin/layouts/settings.js')
@vite('resources/js/admin/layouts/advanced-settings.js')
@vite('resources/js/admin/layouts/selectable-table.js')

{{-- SCRIPT FOR NAVBAR --}}
@vite('resources/js/admin/layouts/navbar.js')

{{-- BOOTSTRAP RE-INITIALIZATION FOR TURBO --}}
@vite('resources/js/admin/bootstrap-init.js')

{{-- SCRIPT FOR PLAN CHECKOUT --}}
@if(request()->is('*/plan/checkout/*'))
<meta name="turbo-cache-control" content="no-cache">
<script>
(function() {
    'use strict';

    var initialized = false;

    function init() {
        if (initialized) return;
        initialized = true;

        initCountdown();
        initPayment();
    }

    document.addEventListener('turbo:load', init);
    if (document.readyState === 'complete' || document.readyState === 'interactive') {
        init();
    }

    var checkoutCountdownInterval = null;

    function initCountdown() {
        var countdownEl = document.getElementById('countdown');
        if (!countdownEl || !countdownEl.dataset.expires) return;

        var expiresAt = new Date(countdownEl.dataset.expires);

        function update() {
            var diff = expiresAt - new Date();

            if (diff <= 0) {
                countdownEl.textContent = '00:00:00';
                countdownEl.classList.add('fw-bold');
                var payButton = document.getElementById('pay-button');
                if (payButton) {
                    payButton.disabled = true;
                    payButton.classList.replace('btn-primary', 'btn-secondary');
                }
                return;
            }

            var h = Math.floor(diff / 3600000);
            var m = Math.floor((diff % 3600000) / 60000);
            var s = Math.floor((diff % 60000) / 1000);

            countdownEl.textContent =
                String(h).padStart(2, '0') + ':' +
                String(m).padStart(2, '0') + ':' +
                String(s).padStart(2, '0');
        }

        update();
        if (checkoutCountdownInterval) clearInterval(checkoutCountdownInterval);
        checkoutCountdownInterval = setInterval(update, 1000);
    }

    function initPayment() {
        var payButton = document.getElementById('pay-button');
        if (!payButton) return;

        var statusDiv = document.getElementById('payment-status');
        if (!statusDiv) return;
        
        var snapToken = payButton.dataset.snapToken;
        var orderId = payButton.dataset.orderId;
        var successUrl = payButton.dataset.successUrl;
        var originalHtml = payButton.innerHTML;

        function showStatus(message, type) {
            statusDiv.className = 'alert mt-3';
            statusDiv.classList.add(
                type === 'error' ? 'alert-danger' :
                type === 'success' ? 'alert-success' : 'alert-info'
            );
            statusDiv.textContent = message;
            statusDiv.classList.remove('d-none');
        }

        function resetButton() {
            payButton.disabled = false;
            payButton.innerHTML = originalHtml;
        }

        loadMidtransScript().then(function() {
            if (typeof snap === 'undefined') {
                showStatus('Snap.js failed to load. Please check your Client Key configuration.', 'error');
                return;
            }

            payButton.addEventListener('click', function(e) {
                e.preventDefault();

                showStatus('Opening payment window...', 'info');
                payButton.disabled = true;
                payButton.innerHTML = '<span class="spinner-border spinner-border-sm me-2" style="width: 1rem; height: 1rem;" role="status" aria-hidden="true"></span>Loading...';

                try {
                    snap.pay(snapToken, {
                        onSuccess: function(result) {
                            showStatus('Payment successful! Confirming...', 'success');
                            
                            var csrfToken = document.querySelector('meta[name="csrf-token"]');
                            var token = csrfToken ? csrfToken.content : '';
                            
                            fetch('/admin/settings/plan/confirm-payment', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': token
                                },
                                body: JSON.stringify({
                                    order_id: orderId,
                                    transaction_result: result
                                })
                            })
                            .then(function(response) { return response.json(); })
                            .then(function(data) {
                                window.location.href = successUrl + '?success=1';
                            })
                            .catch(function(err) {
                                console.error('Confirm error:', err);
                                window.location.href = successUrl + '?success=1';
                            });
                        },
                        onPending: function(result) {
                            showStatus('Payment pending. Please complete payment.', 'info');
                            resetButton();
                        },
                        onError: function(result) {
                            showStatus('Payment failed: ' + JSON.stringify(result), 'error');
                            resetButton();
                        },
                        onClose: function() {
                            showStatus('Payment window closed. You can try again.', 'info');
                            resetButton();
                        }
                    });
                } catch (err) {
                    showStatus('Error: ' + err.message, 'error');
                    resetButton();
                }
            });
        }).catch(function(err) {
            showStatus('Failed to load payment system. ' + err.message, 'error');
        });
    }

    function loadMidtransScript() {
        return new Promise(function(resolve, reject) {
            if (typeof snap !== 'undefined') {
                resolve();
                return;
            }

            var payButton = document.getElementById('pay-button');
            if (!payButton) {
                reject(new Error('Pay button not found'));
                return;
            }
            
            var isProduction = payButton.dataset.isProduction === 'true';
            var clientKey = payButton.dataset.clientKey;

            if (!clientKey) {
                reject(new Error('Client key not found'));
                return;
            }

            var script = document.createElement('script');
            script.src = isProduction 
                ? 'https://app.midtrans.com/snap/snap.js' 
                : 'https://app.sandbox.midtrans.com/snap/snap.js';
            script.setAttribute('data-client-key', clientKey);
            script.onload = function() { resolve(); };
            script.onerror = function() { reject(new Error('Failed to load Snap.js')); };
            document.head.appendChild(script);
        });
    }
})();
</script>
@else
@vite('resources/js/admin/plan/checkout.js')
@endif

{{-- SCRIPT FOR ADMIN DASHBOARD (ApexCharts lazy-loaded) --}}
<script>
    document.addEventListener('turbo:load', function () {
        if (window.location.pathname.includes('/admin/dashboard') && typeof ApexCharts === 'undefined') {
            var s = document.createElement('script');
            s.src = '{{ asset('tabler/dist/libs/apexcharts/dist/apexcharts.min.js') }}';
            document.head.appendChild(s);
        }
    });
</script>

{{-- DELETE MODAL --}}
<script>
    function setDeleteFormAction(action) {
        document.getElementById('deleteForm').setAttribute('action', action);
    }
</script>
