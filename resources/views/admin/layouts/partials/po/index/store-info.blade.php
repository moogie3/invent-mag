<div class="card-body border-bottom py-3">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div class="d-flex align-items-center">
            <div class="bg-primary-lt rounded-3 p-2 me-3">
                <i class="ti ti-building-store fs-1 text-primary"></i>
            </div>
            <div>
                <h2 class="mb-1 fw-bold">
                    {{ __('messages.store_information') }}
                </h2>
                <div class="text-muted">
                    {{ __('messages.overview_store_performance_metrics') }}
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

        <!-- Financial Overview -->
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-3 bg-azure-lt">
                <div class="card-body py-3">
                    <div class="mb-2">
                        <label class="form-label text-primary mb-2 d-block fw-bold">
                            {{ __('messages.financial_overview') }}
                        </label>
                    </div>
                    <div class="d-flex align-items-center mb-3">
                        <div class="me-3 d-flex align-items-center justify-content-center rounded-3 badge bg-white shadow-sm"
                            style="width: 40px; height: 40px;">
                            <i class="ti ti-currency fs-3 text-primary"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="small text-muted">{{ __('messages.monthly_purchase') }}</div>
                            <div class="fw-bold fs-3 text-primary" id="monthlyPurchase">
                                {{ \App\Helpers\CurrencyHelper::format($totalMonthly) }}</div>
                        </div>
                    </div>
                    <div class="d-flex align-items-center">
                        <div class="me-3 d-flex align-items-center justify-content-center rounded-3 badge bg-white shadow-sm"
                            style="width: 40px; height: 40px;">
                            <i class="ti ti-credit-card-pay fs-3 text-success"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="small text-muted">{{ __('messages.monthly_payment') }}</div>
                            <div class="fw-bold fs-3 text-success" id="monthlyPayment">
                                {{ \App\Helpers\CurrencyHelper::format($paymentMonthly) }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Expiry Status -->
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-3 bg-orange-lt">
                <div class="card-body py-3">
                    <div class="mb-2">
                        <label
                            class="form-label {{ $expiringPurchaseCount > 0 ? 'text-warning' : 'text-success' }} mb-2 d-block fw-bold">
                            {{ __('messages.expiry_status') }}
                        </label>
                    </div>
                    <div class="d-flex align-items-center">
                        <div class="me-3 d-flex align-items-center justify-content-center rounded-3 badge bg-white shadow-sm"
                            style="width: 48px; height: 48px;">
                            <i
                                class="ti ti-calendar-time fs-2 {{ $expiringPurchaseCount > 0 ? 'text-warning' : 'text-success' }}"></i>
                        </div>
                        <div>
                            <div
                                class="small {{ $expiringPurchaseCount > 0 ? 'text-warning' : 'text-success' }}">
                                {{ __('messages.expiring_soon_purchase_orders') }}</div>
                            <div class="h3 mb-0 {{ $expiringPurchaseCount > 0 ? 'text-warning' : 'text-success' }}"
                                id="expiringPurchaseItemsCount">
                                {{ $expiringPurchaseCount }}</div>
                            @if ($expiringPurchaseCount > 0)
                                <a href="#" class="mt-2 btn btn-sm btn-outline-warning rounded-pill"
                                    id="viewExpiringPurchase" data-bs-toggle="modal"
                                    data-bs-target="#expiringPurchaseModal">
                                    {{ __('messages.view_details') }}
                                </a>
                            @endif
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
                        @include('admin.layouts.partials.po.index.filters')
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
