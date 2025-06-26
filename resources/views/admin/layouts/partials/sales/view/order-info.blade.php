<div class="card border-0 h-100">
    <div class="card-body p-3">
        <h4 class="card-title mb-3"><i class="ti ti-calendar-event me-2 text-info"></i>Order Information</h4>
        <div class="d-flex justify-content-between mb-2">
            <div><strong>Order Date:</strong></div>
            <div>{{ $sales->order_date->format('d F Y') }}</div>
        </div>
        <div class="d-flex justify-content-between mb-2">
            <div><strong>Due Date:</strong></div>
            <div>{{ $sales->due_date->format('d F Y') }}</div>
        </div>
        <div class="d-flex justify-content-between mb-2">
            <div><strong>Payment Type:</strong></div>
            <div>{{ $sales->payment_type ?? 'N/A' }}</div>
        </div>
        @if ($sales->status === 'Paid')
            <div class="d-flex justify-content-between">
                <div><strong>Payment Date:</strong></div>
                <div>
                    {{ $sales->payment_date->setTimezone(auth()->user()->timezone ?? 'UTC')->format('d F Y H:i') }}
                </div>
            </div>
        @endif
    </div>
</div>
