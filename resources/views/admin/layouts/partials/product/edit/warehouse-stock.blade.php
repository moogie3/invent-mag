<div class="mb-4">
    <div class="row align-items-center mb-3">
        <div class="col">
            <h4 class="mb-0">{{ __('messages.stock_by_warehouse') }}</h4>
        </div>
        <div class="col-auto">
            <a href="{{ route('admin.reports.stock-transfer') }}" class="btn btn-outline-primary btn-sm">
                <i class="ti ti-transfer me-1"></i>{{ __('messages.transfer_stock') }}
            </a>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-bordered table-vcenter">
            <thead>
                <tr>
                    <th class="fs-5">{{ __('messages.warehouse') }}</th>
                    <th class="text-center fs-5">{{ __('messages.quantity') }}</th>
                        <th class="text-center fs-5">{{ __('messages.product_low_stock_threshold') }}</th>
                    <th class="text-center fs-5">{{ __('messages.status') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($warehouses as $warehouse)
                    @php
                        $pw = $products->productWarehouses->where('warehouse_id', $warehouse->id)->first();
                        $quantity = $pw ? $pw->quantity : 0;
                        $threshold = $products->low_stock_threshold ?? 10;
                        $statusClass = $quantity <= 0 ? 'text-danger' : ($quantity <= $threshold ? 'text-warning' : 'text-success');
                        $statusText = $quantity <= 0 ? __('messages.out_of_stock') : ($quantity <= $threshold ? __('messages.low_stock') : __('messages.in_stock'));
                    @endphp
                    <tr>
                        <td>
                            {{ $warehouse->name }}
                            @if($warehouse->is_main)
                                <span class="badge bg-blue-lt ms-1">{{ __('messages.table_main') }}</span>
                            @endif
                        </td>
                        <td class="text-center fw-medium">{{ $quantity }}</td>
                        <td class="text-center">{{ $threshold }}</td>
                        <td class="text-center {{ $statusClass }} fw-medium">{{ $statusText }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-center text-muted py-4">
                            {{ __('messages.no_warehouses_available') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
