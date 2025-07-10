<div class="card border mb-4">
    <div class="card-header py-2">
        <h4 class="card-title mb-0"><i class="ti ti-list me-2 text-info"></i>Order Items</h4>
    </div>
    <div class="table-responsive">
        <table class="table card-table table-vcenter table-hover">
            <thead>
                <tr>
                    <th class="text-center" style="width: 60px">No</th>
                    <th>Product</th>
                    <th class="text-center" style="width: 100px">QTY</th>
                    <th class="text-end" style="width: 140px">Price</th>
                    <th class="text-end" style="width: 140px">Discount</th>
                    <th class="text-end" style="width: 140px">Amount</th>
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
                                <small class="text-muted">SKU: {{ $item->product->sku }}</small>
                            @endif
                        </td>
                        <td class="text-center">{{ $item->quantity }}</td>
                        <td class="text-end">
                            {{ \App\Helpers\CurrencyHelper::format($item->customer_price) }}
                        </td>
                        <td class="text-end">
                            @if ($item->discount > 0)
                                <span class="text-danger">
                                    {{ $item->discount_type === 'percentage' ? $item->discount . '%' : \App\Helpers\CurrencyHelper::format($item->discount) }}
                                </span>
                            @else
                                -
                            @endif
                        </td>
                        <td class="text-end">
                            {{ \App\Helpers\CurrencyHelper::format($finalAmount) }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
