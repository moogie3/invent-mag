{{-- MODAL --}}
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-danger" id="deleteModalLabel">Confirm Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center">
                <i class="ti ti-alert-circle icon text-danger icon-lg mb-10"></i>
                <p class="mt-3">Are you sure you want to delete this Warehouse?</p>
                <p class="text-danger" id="mainWarehouseWarning" style="display: none;">
                    <strong>Warning:</strong> You cannot delete the main warehouse. Please set another warehouse as main
                    first.
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form id="deleteForm" method="POST">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Delete</button>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="mainWarehouseErrorModal" tabindex="-1" aria-labelledby="mainWarehouseErrorModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-danger" id="mainWarehouseErrorModalLabel">Main Warehouse Error</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center">
                <i class="ti ti-alert-circle icon text-danger icon-lg mb-10"></i>
                <p class="mt-3">There is already a main warehouse defined. Please unset the current main warehouse
                    first.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="createWarehouseModal" tabindex="-1" aria-labelledby="createWarehouseModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createWarehouseModalLabel">Create Warehouse</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="createWarehouseForm" action="{{ route('admin.warehouse.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="warehouseName" class="form-label">Name</label>
                        <input type="text" class="form-control" id="warehouseName" name="name">
                    </div>
                    <div class="mb-3">
                        <label for="warehouseAddress" class="form-label">Address</label>
                        <input type="text" class="form-control" id="warehouseAddress" name="address">
                    </div>
                    <div class="mb-3">
                        <label for="warehouseDescription" class="form-label">Description</label>
                        <textarea class="form-control" id="warehouseDescription" name="description" rows="3"></textarea>
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="isMainWarehouse" name="is_main"
                            value="1">
                        <label class="form-check-label" for="isMainWarehouse">Set as Main Warehouse</label>
                        <div class="form-text">Main warehouse will be the default for inventory operations.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="editWarehouseModal" tabindex="-1" aria-labelledby="editWarehouseModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editWarehouseModalLabel">Edit Warehouse</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editWarehouseForm" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <input type="hidden" id="warehouseId" name="id">

                    <div class="mb-3">
                        <label for="warehouseNameEdit" class="form-label">Name</label>
                        <input type="text" class="form-control" id="warehouseNameEdit" name="name">
                    </div>
                    <div class="mb-3">
                        <label for="warehouseAddressEdit" class="form-label">Address</label>
                        <input type="text" class="form-control" id="warehouseAddressEdit" name="address">
                    </div>
                    <div class="mb-3">
                        <label for="warehouseDescriptionEdit" class="form-label">Description</label>
                        <textarea class="form-control" id="warehouseDescriptionEdit" name="description" rows="3"></textarea>
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="isMainWarehouseEdit" name="is_main"
                            value="1">
                        <label class="form-check-label" for="isMainWarehouseEdit">Set as Main Warehouse</label>
                        <div class="form-text">Main warehouse will be the default for inventory operations.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save</button>
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
            console.log('Warehouse ID:', id);
            console.log('Update URL:', updateUrl);
            modal.find('form').attr('action', updateUrl);
        });

        // For Delete Modal - check if it's main warehouse
        $('#deleteModal').on('show.bs.modal', function(event) {
            var button = $(event.relatedTarget);
            var targetRow = button.closest('tr');
            var isMainCell = targetRow.find('.sort-is-main');

            // Check if the warehouse is main
            if (isMainCell.find('.badge.bg-success').length > 0) {
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
