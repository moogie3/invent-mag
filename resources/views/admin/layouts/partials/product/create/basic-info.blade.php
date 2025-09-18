<div class="mb-2 pb-2">
    <h4 class="fw-semibold mb-3 text-secondary d-flex align-items-center">
        <i class="ti ti-info-circle me-2 text-muted"></i> {{ __('Basic Information') }}
    </h4>
    <div class="row g-3">
        <div class="col-md-4">
            <label class="form-label">{{ __('Product Code') }}</label>
            <input type="text" class="form-control" name="code" placeholder="{{ __('Enter code') }}">
        </div>
        <div class="col-md-8">
            <label class="form-label">{{ __('Product Name') }}</label>
            <input type="text" class="form-control" name="name" placeholder="{{ __('Enter name') }}">
        </div>
        <div class="col-md-12">
            <label class="form-label">{{ __('description') }}</label>
            <textarea class="form-control" name="description" rows="3" placeholder="{{ __('Enter product description') }}"></textarea>
        </div>
    </div>
</div>
