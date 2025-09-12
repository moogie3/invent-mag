<div class="card border mb-4">
    <div class="card-header py-2">
        <h4 class="card-title mb-0"><i class="ti ti-list me-2 text-primary"></i>Order Items</h4>
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
                    <th class="text-center" style="width: 140px">Expiry Date</th>
                </tr>
            </thead>
            <tbody>
                @php
                    // Use the helper to calculate all invoice summary figures at once
                    $summary = \App\Helpers\PurchaseHelper::calculateInvoiceSummary(
                        $pos->items,
                        $pos->discount_total,
                        $pos->discount_total_type,
                    );

                    $subtotal = $summary['subtotal'];
                    $itemCount = $summary['itemCount'];
                    $totalProductDiscount = $summary['totalProductDiscount'];
                    $orderDiscount = $summary['orderDiscount'];
                    $finalTotal = $summary['finalTotal'];
                @endphp

                @foreach ($pos->items as $index => $item)
                    @php
                        // Calculate the final amount for this item
                        $finalAmount = \App\Helpers\PurchaseHelper::calculateTotal(
                            $item->price,
                            $item->quantity,
                            $item->discount,
                            $item->discount_type,
                        );

                        // Calculate the discount per unit for display
                        $discountPerUnit = \App\Helpers\PurchaseHelper::calculateDiscountPerUnit(
                            $item->price,
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
                            {{ \App\Helpers\CurrencyHelper::formatWithPosition($item->price) }}
                        </td>
                        <td class="text-end">
                            @if ($item->discount > 0)
                                <span class="text-danger">
                                    {{ $item->discount_type === 'percentage' ? $item->discount . '%' : \App\Helpers\CurrencyHelper::formatWithPosition($item->discount) }}
                                </span>
                            @else
                                -
                            @endif
                        </td>
                        <td class="text-end">
                            {{ \App\Helpers\CurrencyHelper::formatWithPosition($finalAmount) }}
                        </td>
                        <td class="text-center">
                            @if ($item->expiry_date)
                                {{ \Carbon\Carbon::parse($item->expiry_date)->format('d M Y') }}
                            @else
                                N/A
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
