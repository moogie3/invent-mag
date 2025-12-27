<div class="card-header">
    <div class="row align-items-center">
        <div class="col">
            @php
                $statusClass = \App\Helpers\SalesHelper::getStatusClass($sales->status, $sales->due_date);
            @endphp
            <div class="d-flex align-items-center">
                <div class="status-indicator {{ $statusClass }}"
                    style="width: 6px; height: 36px; border-radius: 3px; margin-right: 15px;">
                </div>
                <div>
                    <h2 class="mb-0">{{ __('messages.invoice_no') }}{{ $sales->invoice }}</h2>
                    <div class="text-muted fs-5">{{ $sales->customer->address }}</div>
                </div>
            </div>
        </div>
        <div class="col-auto">
            <span class="badge fs-5 p-2 {{ $statusClass }}">
                {!! \App\Helpers\SalesHelper::getStatusText($sales->status, $sales->due_date) !!}
            </span>
        </div>
    </div>
</div>
