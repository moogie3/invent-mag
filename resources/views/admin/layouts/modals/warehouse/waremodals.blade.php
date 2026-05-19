{{-- MODAL --}}
<div class="modal modal-blur fade" id="deleteModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
        <div class="modal-content border-0 shadow-lg rounded-3">
            <div class="modal-header">
                <h5 class="modal-title">{{ __('messages.warehouse_modal_delete_title') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-status bg-danger"></div>
            <div class="modal-body text-center py-4">
                <i class="ti ti-alert-triangle text-danger" style="font-size: 3rem;"></i>
                <h3>{{ __('messages.are_you_sure') }}</h3>
                <div class="text-muted">{{ __('messages.warehouse_modal_delete_warning') }}</div>
                <p class="text-danger mt-2" id="mainWarehouseWarning" style="display: none;">
                    <strong>{{ __('messages.warehouse_modal_delete_main_warning_title') }}</strong> {{ __('messages.warehouse_modal_delete_main_warning_message') }}
                </p>
            </div>
            <div class="modal-footer">
                <div class="w-100">
                    <div class="row">
                        <div class="col">
                            <button type="button" class="btn w-100" data-bs-dismiss="modal">
                                {{ __('messages.cancel') }}
                            </button>
                        </div>
                        <div class="col">
                            <form id="deleteForm" method="POST">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger w-100">{{ __('messages.delete') }}</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- View Warehouse Modal -->
<div class="modal fade" id="viewWarehouseModal" tabindex="-1" aria-labelledby="viewWarehouseModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content border-0 shadow-lg rounded-3">
            <div class="modal-header">
                <h5 class="modal-title fs-3" id="viewWarehouseModalLabel">
                    <i class="ti ti-building-warehouse me-2"></i>{{ __('messages.warehouse_details') }}
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row g-4 mb-4">
                    <div class="col-md-6">
                        <div class="bg-body rounded-3 p-4 border">
                            <div class="text-muted mb-2">{{ __('messages.table_name') }}</div>
                            <div class="fw-bold fs-4" id="viewWarehouseName"></div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="bg-body rounded-3 p-4 border">
                            <div class="text-muted mb-2">{{ __('messages.table_address') }}</div>
                            <div class="fs-4" id="viewWarehouseAddress"></div>
                        </div>
                    </div>
                </div>

                <div class="row g-4 mb-4">
                    <div class="col-12">
                        <div class="bg-body rounded-3 p-4 border">
                            <div class="text-muted mb-2">{{ __('messages.table_description') }}</div>
                            <div class="fs-4" id="viewWarehouseDescription"></div>
                        </div>
                    </div>
                </div>

                <div class="border-top pt-4 mt-4" style="border-color: var(--tblr-border-color, #dee6ed) !important;">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div class="d-flex align-items-center">
                            <i class="ti ti-packages text-muted me-3 fs-3"></i>
                            <h6 class="mb-0 fw-bold fs-3">{{ __('messages.product_title') }}</h6>
                            <span class="badge bg-primary-lt text-primary ms-3 fs-4" id="viewWarehouseTotalProducts">0</span>
                        </div>
                        <div class="text-muted fs-4">
                            {{ __('messages.total_stock') }}: <span class="fw-bold" id="viewWarehouseTotalStock">-</span>
                        </div>
                    </div>
                    
                    <div class="table-responsive" style="max-height: 500px; overflow-y: auto;">
                        <table class="table table-sm table-hover mb-0">
                            <thead class="table-light sticky-top">
                                <tr>
                                    <th class="fs-4">{{ __('messages.table_product') }}</th>
                                    <th class="fs-4">{{ __('messages.table_code') }}</th>
                                    <th class="fs-4">PO</th>
                                    <th class="text-center fs-4">{{ __('messages.table_qty') }}</th>
                                    <th class="text-center fs-4">Expiry</th>
                                </tr>
                            </thead>
                            <tbody id="viewWarehouseProductsList">
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4 fs-4">
                                        <i class="ti ti-loader me-2 fs-4"></i>Loading products...
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-lg" data-bs-dismiss="modal">{{ __('messages.close') }}</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="mainWarehouseErrorModal" tabindex="-1" aria-labelledby="mainWarehouseErrorModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-3">
            <div class="modal-header">
                <h5 class="modal-title text-danger" id="mainWarehouseErrorModalLabel">{{ __('messages.warehouse_modal_main_error_title') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center">
                <i class="ti ti-alert-circle icon text-danger icon-lg mb-10"></i>
                <p class="mt-3">{{ __('messages.warehouse_modal_main_error_message') }}</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('messages.close') }}</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="createWarehouseModal" tabindex="-1" aria-labelledby="createWarehouseModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-3">
            <div class="modal-header">
                <h5 class="modal-title" id="createWarehouseModalLabel"><i class="ti ti-plus me-1"></i> {{ __('messages.warehouse_create_warehouse') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="createWarehouseForm" action="{{ route('admin.warehouse.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="warehouseName" class="form-label">{{ __('messages.table_name') }}</label>
                        <input type="text" class="form-control" id="warehouseName" name="name">
                    </div>
                    <div class="mb-3">
                        <label for="warehouseAddress" class="form-label">{{ __('messages.table_address') }}</label>
                        <input type="text" class="form-control" id="warehouseAddress" name="address">
                    </div>
                    <div class="mb-3">
                        <label for="warehouseDescription" class="form-label">{{ __('messages.table_description') }}</label>
                        <textarea class="form-control" id="warehouseDescription" name="description" rows="3"></textarea>
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="isMainWarehouse" name="is_main"
                            value="1">
                        <label class="form-check-label" for="isMainWarehouse">{{ __('messages.warehouse_modal_set_as_main_checkbox') }}</label>
                        <div class="form-text">{{ __('messages.warehouse_modal_set_as_main_description') }}</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('messages.cancel') }}</button>
                    <button type="submit" class="btn btn-primary">{{ __('messages.save') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="editWarehouseModal" tabindex="-1" aria-labelledby="editWarehouseModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-3">
            <div class="modal-header">
                <h5 class="modal-title" id="editWarehouseModalLabel"><i class="ti ti-edit me-1"></i> {{ __('messages.warehouse_modal_edit_title') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editWarehouseForm" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <input type="hidden" id="warehouseId" name="id">

                    <div class="mb-3">
                        <label for="warehouseNameEdit" class="form-label">{{ __('messages.table_name') }}</label>
                        <input type="text" class="form-control" id="warehouseNameEdit" name="name">
                    </div>
                    <div class="mb-3">
                        <label for="warehouseAddressEdit" class="form-label">{{ __('messages.table_address') }}</label>
                        <input type="text" class="form-control" id="warehouseAddressEdit" name="address">
                    </div>
                    <div class="mb-3">
                        <label for="warehouseDescriptionEdit" class="form-label">{{ __('messages.table_description') }}</label>
                        <textarea class="form-control" id="warehouseDescriptionEdit" name="description" rows="3"></textarea>
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="isMainWarehouseEdit" name="is_main"
                            value="1">
                        <label class="form-check-label" for="isMainWarehouseEdit">{{ __('messages.warehouse_modal_set_as_main_checkbox') }}</label>
                        <div class="form-text">{{ __('messages.warehouse_modal_set_as_main_description') }}</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('messages.cancel') }}</button>
                    <button type="submit" class="btn btn-primary">{{ __('messages.save') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script nonce="{{ $cspNonce ?? '' }}">
    function setDeleteFormAction(action) {
        var form = document.getElementById('deleteForm');
        if (form) form.action = action;
    }
    window.setDeleteFormAction = setDeleteFormAction;
</script>
