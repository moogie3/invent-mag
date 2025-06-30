<tbody id="invoiceTableBody" class="table-tbody">
    @foreach ($products as $index => $product)
        <tr class="table-row" data-id="{{ $product->id }}">
            <td>
                <input type="checkbox" class="form-check-input row-checkbox" value="{{ $product->id }}">
            </td>
            <td class="sort-no">{{ $products->firstItem() + $index }}</td>
            <td class="sort-image" style="width:120px">
                <img src="{{ asset($product->image) }}" width="80px" height="80px">
            </td>
            <td class="sort-code no-print">{{ $product->code }}</td>
            <td class="sort-name">{{ $product->name }}</td>
            <td class="sort-quantity no-print text-center">
                {{ $product->stock_quantity }}
                @if ($product->hasLowStock())
                    <span class="badge bg-red-lt">Low Stock</span>
                    @if ($product->low_stock_threshold)
                        <small class="d-block text-muted">Threshold: {{ $product->low_stock_threshold }}</small>
                    @endif
                @endif
            </td>
            <td class="sort-category no-print">{{ $product->category->name }}</td>
            <td class="sort-unit">{{ $product->unit->symbol }}</td>
            <td class="sort-price text-center">
                {{ \App\Helpers\CurrencyHelper::format($product->price) }}
            </td>
            <td class="sort-sellingprice text-center">
                {{ \App\Helpers\CurrencyHelper::format($product->selling_price) }}
            </td>
            <td class="sort-supplier text-center">{{ $product->supplier->name }}</td>
            <td class="sort-expiry text-center">
                @if ($product->has_expiry && $product->expiry_date)
                    {{ $product->expiry_date->format('d-m-Y') }}
                    @php
                        [$badgeClass, $badgeText] = \App\Helpers\ProductHelper::getExpiryClassAndText(
                            $product->expiry_date,
                        );
                    @endphp
                    @if ($badgeClass)
                        <span class="{{ $badgeClass }}">{{ $badgeText }}</span>
                    @endif
                @else
                    <span class="text-muted">N/A</span>
                @endif
            </td>
            <td class="no-print" style="text-align:center">
                @include('admin.layouts.partials.product.index.action-dropdown', compact('product'))
            </td>
        </tr>
    @endforeach
</tbody>
