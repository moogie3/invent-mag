<div class="row g-3 mb-4">
    <div class="col-sm-6 col-lg-3">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="subheader">Total Transactions</div>
                    <div class="ms-auto">
                        <div class="avatar avatar-sm bg-primary-lt">
                            <i class="ti ti-receipt-2 fs-3"></i>
                        </div>
                    </div>
                </div>
                <div class="h2 mb-2">{{ number_format($summary['total_count'] ?? 0) }}</div>
                <div class="d-flex mb-2">
                    <div class="text-muted">Sales: {{ number_format($summary['sales_count'] ?? 0) }}</div>
                    <div class="ms-2 text-muted">Purchases:
                        {{ number_format($summary['purchases_count'] ?? 0) }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="subheader">Total Amount</div>
                    <div class="ms-auto">
                        <div class="avatar avatar-sm bg-success-lt">
                            <i class="ti ti-currency-dollar fs-3"></i>
                        </div>
                    </div>
                </div>
                <div class="h2 mb-2">
                    {{ \App\Helpers\CurrencyHelper::format($summary['total_amount'] ?? 0) }}</div>
                <div class="d-flex mb-2">
                    <div class="text-success">Revenue:
                        {{ \App\Helpers\CurrencyHelper::format($summary['sales_amount'] ?? 0) }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="subheader">Paid Transactions</div>
                    <div class="ms-auto">
                        <div class="avatar avatar-sm bg-success-lt">
                            <i class="ti ti-check fs-3"></i>
                        </div>
                    </div>
                </div>
                <div class="h2 mb-2">{{ number_format($summary['paid_count'] ?? 0) }}</div>
                <div class="d-flex mb-2">
                    <div class="text-success">
                        {{ \App\Helpers\CurrencyHelper::format($summary['paid_amount'] ?? 0) }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="subheader">Outstanding</div>
                    <div class="ms-auto">
                        <div class="avatar avatar-sm bg-warning-lt">
                            <i class="ti ti-clock fs-3"></i>
                        </div>
                    </div>
                </div>
                <div class="h2 mb-2">{{ number_format($summary['unpaid_count'] ?? 0) }}</div>
                <div class="d-flex mb-2">
                    <div class="text-warning">
                        {{ \App\Helpers\CurrencyHelper::format($summary['unpaid_amount'] ?? 0) }}</div>
                </div>
            </div>
        </div>
    </div>
</div>
