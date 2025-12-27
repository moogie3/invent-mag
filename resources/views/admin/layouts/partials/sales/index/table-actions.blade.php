<div class="dropdown">
    <button class="btn dropdown-toggle align-text-top" data-bs-toggle="dropdown" data-bs-boundary="viewport">
        {{ __('messages.actions') }}
    </button>
    <div class="dropdown-menu">
        @if ($sale->is_pos)
            <a href="{{ route('admin.pos.receipt', $sale->id) }}" class="dropdown-item" target="_blank">
                <i class="ti ti-receipt me-2"></i> {{ __('messages.view_receipt') }}
            </a>
        @else
            <a href="javascript:void(0)" class="dropdown-item view-sales-details-btn" data-id="{{ $sale->id }}">
                <i class="ti ti-zoom-scan me-2"></i> {{ __('messages.view_details') }}
            </a>
        @endif

        <a href="{{ route('admin.sales.edit', $sale->id) }}" class="dropdown-item">
            <i class="ti ti-edit me-2"></i> {{ __('messages.edit') }}
        </a>

        <button type="button" class="dropdown-item text-danger" data-bs-toggle="modal" data-bs-target="#deleteModal"
            onclick="setDeleteFormAction('{{ route('admin.sales.destroy', $sale->id) }}')">
            <i class="ti ti-trash me-2"></i> {{ __('messages.delete') }}
        </button>
    </div>
</div>
