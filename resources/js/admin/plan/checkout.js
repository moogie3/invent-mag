// Checkout page initialization - standalone script
(function() {
    'use strict';

    // Prevent double initialization
    var initialized = false;

    function init() {
        if (initialized) return;
        initialized = true;

        initCountdown();
        initPayment();
    }

    // Listen for Turbo and DOM ready
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
                            
                            fetch('/api/payment/confirm', {
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
                                window.location.href = successUrl + '&confirmed=1';
                            })
                            .catch(function(err) {
                                console.error('Confirm error:', err);
                                window.location.href = successUrl + '&confirmed=1';
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
