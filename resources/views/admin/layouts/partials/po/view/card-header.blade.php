<div class="card-header">
    @php
        $statusClass = \App\Helpers\PurchaseHelper::getStatusClass($pos->status, $pos->due_date);
    @endphp
    <div class="row align-items-center w-100">
        <div class="col">
            <div class="d-flex align-items-center">
                <div class="status-indicator {{ $statusClass }}"
                    style="width: 6px; height: 36px; border-radius: 3px; margin-right: 15px;">
                </div>
                <div>
                    <h2 class="mb-0">{{ __('messages.po_hash') }}{{ $pos->invoice }}</h2>
                    <div class="text-muted fs-5">
                        {{ $pos->supplier->code }} - {{ $pos->supplier->location ?? __('messages.not_available') }}
                    </div>
                </div>
            </div>
        </div>
        <div class="col-auto">
            <span class="badge fs-6 {{ $statusClass }}">
                {!! \App\Helpers\PurchaseHelper::getStatusText($pos->status, $pos->due_date) !!}
            </span>
        </div>
    </div>
</div>
