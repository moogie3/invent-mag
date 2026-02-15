<div class="card-body border-bottom py-3">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div class="d-flex align-items-center">
            <div class="bg-primary-lt rounded-3 p-2 me-3">
                <i class="ti ti-report-money fs-1 text-primary"></i>
            </div>
            <div>
                <h2 class="mb-1 fw-bold">
                    {{ __('messages.store_information') }}
                </h2>
                <div class="text-muted">
                    {{ __('messages.overview_of_your_store_performance_and_metrics') }}
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <!-- Store Details -->
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-3 bg-light-lt">
                <div class="card-body py-3">
                    <div class="mb-2">
                        <label class="form-label text-muted mb-2 d-block fw-bold">
                            {{ __('messages.store_details') }}
                        </label>
                    </div>
                    <div class="d-flex align-items-center mb-3">
                        <div class="me-3 d-flex align-items-center justify-content-center rounded-3 badge bg-white shadow-sm"
                            style="width: 40px; height: 40px;">
                            <i class="ti ti-building-store fs-3 text-primary"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="small text-muted">{{ __('messages.user_store') }}</div>
                            <div class="fw-bold fs-3 text-primary">{{ $shopname }}</div>
                        </div>
                    </div>
                    <div class="d-flex align-items-center mb-3">
                        <div class="me-3 d-flex align-items-center justify-content-center rounded-3 badge bg-white shadow-sm"
                            style="width: 40px; height: 40px;">
                            <i class="ti ti-map fs-3 text-success"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="small text-muted">{{ __('messages.store_address') }}</div>
                            <div class="fw-bold fs-4">{{ $address }}</div>
                        </div>
                    </div>
                    <div class="d-flex align-items-center">
                        <div class="me-3 d-flex align-items-center justify-content-center rounded-3 badge bg-white shadow-sm"
                            style="width: 40px; height: 40px;">
                            <i class="ti ti-file-invoice fs-3 text-info"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="small text-muted">{{ __('messages.total_invoice') }}</div>
                            <div class="fw-bold fs-3 text-info" id="totalInvoiceCount">{{ $totalinvoice }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sales Overview -->
        <div class="col-md-6">
            <div class="card border-0 shadow-sm rounded-3 bg-azure-lt">
                <div class="card-body py-3">
                    <div class="mb-2">
                        <label class="form-label text-primary mb-2 d-block fw-bold">
                            {{ __('messages.sales_overview') }}
                        </label>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="d-flex align-items-center">
                                <div class="me-3 d-flex align-items-center justify-content-center rounded-3 badge bg-white shadow-sm"
                                    style="width: 40px; height: 40px;">
                                    <i class="ti ti-currency fs-3 text-primary"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="small text-muted">{{ __('messages.this_month_sales') }}</div>
                                    <div class="fw-bold fs-3 text-primary" id="thisMonthSales">
                                        {{ \App\Helpers\CurrencyHelper::format($totalMonthly) }}</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex align-items-center">
                                <div class="me-3 d-flex align-items-center justify-content-center rounded-3 badge bg-white shadow-sm"
                                    style="width: 40px; height: 40px;">
                                    <i class="ti ti-http-post fs-3 text-success"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="small text-muted">{{ __('messages.total_pos_sales') }}</div>
                                    <div class="fw-bold fs-3 text-success" id="totalPosSales">
                                        {{ \App\Helpers\CurrencyHelper::format($posTotal) }}</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex align-items-center">
                                <div class="me-3 d-flex align-items-center justify-content-center rounded-3 badge bg-white shadow-sm"
                                    style="width: 40px; height: 40px;">
                                    <i class="ti ti-moneybag fs-3 text-warning"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="small text-muted">{{ __('messages.unpaid_receivable') }}</div>
                                    <div class="fw-bold fs-3 text-warning" id="unpaidReceivable">
                                        {{ \App\Helpers\CurrencyHelper::format($unpaidDebt) }}</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex align-items-center">
                                <div class="me-3 d-flex align-items-center justify-content-center rounded-3 badge bg-white shadow-sm"
                                    style="width: 40px; height: 40px;">
                                    <i class="ti ti-loader fs-3 text-warning"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="small text-muted">{{ __('messages.pending_orders') }}</div>
                                    <div class="fw-bold fs-3 text-warning" id="pendingOrdersCount">{{ $pendingOrders }}</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex align-items-center">
                                <div class="me-3 d-flex align-items-center justify-content-center rounded-3 badge bg-white shadow-sm"
                                    style="width: 40px; height: 40px;">
                                    <i class="ti ti-reload fs-3 text-info"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="small text-muted">{{ __('messages.due_soon_invoices') }}</div>
                                    <div class="fw-bold fs-3 text-info" id="dueInvoicesCount">{{ $dueInvoices }}</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="d-flex align-items-center pt-2 border-top">
                                <div class="me-3 d-flex align-items-center justify-content-center rounded-3 badge bg-white shadow-sm"
                                    style="width: 40px; height: 40px;">
                                    <i class="ti ti-calendar-time fs-3 text-muted"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="small text-muted">{{ __('messages.expiring_soon_sales_invoices') }}</div>
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="fw-bold fs-3 text-muted" id="expiringSalesItemsCount">{{ $expiringSalesCount }}</div>
                                        @if ($expiringSalesCount > 0)
                                            <a href="#" class="btn btn-sm btn-outline-secondary rounded-pill"
                                                id="viewExpiringSales" data-bs-toggle="modal"
                                                data-bs-target="#expiringSalesModal">{{ __('messages.view_details') }}</a>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-3 bg-light h-100">
                <div class="card-body py-3 d-flex align-items-center">
                    <div class="w-100">
                        @include('admin.layouts.partials.sales.index.filters')
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>