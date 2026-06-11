<tr class="product-row" data-product-id="{{ $item->product_id }}" data-row-id="{{ $item->id ?? $index }}">
    <td class="text-center">{{ $index + 1 }}</td>
    <td>
        <div class="strong">{{ $item->product_name ?? $item->product?->name ?? __('messages.not_available') }}</div>
        @if($item->product?->sku)
            <small class="text-muted">{{ __('messages.sku_colon') }} {{ $item->product->sku }}</small>
        @endif
    </td>
    <td>
        <input type="number" name="items[{{ $item->id ?? $index }}][quantity]" value="{{ $item->quantity }}"
            class="form-control text-end quantity-input" min="1" />
    </td>
    <td>
        <input type="number" name="items[{{ $item->id ?? $index }}][price]" value="{{ $item->price }}"
            class="form-control text-end price-input" step="1" min="0" />
    </td>
    <td>
        <div class="input-group">
            <input type="number" name="items[{{ $item->id ?? $index }}][discount]" value="{{ (float) $item->discount }}"
                class="form-control text-end discount-input" style="min-width: 80px;" step="1" min="0" />
            <select name="items[{{ $item->id ?? $index }}][discount_type]" class="form-select discount-type-input" style="min-width: 70px;">
                <option value="percentage" {{ ($item->discount_type ?? 'percentage') === 'percentage' ? 'selected' : '' }}>{{ __('messages.percentage') }}</option>
                <option value="fixed" {{ ($item->discount_type ?? '') === 'fixed' ? 'selected' : '' }}>{{ __('messages.fixed') }}</option>
            </select>
        </div>
    </td>
    <td>
        <input type="text" class="form-control text-end amount-input" value="{{ number_format($item->total, 0) }}" readonly />
    </td>
    <td class="text-center">
        <button type="button" class="btn btn-danger btn-sm remove-item-btn" title="{{ __('messages.delete') }}">
            <i class="ti ti-trash"></i>
        </button>
    </td>
</tr>
