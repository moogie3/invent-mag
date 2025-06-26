<div class="card-body border-bottom py-3">
    <div class="d-flex justify-content-between">
        <div class="col-md-8">
            <div class="card">
                <div class="card-body">
                    <div class="card-title">Store information</div>
                    <div class="sales-info row">
                        <div class="col-md-4">
                            <div class="mb-2">
                                <span class="nav-link-icon d-md-none d-lg-inline-block align-middle">
                                    <i class="ti ti-building-store fs-2"></i>
                                </span>
                                User Store : <strong>{{ $shopname }}</strong>
                            </div>
                            <div class="mb-2">
                                <span class="nav-link-icon d-md-none d-lg-inline-block align-middle">
                                    <i class="ti ti-map fs-2"></i>
                                </span>
                                Store Address : <strong>{{ $address }}</strong>
                            </div>
                            <div class="mb-2">
                                <span class="nav-link-icon d-md-none d-lg-inline-block align-middle">
                                    <i class="ti ti-file-invoice fs-2"></i>
                                </span>
                                Total Invoice : <strong>{{ $totalinvoice }}</strong>
                            </div>
                        </div>
                        <div class="col-md-5">
                            <div class="mb-2">
                                <span class="nav-link-icon d-md-none d-lg-inline-block align-middle">
                                    <i class="ti ti-currency fs-2"></i>
                                </span>
                                This Month Sales:
                                <strong>{{ \App\Helpers\CurrencyHelper::format($totalMonthly) }}</strong>
                            </div>
                            <div class="mb-2">
                                <span class="nav-link-icon d-md-none d-lg-inline-block align-middle">
                                    <i class="ti ti-cash fs-2"></i>
                                </span>
                                Total POS Sales:
                                <strong>{{ \App\Helpers\CurrencyHelper::format($posTotal) }}</strong>
                            </div>
                            <div class="mb-2">
                                <span class="nav-link-icon d-md-none d-lg-inline-block align-middle">
                                    <i class="ti ti-moneybag fs-2"></i>
                                </span>
                                Unpaid Receivable:
                                <strong>{{ \App\Helpers\CurrencyHelper::format($unpaidDebt) }}</strong>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-2">
                                <span class="nav-link-icon d-md-none d-lg-inline-block align-middle">
                                    <i class="ti ti-loader fs-2"></i>
                                </span>
                                Pending Orders:
                                <strong>{{ $pendingOrders }}</strong>
                            </div>
                            <div class="mb-2">
                                <span class="nav-link-icon d-md-none d-lg-inline-block align-middle">
                                    <i class="ti ti-reload fs-2"></i>
                                </span>
                                Due Soon Invoices:
                                <strong>{{ $dueInvoices }}</strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @include('admin.layouts.partials.sales.index.filters')
    </div>
</div>
