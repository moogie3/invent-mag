<tbody id="invoiceTableBody" class="table-tbody">
    @forelse ($products as $index => $product)
        <tr class="table-row" data-id="{{ $product->id }}">
            <td>
                <input type="checkbox" class="form-check-input row-checkbox" value="{{ $product->id }}">
            </td>
            <td class="sort-no">{{ $products->firstItem() + $index }}</td>
            <td class="sort-image" style="width:120px">
                @if ($product->image == asset('img/default_placeholder.png'))
                    <i class="ti ti-photo fs-1"
                        style="width: 80px; height: 80px; display: flex; align-items: center; justify-content: center; border: 1px solid #ccc; border-radius: 5px; margin: 0 auto;"></i>
                @else
                    <img src="{{ $product->image }}" alt="" height="80px"
                        width="80px"
                        style="display: flex; align-items: center; justify-content: center; border: 1px solid #ccc; border-radius: 5px; margin: 0 auto;">
                @endif
            </td>
            <td class="sort-code no-print">{{ $product->code }}</td>
            <td class="sort-name">{{ $product->name }}</td>
            <td class="sort-quantity no-print text-center">
                <div class="fw-bold">{{ $product->stock_quantity }}</div>
                @php
                    [$badgeClass, $badgeText] = \App\Helpers\ProductHelper::getStockClassAndText($product->stock_quantity, $product->low_stock_threshold);
                @endphp
                @if ($badgeClass)
                    <span class="{{ $badgeClass }}">{{ $badgeText }}</span>
                    @if ($product->low_stock_threshold)
                        <small class="d-block text-muted">{{ __('messages.threshold_colon') }} {{ $product->low_stock_threshold }}</small>
                    @endif
                @endif
            </td>
            <td class="sort-category no-print">{{ $product->category->name }}</td>
            <td class="sort-unit">{{ $product->unit->symbol }}</td>
            <td class="sort-price text-center">
                {{ \App\Helpers\CurrencyHelper::formatWithPosition($product->price) }}
            </td>
            <td class="sort-sellingprice text-center">
                {{ \App\Helpers\CurrencyHelper::formatWithPosition($product->selling_price) }}
            </td>
            <td class="sort-supplier text-center">{{ $product->supplier->name }}</td>
            <td class="no-print" style="text-align:center">
                @include('admin.layouts.partials.product.index.action-dropdown', compact('product'))
            </td>
        </tr>
    @empty
        <tr>
            <td colspan="12">
                <div class="empty">
                    <div class="empty-img">
                        <i class="ti ti-mood-sad" style="font-size: 5rem; color: #ccc;"></i>
                    </div>
                    <p class="empty-title">{{ __('messages.no_products_found') }}</p>
                    <p class="empty-subtitle text-muted">
                        {{ __('messages.it_looks_like_you_havent_added_any_products_yet') }}
                    </p>
                </div>
            </td>
        </tr>
    @endforelse
</tbody>
