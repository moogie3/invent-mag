<div class="row mb-5">
    <div class="col-md-4">
        @include('admin.layouts.partials.sales.create.order-discount')
    </div>
    <div class="col-md-4">
        @include('admin.layouts.partials.sales.create.customer-credit')
    </div>
    <div class="col-md-4">
        @include('admin.layouts.partials.sales.create.summary-totals')
    </div>
</div>

<div class="row mb-5">
    <div class="col-md-6">
        <div class="h-100 p-4 bg-body-secondary rounded-3">
            <label class="form-label fw-bold"><i class="ti ti-notes me-1"></i> {{ __('messages.notes') }}</label>
            <textarea name="notes" class="form-control" rows="3">{{ old('notes') }}</textarea>
        </div>
    </div>
    <div class="col-md-6">
        <div class="h-100 p-4 bg-body-secondary rounded-3">
            <label class="form-label fw-bold"><i class="ti ti-file-text me-1"></i> {{ __('messages.terms_conditions') }}</label>
            <textarea name="terms_conditions" class="form-control" rows="3">{{ old('terms_conditions') }}</textarea>
        </div>
    </div>
</div>
