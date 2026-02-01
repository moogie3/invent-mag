<div class="card border mb-4">
    <div class="card-header py-2">
        <h4 class="card-title mb-0"><i class="ti ti-list me-2 text-primary"></i>{{ __('messages.po_order_items_title') }}</h4>
    </div>
    <div class="table-responsive">
        <table class="table card-table table-vcenter table-hover">
            <thead>
                <tr>
                    <th class="text-center" style="width: 60px">{{ __('messages.table_no') }}</th>
                    <th>{{ __('messages.table_product') }}</th>
                    <th class="text-center" style="width: 100px">{{ __('messages.table_qty') }}</th>
                    <th class="text-end" style="width: 140px">{{ __('messages.table_price') }}</th>
                    <th class="text-end" style="width: 140px">{{ __('messages.table_discount') }}</th>
                    <th class="text-end" style="width: 140px">{{ __('messages.table_amount') }}</th>
                    <th class="text-center" style="width: 140px">{{ __('messages.table_expiry_date') }}</th>
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

                        // Calculate returned quantity for this item
                        $returnedQty = $pos->purchaseReturns
                            ->where('status', 'Completed')
                            ->flatMap(fn($pr) => $pr->items)
                            ->where('product_id', $item->product_id)
                            ->sum('quantity');
                    @endphp

                    <tr>
                        <td class="text-center">{{ $index + 1 }}</td>
                        <td>
                            <div class="strong">{{ $item->product->name }}</div>
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
                            {{ \App\Helpers\CurrencyHelper::formatWithPosition($item->price) }}
                        </td>
                        <td class="text-end">
                            @if ($item->discount > 0)
                                @if ($item->discount_type === 'percentage')
                                    <span class="text-danger">{{ $item->discount }}%</span>
                                    <br><small class="text-muted">({{ \App\Helpers\CurrencyHelper::formatWithPosition(($item->price * $item->discount) / 100) }} {{ __('messages.per_unit') }})</small>
                                @else
                                    <span class="text-danger">{{ \App\Helpers\CurrencyHelper::formatWithPosition($item->discount) }}</span>
                                    <br><small class="text-muted">({{ __('messages.fixed') }})</small>
                                @endif
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
                                {{ __('messages.not_available') }}
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
