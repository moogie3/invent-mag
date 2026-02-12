<div class="btn-list flex-nowrap justify-content-center">
    <button type="button" class="btn btn-icon btn-ghost-primary" data-bs-toggle="modal" data-bs-target="#editWarehouseModal"
        data-id="{{ $wo->id }}" data-name="{{ $wo->name }}" data-address="{{ $wo->address }}"
        data-description="{{ $wo->description }}" data-is-main="{{ $wo->is_main }}"
        data-update-url="{{ route('admin.warehouse.update', $wo->id) }}"
        title="{{ __('messages.edit') }}">
        <i class="ti ti-edit"></i>
    </button>

    @if (!$wo->is_main)
        <a href="{{ route('admin.warehouse.set-main', $wo->id) }}" class="btn btn-icon btn-ghost-warning"
            title="{{ __('messages.warehouse_action_set_as_main') }}">
            <i class="ti ti-star"></i>
        </a>
    @else
        <a href="{{ route('admin.warehouse.unset-main', $wo->id) }}" class="btn btn-icon btn-ghost-secondary"
            title="{{ __('messages.warehouse_action_unset_main') }}">
            <i class="ti ti-star-off"></i>
        </a>
    @endif

    <button type="button" class="btn btn-icon btn-ghost-danger" data-bs-toggle="modal" data-bs-target="#deleteModal"
        onclick="setDeleteFormAction('{{ route('admin.warehouse.destroy', $wo->id) }}')" @if($wo->is_main) disabled @endif
        title="{{ __('messages.delete') }}">
        <i class="ti ti-trash"></i>
    </button>
</div>
