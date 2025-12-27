<div>
    <div class="card shadow-sm border-1 h-100">
        <div class="card-status-top bg-green"></div>
        <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="card-title mb-0"><i class="ti ti-file-invoice fs-3 me-2 text-green"></i>
                {{ __('messages.order_invoice_status') }}</h3>
        </div>
        <div class="card-body p-3">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="h1 mb-0">{{ $invoiceStatusData['totalInvoices'] }}</div>
                <div class="small text-muted">{{ __('messages.total_invoices') }}</div>
                <span class="badge bg-green-lt"><i class="ti ti-check"></i>
                    {{ $invoiceStatusData['collectionRateDisplay'] }}% {{ __('messages.collection_rate') }}</span>
            </div>
            <!-- Outgoing Invoices (Accounts Receivable) -->
            <div class="mb-3">
                <div class="d-flex justify-content-between mb-1">
                    <div><i class="ti ti-arrow-up text-blue"></i> {{ __('messages.outgoing_ar') }}</div>
                    <div class="fw-semibold">{{ \App\Helpers\CurrencyHelper::format($invoiceStatusData['arAging']['current'] + $invoiceStatusData['arAging']['total_overdue']) }}</div>
                </div>
                <div class="text-muted small">
                    <div class="d-flex justify-content-between">
                        <span>{{ __('messages.bucket_current') }}:</span>
                        <span>{{ \App\Helpers\CurrencyHelper::format($invoiceStatusData['arAging']['current']) }}</span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span>{{ __('messages.bucket_1-30') }}:</span>
                        <span>{{ \App\Helpers\CurrencyHelper::format($invoiceStatusData['arAging']['1-30']) }}</span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span>{{ __('messages.bucket_31-60') }}:</span>
                        <span>{{ \App\Helpers\CurrencyHelper::format($invoiceStatusData['arAging']['31-60']) }}</span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span>{{ __('messages.bucket_61-90') }}:</span>
                        <span>{{ \App\Helpers\CurrencyHelper::format($invoiceStatusData['arAging']['61-90']) }}</span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span>{{ __('messages.bucket_90+') }}:</span>
                        <span>{{ \App\Helpers\CurrencyHelper::format($invoiceStatusData['arAging']['90+']) }}</span>
                    </div>
                </div>
                <div class="mt-2 text-end">
                    <a href="{{ route('admin.reports.aged-receivables') }}" class="btn btn-sm btn-link">{{ __('messages.view_ar_report') }}</a>
                </div>
            </div>
            <!-- Incoming Invoices (Accounts Payable) -->
            <div>
                <div class="d-flex justify-content-between mb-1">
                    <div><i class="ti ti-arrow-down text-pink"></i> {{ __('messages.incoming_ap') }}</div>
                    <div class="fw-semibold">{{ \App\Helpers\CurrencyHelper::format($invoiceStatusData['apAging']['current'] + $invoiceStatusData['apAging']['total_overdue']) }}</div>
                </div>
                <div class="text-muted small">
                    <div class="d-flex justify-content-between">
                        <span>{{ __('messages.bucket_current') }}:</span>
                        <span>{{ \App\Helpers\CurrencyHelper::format($invoiceStatusData['apAging']['current']) }}</span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span>{{ __('messages.bucket_1-30') }}:</span>
                        <span>{{ \App\Helpers\CurrencyHelper::format($invoiceStatusData['apAging']['1-30']) }}</span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span>{{ __('messages.bucket_31-60') }}:</span>
                        <span>{{ \App\Helpers\CurrencyHelper::format($invoiceStatusData['apAging']['31-60']) }}</span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span>{{ __('messages.bucket_61-90') }}:</span>
                        <span>{{ \App\Helpers\CurrencyHelper::format($invoiceStatusData['apAging']['61-90']) }}</span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span>{{ __('messages.bucket_90+') }}:</span>
                        <span>{{ \App\Helpers\CurrencyHelper::format($invoiceStatusData['apAging']['90+']) }}</span>
                    </div>
                </div>
                <div class="mt-2 text-end">
                    <a href="{{ route('admin.reports.aged-payables') }}" class="btn btn-sm btn-link">{{ __('messages.view_ap_report') }}</a>
                </div>
            </div>
            <!-- Summary -->
            <div class="mt-3 row text-center">
                <div class="col">
                    <div class="small text-muted">{{ __('messages.average_due_days') }}</div>
                    <div class="h3">{{ $invoiceStatusData['avgDueDays'] }}</div>
                </div>
                <div class="col">
                    <div class="small text-muted">{{ __('messages.collection_rate') }}</div>
                    <div class="h3">{{ $invoiceStatusData['collectionRate'] }}%</div>
                </div>
            </div>
        </div>
    </div>
</div>
