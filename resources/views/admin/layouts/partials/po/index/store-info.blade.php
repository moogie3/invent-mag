<div class="card-body border-bottom py-3">
    <div class="d-flex justify-content-between">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <div class="card-title">Store information</div>
                    <div class="purchase-info row">
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
                        <div class="col-md-4">
                            <div class="mb-2">
                                <span class="nav-link-icon d-md-none d-lg-inline-block align-middle">
                                    <i class="ti ti-step-out fs-2"></i>
                                </span>
                                Invoice OUT: <strong>{{ $outCount }}</strong>
                            </div>
                            <div class="mb-2">
                                <span class="nav-link-icon d-md-none d-lg-inline-block align-middle">
                                    <i class="ti ti-basket-dollar fs-2"></i>
                                </span>
                                Amount OUT:
                                <strong>{{ \App\Helpers\CurrencyHelper::format($outCountamount) }}</strong>
                            </div>
                            <div class="mb-2">
                                <span class="nav-link-icon d-md-none d-lg-inline-block align-middle">
                                    <i class="ti ti-currency fs-2"></i>
                                </span>
                                This Month PO:
                                <strong>{{ \App\Helpers\CurrencyHelper::format($totalMonthly) }}</strong>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-2">
                                <span class="nav-link-icon d-md-none d-lg-inline-block align-middle">
                                    <i class="ti ti-step-into fs-2"></i>
                                </span>
                                Invoice IN: <strong>{{ $inCount }}</strong>
                            </div>
                            <div class="mb-2">
                                <span class="nav-link-icon d-md-none d-lg-inline-block align-middle">
                                    <i class="ti ti-basket-dollar fs-2"></i>
                                </span>
                                Amount IN:
                                <strong>{{ \App\Helpers\CurrencyHelper::format($inCountamount) }}</strong>
                            </div>
                            <div class="mb-2">
                                <span class="nav-link-icon d-md-none d-lg-inline-block align-middle">
                                    <i class="ti ti-credit-card-pay fs-2"></i>
                                </span>
                                This Month Paid:
                                <strong>{{ \App\Helpers\CurrencyHelper::format($paymentMonthly) }}</strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
