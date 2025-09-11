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
                                            <i class="ti ti-building-store fs-2 text-primary"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="small text-muted">User Store</div>
                                            <div class="fw-bold">{{ $shopname }}</div>
                                        </div>
                                    </div>
                                    <div class="d-flex align-items-center mb-3">
                                        <div class="me-3 d-flex align-items-center justify-content-center"
                                            style="width: 32px; height: 32px;">
                                            <i class="ti ti-map fs-2 text-success"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="small text-muted">Store Address</div>
                                            <div class="fw-bold">{{ $address }}</div>
                                        </div>
                                    </div>
                                    <div class="d-flex align-items-center">
                                        <div class="me-3 d-flex align-items-center justify-content-center"
                                            style="width: 32px; height: 32px;">
                                            <i class="ti ti-file-invoice fs-2 text-info"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="small text-muted">Total Invoice</div>
                                            <div class="fw-bold" id="totalInvoiceCount">{{ $totalinvoice }}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card border-0 bg-primary text-white">
                                <div class="card-body py-3">
                                    <div class="mb-2">
                                        <label class="form-label text-white-50 mb-2 d-block">
                                            Financial Overview
                                        </label>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="d-flex align-items-center mb-3">
                                                <div class="me-2">
                                                    <i class="ti ti-step-out fs-2"></i>
                                                </div>
                                                <div>
                                                    <div class="text-white-50 small">Invoice OUT</div>
                                                    <div class="h4 mb-0" id="invoiceOutCount">{{ $outCount }}</div>
                                                </div>
                                            </div>
                                            <div class="d-flex align-items-center mb-3">
                                                <div class="me-2">
                                                    <i class="ti ti-basket-dollar fs-2"></i>
                                                </div>
                                                <div>
                                                    <div class="text-white-50 small">Amount OUT</div>
                                                    <div class="h4 mb-0" id="amountOutCount">
                                                        {{ \App\Helpers\CurrencyHelper::format($outCountamount) }}</div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="d-flex align-items-center mb-3">
                                                <div class="me-2">
                                                    <i class="ti ti-step-into fs-2"></i>
                                                </div>
                                                <div>
                                                    <div class="text-white-50 small">Invoice IN</div>
                                                    <div class="h4 mb-0" id="invoiceInCount">{{ $inCount }}</div>
                                                </div>
                                            </div>
                                            <div class="d-flex align-items-center mb-3">
                                                <div class="me-2">
                                                    <i class="ti ti-basket-dollar fs-2"></i>
                                                </div>
                                                <div>
                                                    <div class="text-white-50 small">Amount IN</div>
                                                    <div class="h4 mb-0" id="amountInCount">
                                                        {{ \App\Helpers\CurrencyHelper::format($inCountamount) }}</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="d-flex align-items-center">
                                                <div class="me-2">
                                                    <i class="ti ti-currency fs-2"></i>
                                                </div>
                                                <div>
                                                    <div class="text-white-50 small">Monthly Purchase</div>
                                                    <div class="h4 mb-0" id="monthlyPurchase">
                                                        {{ \App\Helpers\CurrencyHelper::format($totalMonthly) }}</div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="d-flex align-items-center">
                                                <div class="me-2">
                                                    <i class="ti ti-credit-card-pay fs-2"></i>
                                                </div>
                                                <div>
                                                    <div class="text-white-50 small">Monthly Payment</div>
                                                    <div class="h4 mb-0" id="monthlyPayment">
                                                        {{ \App\Helpers\CurrencyHelper::format($paymentMonthly) }}</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="card border-0 bg-white">
                                <div class="card-body py-3">
                                    <div class="mb-2">
                                        <label
                                            class="form-label {{ $expiringPurchaseCount > 0 ? 'text-warning' : 'text-success' }} mb-2 d-block">
                                            Expiry Status
                                        </label>
                                    </div>
                                    <div class="d-flex align-items-center">
                                        <div class="me-3">
                                            <i
                                                class="ti ti-calendar-time fs-2 {{ $expiringPurchaseCount > 0 ? 'text-warning' : 'text-success' }}"></i>
                                        </div>
                                        <div>
                                            <div
                                                class="small {{ $expiringPurchaseCount > 0 ? 'text-warning' : 'text-success' }}">
                                                Expiring Soon Purchase Orders</div>
                                            <div class="h4 mb-0 {{ $expiringPurchaseCount > 0 ? 'text-warning' : 'text-success' }}"
                                                id="expiringPurchaseItemsCount">
                                                {{ $expiringPurchaseCount }}</div>
                                            @if ($expiringPurchaseCount > 0)
                                                <a href="#" class="mt-2 btn btn-sm btn-outline-warning"
                                                    id="viewExpiringPurchase" data-bs-toggle="modal"
                                                    data-bs-target="#expiringPurchaseModal">
                                                    View Details
                                                </a>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            @include('admin.layouts.partials.po.index.filters')
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
