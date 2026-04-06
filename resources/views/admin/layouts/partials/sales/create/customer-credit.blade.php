<div class="h-100 p-4 bg-body-secondary rounded-3" id="customer-credit-section" style="display: none;">
    <h4 class="card-title mb-4">
        <i class="ti ti-credit-card me-2 text-primary"></i> {{ __('messages.customer_credit') }}
    </h4>
    <div class="mb-3">
        <label class="form-label fw-bold">{{ __('messages.available_credits') }}</label>
        <div id="available-credits-display" class="fs-5 fw-bold text-success">
            {{ __('messages.select_customer_to_see_credits') }}
        </div>
    </div>
    <div class="mb-3">
        <label class="form-label fw-bold">{{ __('messages.use_credit') }}</label>
        <div class="input-group">
            <span class="input-group-text">
                <i class="ti ti-currency"></i>
            </span>
            <input type="number" min="0" step="0.01" class="form-control" id="customerCreditAmount" 
                name="customer_credit_amount" placeholder="0" value="0" />
            <button type="button" id="applyCustomerCredit" class="btn btn-secondary d-none d-sm-inline-block">
                <i class="ti ti-check"></i> {{ __('messages.apply') }}
            </button>
        </div>
        <small class="text-muted">{{ __('messages.use_credit_towards_order') }}</small>
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
    const applyButton = document.getElementById('applyCustomerCredit');
    
    if (!customerSelect || !creditSection) return;
    
    function fetchCustomerCredits(customerId) {
        if (!customerId) {
            creditSection.style.display = 'none';
            return;
        }
        
        fetch(`/admin/customers/${customerId}/credits`)
            .then(response => response.json())
            .then(data => {
                if (data.credits && data.credits.length > 0) {
                    creditSection.style.display = 'block';
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
                } else {
                    creditSection.style.display = 'none';
                }
            })
            .catch(error => {
                console.error('Error fetching credits:', error);
                creditSection.style.display = 'none';
            });
    }
    
    customerSelect.addEventListener('change', function() {
        fetchCustomerCredits(this.value);
    });
    
    if (applyButton) {
        applyButton.addEventListener('click', function() {
            const amount = parseFloat(creditInput.value) || 0;
            creditHiddenInput.value = amount;
            updateOrderTotals();
        });
    }
    
    creditInput.addEventListener('change', function() {
        const max = parseFloat(this.max) || 0;
        if (parseFloat(this.value) > max) {
            this.value = max;
        }
        // Trigger the SalesOrderCreate updateTotalPrice if it exists
        if (window.salesApp && typeof window.salesApp.updateTotalPrice === 'function') {
            window.salesApp.updateTotalPrice();
        }
    });
});
</script>
