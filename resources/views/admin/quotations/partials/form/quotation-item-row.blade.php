<tr class="product-row" data-product-id="{{ $item->product_id }}" data-row-id="{{ $item->id ?? $index }}">
    <td class="text-center">{{ $index + 1 }}</td>
    <td>
        <input type="hidden" name="items[{{ $item->id ?? $index }}][product_id]" value="{{ $item->product_id }}">
        <div class="strong">{{ $item->product_name ?? $item->name ?? \App\Models\Product::find($item->product_id ?? null)?->name ?? __('messages.not_available') }}</div>
    </td>
    <td>
        <input type="number" name="items[{{ $item->id ?? $index }}][quantity]" value="{{ $item->quantity }}"
            class="form-control text-end item-quantity" min="1" />
    </td>
    <td>
        <input type="number" name="items[{{ $item->id ?? $index }}][price]" value="{{ $item->price }}"
            class="form-control text-end item-price" step="1" min="0" />
    </td>
    <td>
        <div class="input-group">
            <input type="number" name="items[{{ $item->id ?? $index }}][discount]" value="{{ (float) $item->discount }}"
                class="form-control text-end item-discount" style="min-width: 80px;" step="1" min="0" />
            <select name="items[{{ $item->id ?? $index }}][discount_type]" class="form-select item-discount-type" style="min-width: 70px;">
                <option value="percentage" {{ ($item->discount_type ?? 'percentage') === 'percentage' ? 'selected' : '' }}>{{ __('messages.percentage') }}</option>
                <option value="fixed" {{ ($item->discount_type ?? '') === 'fixed' ? 'selected' : '' }}>{{ __('messages.fixed') }}</option>
            </select>
        </div>
    </td>
    <td>
        <input type="text" class="form-control text-end item-subtotal" value="{{ number_format($item->total ?? 0, 0) }}" readonly />
    </td>
    <td class="text-center">
        <button type="button" class="btn btn-danger btn-sm remove-item-btn" title="{{ __('messages.delete') }}">
            <i class="ti ti-trash"></i>
        </button>
    </td>
</tr>
