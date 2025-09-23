<div class="row g-3 mb-4">
    <div class="col-md-2">
        <label class="form-label fw-bold">{{ __('messages.invoice') }}</label>
        <input type="text" class="form-control" name="invoice" id="invoice" placeholder="{{ __('messages.invoice_number') }}" />
    </div>
    <div class="col-md-2">
        <label class="form-label fw-bold">{{ __('messages.customer') }}</label>
        <select class="form-select" name="customer_id" id="customer_id">
            <option value="">{{ __('messages.select_customer') }}</option>
            @foreach ($customers as $customer)
                <option value="{{ $customer->id }}" data-payment-terms="{{ $customer->payment_terms }}">
                    {{ $customer->name }}
                </option>
            @endforeach
        </select>
    </div>
    <div class="col-md-2">
        <label class="form-label fw-bold">{{ __('messages.order_date') }}</label>
        <input type="date" class="form-control" name="order_date" id="order_date" />
    </div>
    <div class="col-md-2">
        <label class="form-label fw-bold">{{ __('messages.due_date') }}</label>
        <input type="date" class="form-control bg-light" name="due_date" id="due_date" placeholder="{{ __('messages.autofill') }}"
            readonly />
    </div>
</div>
