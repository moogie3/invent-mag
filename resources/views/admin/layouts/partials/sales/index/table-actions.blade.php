<div class="dropdown">
    <button class="btn dropdown-toggle align-text-top" data-bs-toggle="dropdown" data-bs-boundary="viewport">
        Actions
    </button>
    <div class="dropdown-menu">
        <a href="javascript:void(0)" onclick="loadSalesDetails('{{ $sale->id }}')" data-bs-toggle="modal"
            data-bs-target="#viewSalesModal" class="dropdown-item">
            <i class="ti ti-zoom-scan me-2"></i> View
        </a>

        <a href="{{ route('admin.sales.edit', $sale->id) }}" class="dropdown-item">
            <i class="ti ti-edit me-2"></i> Edit
        </a>

        <button type="button" class="dropdown-item text-danger" data-bs-toggle="modal" data-bs-target="#deleteModal"
            onclick="setDeleteFormAction('{{ route('admin.sales.destroy', $sale->id) }}')">
            <i class="ti ti-trash me-2"></i> Delete
        </button>
    </div>
</div>
