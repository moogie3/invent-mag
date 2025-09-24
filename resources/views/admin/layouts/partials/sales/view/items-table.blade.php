<div class="card border mb-4">
    <div class="card-header py-2">
        <h4 class="card-title mb-0"><i class="ti ti-list me-2 text-info"></i>{{ __('messages.order_items') }}</h4>
    </div>
    <div class="table-responsive">
        <table class="table card-table table-vcenter table-hover">
            <thead>
                <tr>
                    <th class="text-center" style="width: 60px">{{ __('messages.no') }}</th>
                    <th>{{ __('messages.product') }}</th>
                    <th class="text-center" style="width: 100px">{{ __('messages.qty') }}</th>
                    <th class="text-end" style="width: 140px">{{ __('messages.price') }}</th>
                    <th class="text-end" style="width: 140px">{{ __('messages.discount') }}</th>
                    <th class="text-end" style="width: 140px">{{ __('messages.amount') }}</th>
                </tr>
            </thead>
            <tbody>
                @php
                    // Use the SalesHelper to calculate summary info
                    // Fix the parameter names to match SalesHelper::calculateInvoiceSummary method
                    $summary = \App\Helpers\SalesHelper::calculateInvoiceSummary(
                        $sales->salesItems,
                        $sales->order_discount ?? 0,
                        $sales->order_discount_type ?? 'percentage',
                        $sales->tax_rate ?? 0,
                    );
                @endphp

                @foreach ($sales->salesItems as $index => $item)
                    @php
                        // Fix: Use customer_price instead of price for the calculation
                        $finalAmount = \App\Helpers\SalesHelper::calculateTotal(
                            $item->customer_price,
                            $item->quantity,
                            $item->discount,
                            $item->discount_type,
                        );
                    @endphp

                    <tr>
                        <td class="text-center">{{ $index + 1 }}</td>
                        <td>
                            <div class="strong">{{ $item->product->name }}</div>
                            @if (isset($item->product->sku) && $item->product->sku)
                                <small class="text-muted">{{ __('messages.sku') }}: {{ $item->product->sku }}</small>
                            @endif
                        </td>
                        <td class="text-center">{{ $item->quantity }}</td>
                        <td class="text-end">
                            {{ \App\Helpers\CurrencyHelper::formatWithPosition($item->customer_price) }}
                        </td>
                        <td class="text-end">
                            @if ($item->discount > 0)
                                <span class="text-danger">
                                    {{ $item->discount_type === 'percentage' ? $item->discount . '%' : \App\Helpers\CurrencyHelper::formatWithPosition($item->discount) }}
                                </span>
                            @else
                                {{ __('messages.not_available') }}
                            @endif
                        </td>
                        <td class="text-end">
                            {{ \App\Helpers\CurrencyHelper::formatWithPosition($finalAmount) }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
