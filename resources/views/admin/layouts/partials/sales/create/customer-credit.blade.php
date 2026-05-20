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


<div id="customer-credit-config" data-select-customer-to-see-credits="{{ __('messages.select_customer_to_see_credits') }}" data-expire="{{ __('messages.expire') }}" data-total-available-credit="{{ __('messages.total_available_credit') }}" data-no-credit-available="{{ __('messages.no_credit_available') }}" data-customer-has-no-credits="{{ __('messages.customer_has_no_credits') }}" data-error-loading-credits="{{ __('messages.error_loading_credits') }}" class="d-none"></div>
