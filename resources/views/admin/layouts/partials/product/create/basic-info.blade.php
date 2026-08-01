<div class="mb-2 pb-2">
    <h4 class="fw-semibold mb-3 text-secondary d-flex align-items-center">
        <i class="ti ti-info-circle me-2 text-muted"></i> {{ __('messages.basic_information') }}
    </h4>
    <div class="row g-3">
        <div class="col-md-3">
            <label class="form-label" for="code">{{ __('messages.product_code') }}</label>
            <input type="text" class="form-control @error('code') is-invalid @enderror" name="code" id="code" placeholder="{{ __('messages.enter_code') }}" value="{{ old('code') }}">
            @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-3">
            <label class="form-label" for="barcode">{{ __('messages.barcode') }}</label>
            <input type="text" class="form-control" name="barcode" id="barcode" placeholder="{{ __('messages.enter_barcode') }}" value="{{ old('barcode') }}">
        </div>
        <div class="col-md-6">
            <label class="form-label" for="name">{{ __('messages.product_name') }}</label>
            <input type="text" class="form-control @error('name') is-invalid @enderror" name="name" id="name" placeholder="{{ __('messages.enter_name') }}" value="{{ old('name') }}" required>
            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-12">
            <label class="form-label" for="description">{{ __('messages.description') }}</label>
            <textarea class="form-control @error('description') is-invalid @enderror" name="description" id="description" rows="3" placeholder="{{ __('messages.enter_product_description') }}">{{ old('description') }}</textarea>
            @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
</div>
