<div>
    <div class="card shadow-sm border-1 h-100">
        <div class="card-status-top bg-green"></div>
        <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="card-title mb-0"><i class="ti ti-file-invoice fs-3 me-2 text-green"></i>
                Invoice Status</h3>
        </div>
        <div class="card-body p-3">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="h1 mb-0">{{ $invoiceStatusData['totalInvoices'] }}</div>
                <div class="small text-muted">Total Invoices</div>
                <span class="badge bg-green-lt"><i class="ti ti-check"></i>
                    {{ $invoiceStatusData['collectionRateDisplay'] }}% collection rate</span>
            </div>
            <!-- Outgoing Invoices -->
            <div class="mb-3">
                <div class="d-flex justify-content-between mb-1">
                    <div><i class="ti ti-arrow-up text-blue"></i> Outgoing</div>
                    <div class="fw-semibold">{{ $invoiceStatusData['outCount'] }}</div>
                </div>
                <div class="progress progress-sm mb-2">
                    <div class="progress-bar bg-blue" style="width: {{ $invoiceStatusData['outPercentage'] }}%;"></div>
                </div>
                <div class="d-flex justify-content-between small text-muted">
                    <div>{{ $invoiceStatusData['outCount'] - $invoiceStatusData['outCountUnpaid'] }}
                        paid</div>
                    <div>{{ $invoiceStatusData['outCountUnpaid'] }} awaiting</div>
                </div>
            </div>
            <!-- Incoming Invoices -->
            <div>
                <div class="d-flex justify-content-between mb-1">
                    <div><i class="ti ti-arrow-down text-pink"></i> Incoming</div>
                    <div class="fw-semibold">{{ $invoiceStatusData['inCount'] }}</div>
                </div>
                <div class="progress progress-sm mb-2">
                    <div class="progress-bar bg-pink" style="width: {{ $invoiceStatusData['inPercentage'] }}%;"></div>
                </div>
                <div class="d-flex justify-content-between small text-muted">
                    <div>{{ $invoiceStatusData['inCount'] - $invoiceStatusData['inCountUnpaid'] }} paid
                    </div>
                    <div>{{ $invoiceStatusData['inCountUnpaid'] }} awaiting</div>
                </div>
            </div>
            <!-- Summary -->
            <div class="mt-3 row text-center">
                <div class="col">
                    <div class="small text-muted">Average Due (Days)</div>
                    <div class="h3">{{ $invoiceStatusData['avgDueDays'] }}</div>
                </div>
                <div class="col">
                    <div class="small text-muted">Collection Rate</div>
                    <div class="h3">{{ $invoiceStatusData['collectionRate'] }}%</div>
                </div>
            </div>
        </div>
    </div>
</div>
