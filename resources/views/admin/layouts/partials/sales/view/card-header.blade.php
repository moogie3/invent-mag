<div class="card-header">
    <div class="row align-items-center">
        <div class="col">
            @php
                $statusClass = \App\Helpers\SalesHelper::getStatusClass($sales->status, $sales->due_date);
            @endphp
        </div>
        <div class="d-flex align-items-start justify-content-between flex-wrap gap-2">
            <div class="d-flex align-items-center">
                <div class="status-indicator {{ $statusClass }}"
                    style="width: 6px; height: 36px; border-radius: 3px; margin-right: 15px;">
                </div>
                <div>
                    <h2 class="mb-0">Invoice #{{ $sales->invoice }}</h2>
                    <div class="text-muted fs-5">
                        {{ $sales->customer->name }} - {{ $sales->customer->address ?? 'N/A' }}
                    </div>
                </div>
            </div>
            <div class="text-end">
                <span class="badge fs-6 {{ $statusClass }}">
                    {!! \App\Helpers\SalesHelper::getStatusText($sales->status, $sales->due_date) !!}
                </span>
            </div>
        </div>
    </div>
</div>
