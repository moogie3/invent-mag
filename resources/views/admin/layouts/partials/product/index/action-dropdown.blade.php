<div class="dropdown">
    <button class="btn dropdown-toggle align-text-top" data-bs-toggle="dropdown" data-bs-boundary="viewport">
        {{ __('messages.actions') }}
    </button>
    <div class="dropdown-menu">
        <a href="javascript:void(0)" onclick="loadProductDetails('{{ $product->id }}')" data-bs-toggle="modal"
            data-bs-target="#viewProductModal" class="dropdown-item">
            <i class="ti ti-zoom-scan me-2"></i> {{ __('messages.view') }}
        </a>
        <a href="{{ route('admin.product.edit', $product->id) }}" class="dropdown-item">
            <i class="ti ti-edit me-2"></i> {{ __('messages.edit') }}
        </a>
        <a href="javascript:void(0)" onclick="openAdjustStockModal('{{ $product->id }}', '{{ $product->name }}', '{{ $product->stock_quantity }}')" class="dropdown-item">
            <i class="ti ti-arrows-diff me-2"></i> {{ __('messages.adjust_stock') }}
        </a>
        <button type="button" class="dropdown-item text-danger" data-bs-toggle="modal" data-bs-target="#deleteModal"
            onclick="setDeleteFormAction('{{ route('admin.product.destroy', $product->id) }}')">
            <i class="ti ti-trash me-2"></i> {{ __('messages.delete') }}
        </button>
    </div>
</div>