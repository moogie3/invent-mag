<div class="card-body border-bottom py-3">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div class="d-flex align-items-center">
            <div class="bg-primary-lt rounded-3 p-2 me-3">
                <i class="ti ti-receipt-refund fs-1 text-primary"></i>
            </div>
            <div>
                <h2 class="mb-1 fw-bold">
                    {{ __('messages.model_sales_return') }}
                </h2>
                <div class="text-muted">
                    {{ __('messages.overview_of_sales_returns') }}
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <!-- Return Summary -->
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-3 bg-light-lt">
                <div class="card-body py-3">
                    <div class="mb-2">
                        <label class="form-label text-muted mb-2 d-block fw-bold">
                            {{ __('messages.return_summary') }}
                        </label>
                    </div>
                    <div class="d-flex align-items-center mb-3">
                        <div class="me-3 d-flex align-items-center justify-content-center rounded-3 badge bg-white shadow-sm"
                            style="width: 40px; height: 40px;">
                            <i class="ti ti-hash fs-3 text-primary"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="small text-muted">{{ __('messages.total_returns') }}</div>
                            <div class="fw-bold fs-3 text-primary">{{ $total_returns }}</div>
                        </div>
                    </div>
                    <div class="d-flex align-items-center">
                        <div class="me-3 d-flex align-items-center justify-content-center rounded-3 badge bg-white shadow-sm"
                            style="width: 40px; height: 40px;">
                            <i class="ti ti-currency fs-3 text-success"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="small text-muted">{{ __('messages.total_amount') }}</div>
                            <div class="fw-bold fs-3 text-success">{{ App\Helpers\CurrencyHelper::format($total_amount) }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Return Status -->
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-3 bg-azure-lt">
                <div class="card-body py-3">
                    <div class="mb-2">
                        <label class="form-label text-primary mb-2 d-block fw-bold">
                            {{ __('messages.return_status') }}
                        </label>
                    </div>
                    <div class="row g-2">
                        <div class="col-12">
                            <div class="d-flex align-items-center mb-2">
                                <div class="me-3 d-flex align-items-center justify-content-center rounded-3 badge bg-white shadow-sm"
                                    style="width: 40px; height: 40px;">
                                    <i class="ti ti-circle-check fs-3 text-success"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="small text-muted">{{ __('messages.completed') }}</div>
                                    <div class="fw-bold fs-4 text-success">{{ $completed_count }}</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="d-flex align-items-center">
                                <div class="me-2 d-flex align-items-center justify-content-center rounded-3 badge bg-white shadow-sm"
                                    style="width: 32px; height: 32px;">
                                    <i class="ti ti-clock fs-4 text-warning"></i>
                                </div>
                                <div>
                                    <div class="small text-muted">{{ __('messages.pending') }}</div>
                                    <div class="fw-bold fs-5 text-warning">{{ $pending_count }}</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="d-flex align-items-center">
                                <div class="me-2 d-flex align-items-center justify-content-center rounded-3 badge bg-white shadow-sm"
                                    style="width: 32px; height: 32px;">
                                    <i class="ti ti-circle-x fs-4 text-danger"></i>
                                </div>
                                <div>
                                    <div class="small text-muted">{{ __('messages.canceled') }}</div>
                                    <div class="fw-bold fs-5 text-danger">{{ $canceled_count }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sales Order Info -->
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-3 bg-orange-lt">
                <div class="card-body py-3">
                    <div class="mb-2">
                        <label class="form-label text-orange mb-2 d-block fw-bold">
                            {{ __('messages.sales_order') }}
                        </label>
                    </div>
                    <div class="d-flex align-items-center">
                        <div class="me-3 d-flex align-items-center justify-content-center rounded-3 badge bg-white shadow-sm"
                            style="width: 48px; height: 48px;">
                            <i class="ti ti-file-invoice fs-2 text-orange"></i>
                        </div>
                        <div>
                            <div class="text-orange small">{{ __('messages.linked_sales_returns') }}</div>
                            <div class="h3 mb-0 text-orange">{{ $total_returns }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-3 bg-light">
                <div class="card-body py-3 d-flex align-items-center">
                    <div class="w-100">
                        @include('admin.layouts.partials.sales-returns.index.filters')
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
