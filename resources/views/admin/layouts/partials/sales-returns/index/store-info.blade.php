<div class="card-body border-bottom py-3">
    <div class="d-flex justify-content-between">
        <div class="col-md-12">
            <div class="card card-primary">
                <div class="card-body border-bottom py-3">
                    <div class="d-flex align-items-center justify-content-between mb-4">
                        <div class="d-flex align-items-center">
                            <i class="ti ti-receipt-refund fs-1 me-3 text-primary"></i>
                            <div>
                                <h2 class="mb-1">{{ __('messages.model_sales_return') }}</h2>
                                <div class="text-muted">{{ __('messages.overview_of_sales_returns') }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <!-- Return Details -->
                        <div class="col-lg-4 col-md-6">
                            <div class="card border-0 bg-light h-100">
                                <div class="card-body py-3">
                                    <div class="mb-2">
                                        <label class="form-label text-muted mb-2 d-block">{{ __('messages.return_summary') }}</label>
                                    </div>
                                    <div class="d-flex align-items-center mb-3">
                                        <div class="me-3 d-flex align-items-center justify-content-center rounded badge" style="width: 40px; height: 40px;">
                                            <i class="ti ti-hash fs-3 text-primary"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="small text-muted">{{ __('messages.total_returns') }}</div>
                                            <div class="fw-bold fs-4">{{ $total_returns }}</div>
                                        </div>
                                    </div>
                                    <div class="d-flex align-items-center">
                                        <div class="me-3 d-flex align-items-center justify-content-center rounded badge" style="width: 40px; height: 40px;">
                                            <i class="ti ti-currency-dollar fs-3 text-success"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="small text-muted">{{ __('messages.total_amount') }}</div>
                                            <div class="fw-bold fs-4">{{ App\Helpers\CurrencyHelper::format($total_amount) }}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Return Status -->
                        <div class="col-lg-5 col-md-6">
                            <div class="card border-0 bg-info text-white h-100">
                                <div class="card-body py-3">
                                    <div class="mb-2">
                                        <label class="form-label text-white-50 mb-2 d-block">{{ __('messages.return_status') }}</label>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="d-flex align-items-center mb-3">
                                                <div class="me-2">
                                                    <i class="ti ti-circle-check fs-2"></i>
                                                </div>
                                                <div>
                                                    <div class="text-white-50 small">{{ __('messages.completed') }}</div>
                                                    <div class="h4 mb-0">{{ $completed_count }}</div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="d-flex align-items-center mb-3">
                                                <div class="me-2">
                                                    <i class="ti ti-clock fs-2"></i>
                                                </div>
                                                <div>
                                                    <div class="text-white-50 small">{{ __('messages.pending') }}</div>
                                                    <div class="h4 mb-0">{{ $pending_count }}</div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="d-flex align-items-center">
                                                <div class="me-2">
                                                    <i class="ti ti-circle-x fs-2"></i>
                                                </div>
                                                <div>
                                                    <div class="text-white-50 small">{{ __('messages.canceled') }}</div>
                                                    <div class="h4 mb-0">{{ $canceled_count }}</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Filters -->
                        <div class="col-lg-3 col-md-12">
                            <div class="card border-0 bg-light h-100">
                                <div class="card-body py-3">
                                    @include('admin.layouts.partials.sales-returns.index.filters')
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
