<div class="card-body border-bottom py-3">
    <div class="d-flex justify-content-between">
        <div class="col-md-12">
            <div class="card card-primary">
                <div class="card-body border-bottom py-3">
                    <div class="d-flex align-items-center justify-content-between mb-4">
                        <div class="d-flex align-items-center">
                            <i class="ti ti-building-store fs-1 me-3 text-primary"></i>
                            <div>
                                <h2 class="mb-1">{{ __('messages.store_information') }}</h2>
                                <div class="text-muted">
                                    {{ __('messages.overview_of_your_store_performance_and_metrics') }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <!-- Store Details -->
                        <div class="col-lg-3 col-md-6">
                            <div class="card border-0 bg-light h-100">
                                <div class="card-body py-3">
                                    <div class="mb-2">
                                        <label
                                            class="form-label text-muted mb-2 d-block">{{ __('messages.store_details') }}</label>
                                    </div>
                                    <div class="d-flex align-items-center mb-3">
                                        <div class="me-3 d-flex align-items-center justify-content-center rounded badge"
                                            style="width: 40px; height: 40px;"">
                                            <i class="ti ti-building-store fs-3 text-primary"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="small text-muted">{{ __('messages.user_store') }}</div>
                                            <div class="fw-bold">{{ $shopname }}</div>
                                        </div>
                                    </div>
                                    <div class="d-flex align-items-center mb-3">
                                        <div class="me-3 d-flex align-items-center justify-content-center rounded badge"
                                            style="width: 40px; height: 40px;"">
                                            <i class="ti ti-map fs-3 text-success"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="small text-muted">{{ __('messages.store_address') }}</div>
                                            <div class="fw-bold">{{ $address }}</div>
                                        </div>
                                    </div>
                                    <div class="d-flex align-items-center">
                                        <div class="me-3 d-flex align-items-center justify-content-center rounded badge"
                                            style="width: 40px; height: 40px;"">
                                            <i class="ti ti-file-invoice fs-3 text-info"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="small text-muted">{{ __('messages.total_invoice') }}</div>
                                            <div class="fw-bold" id="totalInvoiceCount">{{ $totalinvoice }}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Sales Overview -->
                        <div class="col-lg-3 col-md-6">
                            <div class="card border-0 bg-primary text-white h-100">
                                <div class="card-body py-3">
                                    <div class="mb-2">
                                        <label
                                            class="form-label text-white-50 mb-2 d-block">{{ __('messages.sales_overview') }}</label>
                                    </div>
                                    <div class="d-flex align-items-center mb-3">
                                        <div class="me-3">
                                            <i class="ti ti-currency fs-2"></i>
                                        </div>
                                        <div>
                                            <div class="text-white-50 small">{{ __('messages.this_month_sales') }}
                                            </div>
                                            <div class="h4 mb-0" id="thisMonthSales">
                                                {{ \App\Helpers\CurrencyHelper::format($totalMonthly) }}</div>
                                        </div>
                                    </div>
                                    <div class="d-flex align-items-center mb-3">
                                        <div class="me-3">
                                            <i class="ti ti-http-post fs-2"></i>
                                        </div>
                                        <div>
                                            <div class="text-white-50 small">{{ __('messages.total_pos_sales') }}</div>
                                            <div class="h4 mb-0" id="totalPosSales">
                                                {{ \App\Helpers\CurrencyHelper::format($posTotal) }}</div>
                                        </div>
                                    </div>
                                    <div class="d-flex align-items-center">
                                        <div class="me-3">
                                            <i class="ti ti-moneybag fs-2"></i>
                                        </div>
                                        <div>
                                            <div class="text-white-50 small">{{ __('messages.unpaid_receivable') }}
                                            </div>
                                            <div class="h4 mb-0" id="unpaidReceivable">
                                                {{ \App\Helpers\CurrencyHelper::format($unpaidDebt) }}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Order & Invoice Status - Unified Blue Design -->
                        <div class="col-lg-3 col-md-6">
                            <div class="card border-0 bg-info text-white h-100">
                                <div class="card-body py-3">
                                    <div class="mb-2">
                                        <label
                                            class="form-label text-white-50 mb-2 d-block">{{ __('messages.order_invoice_status') }}</label>
                                    </div>
                                    <div class="d-flex align-items-center mb-3">
                                        <div class="me-3">
                                            <i class="ti ti-loader fs-2"></i>
                                        </div>
                                        <div>
                                            <div class="text-white-50 small">{{ __('messages.pending_orders') }}</div>
                                            <div class="h4 mb-0" id="pendingOrdersCount">{{ $pendingOrders }}</div>
                                        </div>
                                    </div>
                                    <div class="d-flex align-items-center mb-3">
                                        <div class="me-3">
                                            <i class="ti ti-reload fs-2"></i>
                                        </div>
                                        <div>
                                            <div class="text-white-50 small">{{ __('messages.due_soon_invoices') }}
                                            </div>
                                            <div class="h4 mb-0" id="dueInvoicesCount">{{ $dueInvoices }}</div>
                                        </div>
                                    </div>
                                    <div class="d-flex align-items-center">
                                        <div class="me-3">
                                            <i class="ti ti-calendar-time fs-2"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="text-white-50 small">
                                                {{ __('messages.expiring_soon_sales_invoices') }}</div>
                                            <div class="h4 mb-0" id="expiringSalesItemsCount">{{ $expiringSalesCount }}
                                            </div>
                                            @if ($expiringSalesCount > 0)
                                                <a href="#" class="mt-1 btn btn-sm btn-outline-light btn-sm"
                                                    id="viewExpiringSales" data-bs-toggle="modal"
                                                    data-bs-target="#expiringSalesModal">{{ __('messages.view_details') }}</a>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Filters Only -->
                        <div class="col-lg-3 col-md-6">
                            <div class="card border-0 bg-light h-100">
                                <div class="card-body py-3">
                                    @include('admin.layouts.partials.sales.index.filters')
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
