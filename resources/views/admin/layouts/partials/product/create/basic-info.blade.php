<div class="mb-2 pb-2">
    <h4 class="fw-semibold mb-3 text-secondary d-flex align-items-center">
        <i class="ti ti-info-circle me-2 text-muted"></i> {{ __('messages.basic_information') }}
    </h4>
    <div class="row g-3">
        <div class="col-md-3">
            <label class="form-label">{{ __('messages.product_code') }}</label>
            <input type="text" class="form-control" name="code" placeholder="{{ __('messages.enter_code') }}">
        </div>
        <div class="col-md-3">
            <label class="form-label">{{ __('messages.barcode') }}</label>
            <input type="text" class="form-control" name="barcode" placeholder="{{ __('messages.enter_barcode') }}">
        </div>
        <div class="col-md-6">
            <label class="form-label">{{ __('messages.product_name') }}</label>
            <input type="text" class="form-control" name="name" placeholder="{{ __('messages.enter_name') }}">
        </div>
        <div class="col-md-12">
            <label class="form-label">{{ __('messages.description') }}</label>
            <textarea class="form-control" name="description" rows="3" placeholder="{{ __('messages.enter_product_description') }}"></textarea>
        </div>
    </div>
</div>
