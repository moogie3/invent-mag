<div class="h-100 p-4 bg-body-secondary rounded-3">
    <h5 class="card-title mb-3 text-primary">
        <i class="ti ti-info-circle me-2"></i>{{ __('messages.order_summary_title') }}
    </h5>

    <div class="mb-2">
        <i class="ti ti-package me-1"></i> {{ __('messages.total_items') }}
        <strong>{{ $summary['itemCount'] }}</strong>
    </div>
    <div class="mb-2">
        <i class="ti ti-receipt me-1"></i> {{ __('messages.payment_type') }}
        <strong>{{ $sales->payment_type }}</strong>
    </div>
    <div class="mt-3">
        <label class="form-label fw-bold"><i class="ti ti-notes me-1"></i> {{ __('messages.notes') }}</label>
        <textarea name="notes" class="form-control" rows="3">{{ $sales->notes ?? '' }}</textarea>
    </div>
    <div class="mt-3">
        <label class="form-label fw-bold"><i class="ti ti-file-text me-1"></i> {{ __('messages.terms_conditions') }}</label>
        <textarea name="terms_conditions" class="form-control" rows="3">{{ $sales->terms_conditions ?? '' }}</textarea>
    </div>
</div>
