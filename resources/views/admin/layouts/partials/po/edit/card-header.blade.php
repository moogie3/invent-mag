<div class="card-header">
    <div class="row align-items-center">
        <div class="col">
            @php
                $statusClass = \App\Helpers\PurchaseHelper::getStatusClass($pos->status, $pos->due_date);
            @endphp
            <div class="d-flex align-items-center">
                <div class="status-indicator {{ $statusClass }}"
                    style="width: 6px; height: 36px; border-radius: 3px; margin-right: 15px;">
                </div>
                <div>
                    <h2 class="mb-0">PO #{{ $pos->invoice }}</h2>
                    <div class="text-muted fs-5">{{ $pos->supplier->code }} -
                        {{ $pos->supplier->location ?? 'N/A' }}
                    </div>
                </div>
            </div>
        </div>
        <div class="col-auto">
            <span class="badge fs-5 p-2 {{ $statusClass }}">
                {!! \App\Helpers\PurchaseHelper::getStatusText($pos->status, $pos->due_date) !!}
            </span>
        </div>
    </div>
</div>
