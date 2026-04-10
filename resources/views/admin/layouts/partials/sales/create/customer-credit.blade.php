<div class="h-100 p-4 bg-body-secondary rounded-3" id="customer-credit-section">
    <h4 class="card-title mb-4">
        <i class="ti ti-credit-card me-2 text-primary"></i> {{ __('messages.customer_credit') }}
    </h4>
    <div class="mb-3">
        <label class="form-label fw-bold">{{ __('messages.available_credits') }}</label>
        <div id="available-credits-display" class="fs-5 fw-bold text-success">
            {{ __('messages.no_credit_available') }}
        </div>
    </div>
    <div class="mb-3">
        <label class="form-label fw-bold">{{ __('messages.use_credit') }}</label>
        <div class="input-group">
            <span class="input-group-text">
                <i class="ti ti-currency"></i>
            </span>
            <input type="number" min="0" step="1" class="form-control" id="customerCreditAmount" 
                name="customer_credit_amount" placeholder="0" value="0" disabled />
            <button type="button" id="applyCreditBtn" class="btn btn-secondary d-none">
                <i class="ti ti-check"></i>
            </button>
        </div>
        <small id="credit_warning" class="text-danger mt-1">
            <i class="ti ti-alert-triangle"></i> {{ __('messages.credit_exceeds_available') }}
        </small>
        <small class="text-muted d-block">{{ __('messages.use_credit_towards_order') }}</small>
    </div>
    <input type="hidden" id="customerCreditInput" name="customer_credit_amount" value="0">
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const customerSelect = document.getElementById('customer_id');
    const creditSection = document.getElementById('customer-credit-section');
    const creditDisplay = document.getElementById('available-credits-display');
    const creditInput = document.getElementById('customerCreditAmount');
    const creditHiddenInput = document.getElementById('customerCreditInput');
    
    if (!customerSelect || !creditSection) return;
    
    function fetchCustomerCredits(customerId) {
        if (!customerId) {
            creditDisplay.innerHTML = '{{ __('messages.select_customer_to_see_credits') }}';
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
                            creditHtml += ` ({{ __('messages.expire') }}: ${credit.expires_at})`;
                        }
                        creditHtml += `</div>`;
                    });
                    
                    creditDisplay.innerHTML = `
                        <div class="text-success fs-4">${totalAvailable.toLocaleString('id-ID', {style: 'currency', currency: 'IDR'})}</div>
                        <div class="text-muted small">${data.total_available.toLocaleString('id-ID', {style: 'currency', currency: 'IDR'})} {{ __('messages.total_available_credit') }}</div>
                        ${creditHtml}
                    `;
                    
                    creditInput.max = totalAvailable;
                    creditInput.disabled = false;
                    creditInput.value = 0;
                    creditHiddenInput.value = 0;
                } else {
                    creditDisplay.innerHTML = `
                        <div class="text-muted fs-5">{{ __('messages.no_credit_available') }}</div>
                        <div class="text-muted small">{{ __('messages.customer_has_no_credits') }}</div>
                    `;
                    creditInput.value = 0;
                    creditInput.disabled = true;
                    creditInput.max = 0;
                    creditHiddenInput.value = 0;
                }
            })
            .catch(error => {
                console.error('Error fetching credits:', error);
                creditDisplay.innerHTML = `
                    <div class="text-danger fs-5">{{ __('messages.error_loading_credits') }}</div>
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
});
</script>
