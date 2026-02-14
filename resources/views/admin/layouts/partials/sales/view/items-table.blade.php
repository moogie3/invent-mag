<div class="mb-4 pb-4">
    <div class="row align-items-center mb-4">
        <div class="col">
            <h4 class="card-title mb-0">
                <i class="ti ti-box me-2 text-primary"></i>{{ __('messages.sales_order_items_title') }}
            </h4>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead class="text-center">
                <tr>
                    <th class="text-center" style="width: 60px">{{ __('messages.table_no') }}</th>
                    <th>{{ __('messages.table_product') }}</th>
                    <th class="text-center" style="width: 100px">{{ __('messages.table_qty') }}</th>
                    <th class="text-end" style="width: 140px">{{ __('messages.table_price') }}</th>
                    <th class="text-end" style="width: 160px">{{ __('messages.table_discount') }}</th>
                    <th class="text-end" style="width: 140px">{{ __('messages.table_amount') }}</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $summary = \App\Helpers\SalesHelper::calculateInvoiceSummary(
                        $sales->salesItems,
                        $sales->order_discount ?? 0,
                        $sales->order_discount_type ?? __('messages.percentage'),
                        $sales->tax_rate ?? 0,
                    );
                @endphp

                @foreach ($sales->salesItems as $index => $item)
                    @php
                        $finalAmount = \App\Helpers\SalesHelper::calculateTotal(
                            $item->customer_price,
                            $item->quantity,
                            $item->discount,
                            $item->discount_type,
                        );

                        $returnedQty = $sales->salesReturns
                            ->where('status', 'Completed')
                            ->flatMap(fn($sr) => $sr->items)
                            ->where('product_id', $item->product_id)
                            ->sum('quantity');
                    @endphp

                    <tr>
                        <td class="text-center">{{ $index + 1 }}</td>
                        <td>
                            <div class="fw-bold">{{ $item->product->name }}</div>
                            @if (isset($item->product->sku) && $item->product->sku)
                                <small class="text-muted">{{ __('messages.table_sku') }} {{ $item->product->sku }}</small>
                            @endif
                        </td>
                        <td class="text-center">
                            {{ $item->quantity }}
                            @if($returnedQty > 0)
                                <br><small class="text-danger">(-{{ $returnedQty }} {{ __('messages.returned') }})</small>
                            @endif
                        </td>
                        <td class="text-end">
                            {{ \App\Helpers\CurrencyHelper::formatWithPosition($item->customer_price) }}
                        </td>
                        <td class="text-end">
                            @if ($item->discount > 0)
                                @if ($item->discount_type === 'percentage')
                                    <span class="text-danger">{{ $item->discount }}%</span>
                                    <br><small class="text-muted">({{ \App\Helpers\CurrencyHelper::formatWithPosition(($item->customer_price * $item->discount) / 100) }} {{ __('messages.per_unit') }})</small>
                                @else
                                    <span class="text-danger">{{ \App\Helpers\CurrencyHelper::formatWithPosition($item->discount) }}</span>
                                    <br><small class="text-muted">({{ __('messages.fixed') }})</small>
                                @endif
                            @else
                                -
                            @endif
                        </td>
                        <td class="text-end fw-bold">
                            {{ \App\Helpers\CurrencyHelper::formatWithPosition($finalAmount) }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
