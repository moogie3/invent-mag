<div class="row g-3 mb-4">
    <div class="col-md-3">
        <label class="form-label fw-bold" for="invoice">{{ __('messages.invoice') }}</label>
        <input type="text" class="form-control @error('invoice') is-invalid @enderror" name="invoice" id="invoice" placeholder="{{ __('messages.invoice_number') }}" value="{{ old('invoice') }}" />
        @error('invoice')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-3">
        <label class="form-label fw-bold" for="customer_id">{{ __('messages.customer') }} <span class="text-danger">*</span></label>
        <select class="form-select @error('customer_id') is-invalid @enderror" name="customer_id" id="customer_id" required>
            <option value="">{{ __('messages.select_customer') }}</option>
            @foreach ($customers as $customer)
                <option value="{{ $customer->id }}" data-payment-terms="{{ $customer->payment_terms }}" {{ old('customer_id') == $customer->id ? 'selected' : '' }}>
                    {{ $customer->name }}
                </option>
            @endforeach
        </select>
        @error('customer_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-3">
        <label class="form-label fw-bold" for="order_date">{{ __('messages.order_date') }} <span class="text-danger">*</span></label>
        <input type="date" class="form-control @error('order_date') is-invalid @enderror" name="order_date" id="order_date" value="{{ old('order_date') }}" required />
        @error('order_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-3">
        <label class="form-label fw-bold">{{ __('messages.due_date') }} <span class="text-danger">*</span></label>
        <input type="text" class="form-control" name="due_date" id="due_date" placeholder="{{ __('messages.autofill') }}" readonly required />
    </div>
</div>
