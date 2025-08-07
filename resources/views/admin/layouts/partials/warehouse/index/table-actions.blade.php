<div class="dropdown">
    <button class="btn dropdown-toggle align-text-top" data-bs-toggle="dropdown" data-bs-boundary="viewport">
        Actions
    </button>
    <div class="dropdown-menu">
        <a href="#" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#editWarehouseModal"
            data-id="{{ $wo->id }}" data-name="{{ $wo->name }}" data-address="{{ $wo->address }}"
            data-description="{{ $wo->description }}" data-is-main="{{ $wo->is_main }}"
            data-update-url="{{ route('admin.warehouse.update', $wo->id) }}">
            <i class="ti ti-edit me-2"></i> Edit
        </a>

        @if (!$wo->is_main)
            <a href="{{ route('admin.warehouse.set-main', $wo->id) }}" class="dropdown-item">
                <i class="ti ti-star me-2"></i> Set as Main
            </a>
        @else
            <a href="{{ route('admin.warehouse.unset-main', $wo->id) }}" class="dropdown-item">
                <i class="ti ti-star-off me-2"></i> Unset Main
            </a>
        @endif

        <button type="button" class="dropdown-item text-danger" data-bs-toggle="modal" data-bs-target="#deleteModal"
            onclick="setDeleteFormAction('{{ route('admin.warehouse.destroy', $wo->id) }}')" @if($wo->is_main) disabled @endif>
            <i class="ti ti-trash me-2"></i> Delete
        </button>
    </div>
</div>
