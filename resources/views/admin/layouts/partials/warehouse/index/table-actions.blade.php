<div class="btn-list flex-nowrap justify-content-center">
    <button type="button" class="btn btn-icon btn-ghost-primary view-warehouse-btn"
        data-id="{{ $wo->id }}"
        data-url="{{ route('admin.warehouse.view', $wo->id) }}"
        title="{{ __('messages.view') }}">
        <i class="ti ti-eye"></i>
    </button>

    <button type="button" class="btn btn-icon btn-ghost-primary" data-bs-toggle="modal" data-bs-target="#editWarehouseModal"
        data-id="{{ $wo->id }}" data-name="{{ $wo->name }}" data-address="{{ $wo->address }}"
        data-description="{{ $wo->description }}" data-is-main="{{ $wo->is_main }}"
        data-update-url="{{ route('admin.warehouse.update', $wo->id) }}"
        title="{{ __('messages.edit') }}">
        <i class="ti ti-edit"></i>
    </button>

    @if (!$wo->is_main)
        <button type="button" class="btn btn-icon btn-ghost-warning set-main-btn"
            data-url="{{ route('admin.warehouse.set-main', $wo->id) }}"
            title="{{ __('messages.warehouse_action_set_as_main') }}">
            <i class="ti ti-star"></i>
        </button>
    @else
        <button type="button" class="btn btn-icon btn-ghost-secondary unset-main-btn"
            data-url="{{ route('admin.warehouse.unset-main', $wo->id) }}"
            title="{{ __('messages.warehouse_action_unset_main') }}">
            <i class="ti ti-star-off"></i>
        </button>
    @endif

    <button type="button" class="btn btn-icon btn-ghost-danger" data-bs-toggle="modal" data-bs-target="#deleteModal"
        onclick="setDeleteFormAction('{{ route('admin.warehouse.destroy', $wo->id) }}')" @if($wo->is_main) disabled @endif
        title="{{ __('messages.delete') }}">
        <i class="ti ti-trash"></i>
    </button>
</div>
