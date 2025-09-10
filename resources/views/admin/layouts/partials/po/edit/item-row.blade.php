@php
    $finalAmount = \App\Helpers\PurchaseHelper::calculateTotal(
        $item->price,
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
    <td>
        <input type="number" name="items[{{ $item->id }}][quantity]" value="{{ $item->quantity }}"
            class="form-control text-end quantity-input" data-item-id="{{ $item->id }}" min="1" />
    </td>
    <td>
        <input type="number" name="items[{{ $item->id }}][price]" value="{{ intval($item->price) }}"
            class="form-control text-end price-input" data-item-id="{{ $item->id }}" step="0"
            min="0" />
    </td>
    <td>
        <div class="input-group">
            <input type="number" name="items[{{ $item->id }}][discount]" value="{{ (float) $item->discount }}"
                class="form-control text-end discount-input" style="min-width: 80px;" step="0" min="0"
                data-item-id="{{ $item->id }}" />

            <select name="items[{{ $item->id }}][discountType]" class="form-select discount-type-input"
                style="min-width: 70px;" data-item-id="{{ $item->id }}">
                <option value="percentage" {{ $item->discount_type === 'percentage' ? 'selected' : '' }}>
                    %</option>
                <option value="fixed" {{ $item->discount_type === 'fixed' ? 'selected' : '' }}>
                    Fixed</option>
            </select>
        </div>
    </td>
    <td>
        <input type="text" name="items[{{ $item->id }}][amount]" value="{{ intval($finalAmount) }}"
            class="form-control text-end amount-input" data-item-id="{{ $item->id }}" readonly />
    </td>
</tr>
