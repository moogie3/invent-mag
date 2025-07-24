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
                <p class="mt-3">Are you sure you want to delete this supplier?</p>
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

{{-- Create Supplier Modal --}}
<div class="modal fade" id="createSupplierModal" tabindex="-1" aria-labelledby="createSupplierModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createSupplierModalLabel">Create Supplier</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="createSupplierForm" action="{{ route('admin.supplier.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="supplierCode" class="form-label">Code</label>
                        <input type="text" class="form-control" id="supplierCode" name="code">
                    </div>
                    <div class="mb-3">
                        <label for="supplierName" class="form-label">Name</label>
                        <input type="text" class="form-control" id="supplierName" name="name">
                    </div>
                    <div class="mb-3">
                        <label for="supplierAddress" class="form-label">Address</label>
                        <input type="text" class="form-control" id="supplierAddress" name="address">
                    </div>
                    <div class="mb-3">
                        <label for="supplierPhone" class="form-label">Phone Number</label>
                        <input type="text" class="form-control" id="supplierPhone" name="phone_number">
                    </div>
                    <div class="mb-3">
                        <label for="supplierEmail" class="form-label">Email</label>
                        <input type="email" class="form-control" id="supplierEmail" name="email">
                    </div>
                    <div class="mb-3">
                        <label for="supplierImage" class="form-label">Image</label>
                        <input type="file" class="form-control" id="supplierImage" name="image" accept="image/*">
                    </div>
                    <div class="mb-3">
                        <label for="supplierLocation" class="form-label">Location</label>
                        <select class="form-select" id="supplierLocation" name="location">
                            <option value="IN">IN</option>
                            <option value="OUT">OUT</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="supplierPaymentTerms" class="form-label">Payment
                            Terms</label>
                        <input type="text" class="form-control" id="supplierPaymentTerms" name="payment_terms">
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

{{-- Edit Supplier Modal --}}
<div class="modal fade" id="editSupplierModal" tabindex="-1" aria-labelledby="editSupplierModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editSupplierModalLabel">Edit Supplier</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editSupplierForm" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <input type="hidden" id="supplierId" name="id">
                    <div class="mb-3">
                        <label for="supplierCodeEdit" class="form-label">Code</label>
                        <input type="text" class="form-control" id="supplierCodeEdit" name="code">
                    </div>
                    <div class="mb-3">
                        <label for="supplierNameEdit" class="form-label">Name</label>
                        <input type="text" class="form-control" id="supplierNameEdit" name="name">
                    </div>
                    <div class="mb-3">
                        <label for="supplierAddressEdit" class="form-label">Address</label>
                        <input type="text" class="form-control" id="supplierAddressEdit" name="address">
                    </div>
                    <div class="mb-3">
                        <label for="supplierPhoneEdit" class="form-label">Phone
                            Number</label>
                        <input type="text" class="form-control" id="supplierPhoneEdit" name="phone_number">
                    </div>
                    <div class="mb-3">
                        <label for="supplierEmailEdit" class="form-label">Email</label>
                        <input type="email" class="form-control" id="supplierEmailEdit" name="email">
                    </div>
                    <div class="mb-3 text-center" id="currentSupplierImageContainer">
                        {{-- Image or icon will be displayed here by JavaScript --}}
                    </div>
                    <div class="mb-3">
                        <label for="supplierImageEdit" class="form-label">Image</label>
                        <input type="file" class="form-control" id="supplierImageEdit" name="image"
                            accept="image/*">
                    </div>
                    <div class="mb-3">
                        <label for="supplierLocationEdit" class="form-label">Location</label>
                        <select class="form-select" id="supplierLocationEdit" name="location">
                            <option value="IN">IN</option>
                            <option value="OUT">OUT</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="supplierPaymentTermsEdit" class="form-label">Payment
                            Terms</label>
                        <input type="text" class="form-control" id="supplierPaymentTermsEdit"
                            name="payment_terms">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
                <input type="hidden" id="updateRouteBase" value="{{ route('admin.supplier.update', '') }}">
            </form>
        </div>
    </div>
</div>

<script>
    window.defaultPlaceholderUrl = "{{ asset('img/default_placeholder.png') }}";
</script>
