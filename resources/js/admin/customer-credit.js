import { onPageLoad } from './utils/onPageLoad.js';

onPageLoad(function() {
    if (!document.getElementById('customer-credit-section')) return;
    
    function initCustomerCredit() {
        const customerSelect = document.getElementById('customer_id');
        const creditSection = document.getElementById('customer-credit-section');
        const creditDisplay = document.getElementById('available-credits-display');
        const creditInput = document.getElementById('customerCreditAmount');
        const creditHiddenInput = document.getElementById('customerCreditInput');
        
        if (!customerSelect || !creditSection || customerSelect.dataset.creditListenerAttached) return;
        customerSelect.dataset.creditListenerAttached = 'true';
        
        // Use translations from hidden configuration div if available
        const configEl = document.getElementById('customer-credit-config');
        const lang = configEl ? {
            select_customer_to_see_credits: configEl.getAttribute('data-select-customer-to-see-credits'),
            expire: configEl.getAttribute('data-expire'),
            total_available_credit: configEl.getAttribute('data-total-available-credit'),
            no_credit_available: configEl.getAttribute('data-no-credit-available'),
            customer_has_no_credits: configEl.getAttribute('data-customer-has-no-credits'),
            error_loading_credits: configEl.getAttribute('data-error-loading-credits')
        } : {};
        
        function fetchCustomerCredits(customerId) {
            if (!customerId) {
                creditDisplay.innerHTML = lang.select_customer_to_see_credits || 'Select a customer to view available credits';
                creditInput.value = 0;
                creditInput.disabled = true;
                creditInput.max = 0;
                creditHiddenInput.value = 0;
                return;
            }
            
            fetch(`/admin/customer/${customerId}/credits`)
                .then(response => response.json())
                .then(data => {
                    if (data.credits && data.credits.length > 0) {
                        let totalAvailable = 0;
                        let creditHtml = '';
                        
                        data.credits.forEach(credit => {
                            totalAvailable += credit.available_amount;
                            creditHtml += `<div class="small text-muted">`;
                            creditHtml += `#${credit.id} - ${credit.available_amount.toLocaleString('id-ID', {style: 'currency', currency: 'IDR'})}`;
                            if (credit.expires_at) {
                                const expireText = lang.expire || 'Expire';
                                creditHtml += ` (${expireText}: ${credit.expires_at})`;
                            }
                            creditHtml += `</div>`;
                        });
                        
                        const totalAvailableCreditText = lang.total_available_credit || 'Total Available Credit';
                        creditDisplay.innerHTML = `
                            <div class="text-success fs-4">${totalAvailable.toLocaleString('id-ID', {style: 'currency', currency: 'IDR'})}</div>
                            <div class="text-muted small">${data.total_available.toLocaleString('id-ID', {style: 'currency', currency: 'IDR'})} ${totalAvailableCreditText}</div>
                            ${creditHtml}
                        `;
                        
                        creditInput.max = totalAvailable;
                        creditInput.disabled = false;
                        creditInput.value = 0;
                        creditHiddenInput.value = 0;
                    } else {
                        const noCreditText = lang.no_credit_available || 'No Credit Available';
                        const noCreditDescText = lang.customer_has_no_credits || 'This customer has no available credit balances.';
                        creditDisplay.innerHTML = `
                            <div class="text-muted fs-5">${noCreditText}</div>
                            <div class="text-muted small">${noCreditDescText}</div>
                        `;
                        creditInput.value = 0;
                        creditInput.disabled = true;
                        creditInput.max = 0;
                        creditHiddenInput.value = 0;
                    }
                })
                .catch(error => {
                    console.error('Error fetching credits:', error);
                    const errorText = lang.error_loading_credits || 'Error loading credits';
                    creditDisplay.innerHTML = `
                        <div class="text-danger fs-5">${errorText}</div>
                    `;
                    creditInput.disabled = true;
                });
        }
        
        customerSelect.addEventListener('change', function() {
            fetchCustomerCredits(this.value);
        });
        
        creditInput.addEventListener('change', function() {
            const max = parseFloat(this.max) || 0;
            let value = parseFloat(this.value) || 0;
            
            if (value > max && max > 0) {
                value = max;
                this.value = max;
                this.classList.add("is-invalid");
                const warningEl = document.getElementById("credit_warning");
                if (warningEl) {
                    warningEl.style.display = "block";
                }
            } else {
                this.classList.remove("is-invalid");
                const warningEl = document.getElementById("credit_warning");
                if (warningEl) {
                    warningEl.style.display = "none";
                }
            }
            
            creditHiddenInput.value = value;
            
            if (window.salesApp && typeof window.salesApp.updateTotalPrice === 'function') {
                window.salesApp.updateTotalPrice();
            }
        });
        
        creditInput.addEventListener('input', function() {
            const max = parseFloat(this.max) || 0;
            let value = parseFloat(this.value) || 0;
            
            if (value > max && max > 0) {
                this.classList.add("is-invalid");
                const warningEl = document.getElementById("credit_warning");
                if (warningEl) {
                    warningEl.style.display = "block";
                }
            } else {
                this.classList.remove("is-invalid");
                const warningEl = document.getElementById("credit_warning");
                if (warningEl) {
                    warningEl.style.display = "none";
                }
            }
            
            creditHiddenInput.value = value;
            if (window.salesApp && typeof window.salesApp.updateTotalPrice === 'function') {
                window.salesApp.updateTotalPrice();
            }
        });
    }

    initCustomerCredit();
});
