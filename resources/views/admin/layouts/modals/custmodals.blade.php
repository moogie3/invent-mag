{{-- MODAL --}}
<div class="modal modal-blur fade" id="deleteModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Delete Customer</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-status bg-danger"></div>
            <div class="modal-body text-center py-4">
                <i class="ti ti-alert-triangle" style="font-size: 3rem;"></i>
                <h3>Are you sure?</h3>
                <div class="text-muted">Do you really want to remove this customer? This action cannot be undone.</div>
            </div>
            <div class="modal-footer">
                <div class="w-100">
                    <div class="row">
                        <div class="col">
                            <button type="button" class="btn w-100" data-bs-dismiss="modal">
                                Cancel
                            </button>
                        </div>
                        <div class="col">
                            <form id="deleteForm" method="POST">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger w-100">Delete</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="createCustomerModal" tabindex="-1" aria-labelledby="createCustomerModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createCustomerModalLabel">
                    Create Customer</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="createCustomerForm" action="{{ route('admin.customer.store') }}" method="POST"
                enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="customerName" class="form-label">Name</label>
                        <input type="text" class="form-control" id="customerName" name="name">
                    </div>
                    <div class="mb-3">
                        <label for="customerAddress" class="form-label">Address</label>
                        <input type="text" class="form-control" id="customerAddress" name="address">
                    </div>
                    <div class="mb-3">
                        <label for="customerPhone" class="form-label">Phone Number</label>
                        <input type="text" class="form-control" id="customerPhone" name="phone_number">
                    </div>
                    <div class="mb-3">
                        <label for="customerEmail" class="form-label">Email</label>
                        <input type="email" class="form-control" id="customerEmail" name="email">
                    </div>
                    <div class="mb-3">
                        <label for="customerImage" class="form-label">Image</label>
                        <input type="file" class="form-control" id="customerImage" name="image">
                    </div>
                    <div class="mb-3">
                        <label for="customerPaymentTerms" class="form-label">Payment
                            Terms</label>
                        <input type="text" class="form-control" id="customerPaymentTerms" name="payment_terms">
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

<div class="modal fade" id="editCustomerModal" tabindex="-1" aria-labelledby="editCustomerModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editCustomerModalLabel">Edit Customer</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editCustomerForm" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <input type="hidden" id="customerId" name="id">
                    <div class="mb-3">
                        <label for="customerNameEdit" class="form-label">Name</label>
                        <input type="text" class="form-control" id="customerNameEdit" name="name">
                    </div>
                    <div class="mb-3">
                        <label for="customerAddressEdit" class="form-label">Address</label>
                        <input type="text" class="form-control" id="customerAddressEdit" name="address">
                    </div>
                    <div class="mb-3">
                        <label for="customerPhoneEdit" class="form-label">Phone Number</label>
                        <input type="text" class="form-control" id="customerPhoneEdit" name="phone_number">
                    </div>
                    <div class="mb-3">
                        <label for="customerEmailEdit" class="form-label">Email</label>
                        <input type="email" class="form-control" id="customerEmailEdit" name="email">
                    </div>
                    <div class="mb-3 text-center" id="currentCustomerImageContainer">
                        {{-- Image or icon will be displayed here by JavaScript --}}
                    </div>
                    <div class="mb-3">
                        <label for="customerImageEdit" class="form-label">Image</label>
                        <input type="file" class="form-control" id="customerImageEdit" name="image">
                    </div>
                    <div class="mb-3">
                        <label for="customerPaymentTermsEdit" class="form-label">Payment
                            Terms</label>
                        <input type="text" class="form-control" id="customerPaymentTermsEdit"
                            name="payment_terms">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
                <input type="hidden" id="updateRouteBase" value="{{ route('admin.customer.update', '') }}">
            </form>
        </div>
    </div>
</div>

<script>
    window.defaultPlaceholderUrl = "{{ asset('img/default_placeholder.png') }}";
</script>
