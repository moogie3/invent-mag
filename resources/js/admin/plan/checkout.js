import { onPageLoad } from '../utils/onPageLoad.js';

let checkoutCountdownInterval = null;

onPageLoad(() => {
    initCountdown();
    initPayment();
});

function initCountdown() {
    const countdownEl = document.getElementById('countdown');
    if (!countdownEl || !countdownEl.dataset.expires) return;

    const expiresAt = new Date(countdownEl.dataset.expires);

    function update() {
        const diff = expiresAt - new Date();

        if (diff <= 0) {
            countdownEl.textContent = '00:00:00';
            countdownEl.classList.add('fw-bold');
            const payButton = document.getElementById('pay-button');
            if (payButton) {
                payButton.disabled = true;
                payButton.classList.replace('btn-primary', 'btn-secondary');
            }
            return;
        }

        const h = Math.floor(diff / 3600000);
        const m = Math.floor((diff % 3600000) / 60000);
        const s = Math.floor((diff % 60000) / 1000);

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
    const payButton = document.getElementById('pay-button');
    if (!payButton) return;

    const statusDiv = document.getElementById('payment-status');
    if (!statusDiv) return;
    
    const snapToken = payButton.dataset.snapToken;
    const orderId = payButton.dataset.orderId;
    const successUrl = payButton.dataset.successUrl;
    const originalHtml = payButton.innerHTML;

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

    loadMidtransScript().then(() => {
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
                        
                        const csrfToken = document.querySelector('meta[name="csrf-token"]');
                        const token = csrfToken ? csrfToken.content : '';
                        
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
                        .then(response => response.json())
                        .then(data => {
                            const changeType = data.type || 'upgrade';
                            window.location.href = successUrl + '?success=1&type=' + changeType;
                        })
                        .catch(err => {
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
    }).catch(err => {
        if (window.InventMagApp && window.InventMagApp.showToast) {
            window.InventMagApp.showToast('Error', 'Failed to load payment system. ' + err.message, 'error');
        } else {
            showStatus('Failed to load payment system. ' + err.message, 'error');
        }
    });
}

function loadMidtransScript() {
    return new Promise((resolve, reject) => {
        if (typeof snap !== 'undefined') {
            resolve();
            return;
        }

        const payButton = document.getElementById('pay-button');
        if (!payButton) {
            reject(new Error('Pay button not found'));
            return;
        }
        
        const isProduction = payButton.dataset.isProduction === 'true';
        const clientKey = payButton.dataset.clientKey;

        if (!clientKey) {
            reject(new Error('Client key not found'));
            return;
        }

        const script = document.createElement('script');
        script.src = isProduction 
            ? 'https://app.midtrans.com/snap/snap.js' 
            : 'https://app.sandbox.midtrans.com/snap/snap.js';
        script.setAttribute('data-client-key', clientKey);
        script.onload = () => resolve();
        script.onerror = () => reject(new Error('Failed to load Snap.js'));
        document.head.appendChild(script);
    });
}