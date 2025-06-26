<div class="dropdown">
    <button class="btn dropdown-toggle align-text-top" data-bs-toggle="dropdown" data-bs-boundary="viewport">
        Actions
    </button>
    <div class="dropdown-menu">
        <a href="javascript:void(0)" onclick="loadPoDetails('{{ $po->id }}')" data-bs-toggle="modal"
            data-bs-target="#viewPoModal" class="dropdown-item">
            <i class="ti ti-zoom-scan me-2"></i> View
        </a>
        <a href="{{ route('admin.po.edit', $po->id) }}" class="dropdown-item">
            <i class="ti ti-edit me-2"></i> Edit
        </a>
        <button type="button" class="dropdown-item text-danger" data-bs-toggle="modal" data-bs-target="#deleteModal"
            onclick="setDeleteFormAction('{{ route('admin.po.destroy', $po->id) }}')">
            <i class="ti ti-trash me-2"></i> Delete
        </button>
    </div>
</div>
