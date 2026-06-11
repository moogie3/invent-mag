<div class="mb-5 border-bottom pb-4">
    <h4 class="card-title mb-4">
        <i class="ti ti-file-description me-2 text-primary"></i> {{ __('messages.quotation_information_title') }}
    </h4>
    <div class="row">
        <div class="col-md-4 mb-3">
            <label class="form-label required">{{ __('messages.customer') }}</label>
            <select name="customer_id" class="form-select" required>
                <option value="">{{ __('messages.select_customer') }}</option>
                @foreach($customers as $customer)
                    <option value="{{ $customer->id }}" {{ (old('customer_id', $quotation->customer_id ?? '')) == $customer->id ? 'selected' : '' }}>
                        {{ $customer->name }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-md-4 mb-3">
            <label class="form-label required">{{ __('messages.quotation_date') }}</label>
            <input type="date" name="quotation_date" class="form-control"
                value="{{ old('quotation_date', isset($quotation) ? $quotation->quotation_date?->format('Y-m-d') : date('Y-m-d')) }}" required>
        </div>
        <div class="col-md-4 mb-3">
            <label class="form-label">{{ __('messages.valid_until') }}</label>
            <input type="date" name="valid_until" class="form-control"
                value="{{ old('valid_until', $quotation->valid_until?->format('Y-m-d') ?? '') }}">
        </div>
    </div>

    <input type="hidden" name="products" id="productsField">
</div>
