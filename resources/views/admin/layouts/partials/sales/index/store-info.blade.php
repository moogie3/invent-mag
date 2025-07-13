<div class="card-body border-bottom py-3">
    <div class="d-flex justify-content-between">
        <div class="col-md-12">
            <div class="card card-primary">
                <div class="card-body border-bottom py-3">
                    <div class="d-flex align-items-center justify-content-between mb-4">
                        <div class="d-flex align-items-center">
                            <i class="ti ti-building-store fs-1 me-3 text-primary"></i>
                            <div>
                                <h2 class="mb-1">
                                    Store Information
                                </h2>
                                <div class="text-muted">
                                    Overview of your store performance and metrics
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-3">
                            <div class="card border-0 bg-light">
                                <div class="card-body py-3">
                                    <div class="mb-2">
                                        <label class="form-label text-muted mb-2 d-block">
                                            Store Details
                                        </label>
                                    </div>
                                    <div class="d-flex align-items-center mb-3">
                                        <div class="me-3 d-flex align-items-center justify-content-center"
                                            style="width: 32px; height: 32px;">
                                            <i class="ti ti-building-store fs-3 text-primary"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="small text-muted">User Store</div>
                                            <div class="fw-bold">{{ $shopname }}</div>
                                        </div>
                                    </div>
                                    <div class="d-flex align-items-center mb-3">
                                        <div class="me-3 d-flex align-items-center justify-content-center"
                                            style="width: 32px; height: 32px;">
                                            <i class="ti ti-map fs-3 text-success"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="small text-muted">Store Address</div>
                                            <div class="fw-bold">{{ $address }}</div>
                                        </div>
                                    </div>
                                    <div class="d-flex align-items-center">
                                        <div class="me-3 d-flex align-items-center justify-content-center"
                                            style="width: 32px; height: 32px;">
                                            <i class="ti ti-file-invoice fs-3 text-info"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="small text-muted">Total Invoice</div>
                                            <div class="fw-bold">{{ $totalinvoice }}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card border-0 bg-primary text-white">
                                <div class="card-body py-3">
                                    <div class="mb-2">
                                        <label class="form-label text-white-50 mb-2 d-block">
                                            Sales Overview
                                        </label>
                                    </div>
                                    <div class="d-flex align-items-center mb-3">
                                        <div class="me-3">
                                            <i class="ti ti-currency fs-2"></i>
                                        </div>
                                        <div>
                                            <div class="text-white-50 small">This Month Sales</div>
                                            <div class="h4 mb-0">
                                                {{ \App\Helpers\CurrencyHelper::format($totalMonthly) }}</div>
                                        </div>
                                    </div>
                                    <div class="d-flex align-items-center mb-3">
                                        <div class="me-3">
                                            <i class="ti ti-cash fs-2"></i>
                                        </div>
                                        <div>
                                            <div class="text-white-50 small">Total POS Sales</div>
                                            <div class="h4 mb-0">{{ \App\Helpers\CurrencyHelper::format($posTotal) }}
                                            </div>
                                        </div>
                                    </div>
                                    <div class="d-flex align-items-center">
                                        <div class="me-3">
                                            <i class="ti ti-moneybag fs-2"></i>
                                        </div>
                                        <div>
                                            <div class="text-white-50 small">Unpaid Receivable</div>
                                            <div class="h4 mb-0">{{ \App\Helpers\CurrencyHelper::format($unpaidDebt) }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <!-- Pending Orders Card -->
                            <div class="card border-0 bg-white">
                                <div class="card-body py-3">
                                    <div class="mb-2">
                                        <label class="form-label text-warning mb-2 d-block">
                                            Pending Orders
                                        </label>
                                    </div>
                                    <div class="d-flex align-items-center">
                                        <div class="me-3">
                                            <i class="ti ti-loader fs-2 text-warning"></i>
                                        </div>
                                        <div>
                                            <div class="text-warning small">Pending Orders</div>
                                            <div class="h3 mb-0 text-warning">{{ $pendingOrders }}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- Due Soon Invoices Card -->
                            <div class="card border-0 bg-white">
                                <div class="card-body py-3">
                                    <div class="mb-2">
                                        <label class="form-label text-danger mb-2 d-block">
                                            Due Soon Invoices
                                        </label>
                                    </div>
                                    <div class="d-flex align-items-center">
                                        <div class="me-3">
                                            <i class="ti ti-reload fs-2 text-danger"></i>
                                        </div>
                                        <div>
                                            <div class="text-danger small">Due Soon Invoices</div>
                                            <div class="h3 mb-0 text-danger">{{ $dueInvoices }}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            @include('admin.layouts.partials.sales.index.filters')
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
