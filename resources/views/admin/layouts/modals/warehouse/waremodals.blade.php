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
                <h5 class="modal-title" id="createWarehouseModalLabel">{{ __('messages.warehouse_create_warehouse') }}</h5>
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
                <h5 class="modal-title" id="editWarehouseModalLabel">{{ __('messages.warehouse_modal_edit_title') }}</h5>
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

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // For Edit Warehouse Modal
        $('#editWarehouseModal').on('show.bs.modal', function(event) {
            var button = $(event.relatedTarget);
            var id = button.data('id');
            var name = button.data('name');
            var address = button.data('address');
            var description = button.data('description');
            var isMain = button.data('is-main');

            var modal = $(this);
            modal.find('#warehouseId').val(id);
            modal.find('#warehouseNameEdit').val(name);
            modal.find('#warehouseAddressEdit').val(address);
            modal.find('#warehouseDescriptionEdit').val(description);
            modal.find('#isMainWarehouseEdit').prop('checked', isMain == 1);

            // Set form action URL
            var updateUrl = button.data('update-url');
            // console.log('Warehouse ID:', id);
            // console.log('Update URL:', updateUrl);
            modal.find('form').attr('action', updateUrl);
        });

        // For Delete Modal - check if it's main warehouse
        $('#deleteModal').on('show.bs.modal', function(event) {
            var button = $(event.relatedTarget);
            var targetRow = button.closest('tr');
            var isMainCell = targetRow.find('.sort-is-main');

            // Check if the warehouse is main
            if (isMainCell.find('.badge.bg-green-lt').length > 0) {
                $('#mainWarehouseWarning').show();
                $('#deleteForm button[type="submit"]').prop('disabled', true);
            } else {
                $('#mainWarehouseWarning').hide();
                $('#deleteForm button[type="submit"]').prop('disabled', false);
            }
        });

        // Search functionality
        $('#searchInput').on('keyup', function() {
            var value = $(this).val().toLowerCase();
            $('#invoiceTableBody tr').filter(function() {
                $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
            });
        });
    });

    function setDeleteFormAction(action) {
        document.getElementById('deleteForm').action = action;
    }
</script>
