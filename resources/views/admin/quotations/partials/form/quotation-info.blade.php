<div class="mb-5 border-bottom pb-4">
    <h4 class="card-title mb-4">
        <i class="ti ti-file-description me-2 text-primary"></i> {{ __('messages.quotation_information_title') }}
    </h4>
    <div class="row">
        <div class="col-md-4 mb-3">
            <label class="form-label required" for="customer_id">{{ __('messages.customer') }}</label>
            <select name="customer_id" id="customer_id" class="form-select @error('customer_id') is-invalid @enderror" required>
                <option value="">{{ __('messages.select_customer') }}</option>
                @foreach($customers as $customer)
                    <option value="{{ $customer->id }}" {{ (old('customer_id', $quotation->customer_id ?? '')) == $customer->id ? 'selected' : '' }}>
                        {{ $customer->name }}
                    </option>
                @endforeach
            </select>
            @error('customer_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-4 mb-3">
            <label class="form-label required" for="quotation_date">{{ __('messages.quotation_date') }}</label>
            <input type="date" name="quotation_date" id="quotation_date" class="form-control @error('quotation_date') is-invalid @enderror"
                value="{{ old('quotation_date', isset($quotation) ? $quotation->quotation_date?->format('Y-m-d') : date('Y-m-d')) }}" required>
            @error('quotation_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-4 mb-3">
            <label class="form-label" for="valid_until">{{ __('messages.valid_until') }}</label>
            <input type="date" name="valid_until" id="valid_until" class="form-control @error('valid_until') is-invalid @enderror"
                value="{{ old('valid_until', $quotation->valid_until?->format('Y-m-d') ?? '') }}">
            @error('valid_until')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>

    <input type="hidden" name="products" id="productsField">
</div>
