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
            <!-- Outgoing Invoices -->
            <div class="mb-3">
                <div class="d-flex justify-content-between mb-1">
                    <div><i class="ti ti-arrow-up text-blue"></i> {{ __('messages.outgoing') }}</div>
                    <div class="fw-semibold">{{ $invoiceStatusData['outCount'] }}</div>
                </div>
                <div class="progress progress-sm mb-2">
                    <div class="progress-bar bg-blue" style="width: {{ $invoiceStatusData['outPercentage'] }}%;"></div>
                </div>
                <div class="d-flex justify-content-between small text-muted">
                    <div>{{ $invoiceStatusData['outCount'] - $invoiceStatusData['outCountUnpaid'] }}
                        {{ __('messages.paid') }}</div>
                    <div>{{ $invoiceStatusData['outCountUnpaid'] }} {{ __('messages.awaiting') }}</div>
                </div>
            </div>
            <!-- Incoming Invoices -->
            <div>
                <div class="d-flex justify-content-between mb-1">
                    <div><i class="ti ti-arrow-down text-pink"></i> {{ __('messages.incoming') }}</div>
                    <div class="fw-semibold">{{ $invoiceStatusData['inCount'] }}</div>
                </div>
                <div class="progress progress-sm mb-2">
                    <div class="progress-bar bg-pink" style="width: {{ $invoiceStatusData['inPercentage'] }}%;"></div>
                </div>
                <div class="d-flex justify-content-between small text-muted">
                    <div>{{ $invoiceStatusData['inCount'] - $invoiceStatusData['inCountUnpaid'] }} {{ __('messages.paid') }}
                    </div>
                    <div>{{ $invoiceStatusData['inCountUnpaid'] }} {{ __('messages.awaiting') }}</div>
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
