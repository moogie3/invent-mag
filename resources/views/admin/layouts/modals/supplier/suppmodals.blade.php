{{-- MODAL --}}
<div class="modal modal-blur fade" id="deleteModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
        <div class="modal-content border-0 shadow-lg rounded-3">
            <div class="modal-header">
                <h5 class="modal-title">{{ __('messages.supplier_modal_delete_title') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-status bg-danger"></div>
            <div class="modal-body text-center py-4">
                <i class="ti ti-alert-triangle" style="font-size: 3rem;"></i>
                <h3>{{ __('messages.are_you_sure') }}</h3>
                <div class="text-muted">{{ __('messages.supplier_modal_delete_warning') }}</div>
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

{{-- Create Supplier Modal --}}
<div class="modal fade" id="createSupplierModal" tabindex="-1" aria-labelledby="createSupplierModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-3">
            <div class="modal-header">
                <h5 class="modal-title" id="createSupplierModalLabel"><i class="ti ti-plus me-1"></i> {{ __('messages.supplier_create_supplier') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="createSupplierForm" action="{{ route('admin.supplier.store') }}" method="POST" enctype="multipart/form-data" novalidate data-validate-form>
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="supplierCode" class="form-label">{{ __('messages.table_code') }}</label>
                        <input type="text" class="form-control @error('code') is-invalid @enderror" id="supplierCode" name="code">
                        @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label for="supplierName" class="form-label">{{ __('messages.table_name') }}</label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" id="supplierName" name="name" required>
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label for="supplierAddress" class="form-label">{{ __('messages.table_address') }}</label>
                        <input type="text" class="form-control @error('address') is-invalid @enderror" id="supplierAddress" name="address">
                        @error('address')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label for="supplierPhone" class="form-label">{{ __('messages.supplier_modal_phone_number') }}</label>
                        <input type="text" class="form-control @error('phone_number') is-invalid @enderror" id="supplierPhone" name="phone_number">
                        @error('phone_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label for="supplierEmail" class="form-label">{{ __('messages.table_email') }}</label>
                        <input type="email" class="form-control @error('email') is-invalid @enderror" id="supplierEmail" name="email">
                        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label for="supplierImage" class="form-label">{{ __('messages.table_image') }}</label>
                        <input type="file" class="form-control @error('image') is-invalid @enderror" id="supplierImage" name="image" accept="image/*">
                        @error('image')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label for="supplierLocation" class="form-label">{{ __('messages.table_location') }}</label>
                        <select class="form-select @error('location') is-invalid @enderror" id="supplierLocation" name="location">
                            <option value="IN">IN</option>
                            <option value="OUT">OUT</option>
                        </select>
                        @error('location')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label for="supplierPaymentTerms" class="form-label">{{ __('messages.table_payment_terms') }}</label>
                        <input type="text" class="form-control @error('payment_terms') is-invalid @enderror" id="supplierPaymentTerms" name="payment_terms">
                        @error('payment_terms')<div class="invalid-feedback">{{ $message }}</div>@enderror
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

{{-- Edit Supplier Modal --}}
<div class="modal fade" id="editSupplierModal" tabindex="-1" aria-labelledby="editSupplierModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-3">
            <div class="modal-header">
                <h5 class="modal-title" id="editSupplierModalLabel"><i class="ti ti-edit me-1"></i> {{ __('messages.supplier_modal_edit_title') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editSupplierForm" method="POST" enctype="multipart/form-data" novalidate data-validate-form>
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <input type="hidden" id="supplierId" name="id">
                    <div class="mb-3">
                        <label for="supplierCodeEdit" class="form-label">{{ __('messages.table_code') }}</label>
                        <input type="text" class="form-control @error('code') is-invalid @enderror" id="supplierCodeEdit" name="code">
                        @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label for="supplierNameEdit" class="form-label">{{ __('messages.table_name') }}</label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" id="supplierNameEdit" name="name" required>
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label for="supplierAddressEdit" class="form-label">{{ __('messages.table_address') }}</label>
                        <input type="text" class="form-control @error('address') is-invalid @enderror" id="supplierAddressEdit" name="address">
                        @error('address')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label for="supplierPhoneEdit" class="form-label">{{ __('messages.supplier_modal_phone_number') }}</label>
                        <input type="text" class="form-control @error('phone_number') is-invalid @enderror" id="supplierPhoneEdit" name="phone_number">
                        @error('phone_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label for="supplierEmailEdit" class="form-label">{{ __('messages.table_email') }}</label>
                        <input type="email" class="form-control @error('email') is-invalid @enderror" id="supplierEmailEdit" name="email">
                        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3 text-center" id="currentSupplierImageContainer">
                        {{-- Image or icon will be displayed here by JavaScript --}}
                    </div>
                    <div class="mb-3">
                        <label for="supplierImageEdit" class="form-label">{{ __('messages.table_image') }}</label>
                        <input type="file" class="form-control @error('image') is-invalid @enderror" id="supplierImageEdit" name="image"
                            accept="image/*">
                        @error('image')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label for="supplierLocationEdit" class="form-label">{{ __('messages.table_location') }}</label>
                        <select class="form-select @error('location') is-invalid @enderror" id="supplierLocationEdit" name="location">
                            <option value="IN">IN</option>
                            <option value="OUT">OUT</option>
                        </select>
                        @error('location')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label for="supplierPaymentTermsEdit" class="form-label">{{ __('messages.table_payment_terms') }}</label>
                        <input type="text" class="form-control @error('payment_terms') is-invalid @enderror" id="supplierPaymentTermsEdit"
                            name="payment_terms">
                        @error('payment_terms')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('messages.cancel') }}</button>
                    <button type="submit" class="btn btn-primary">{{ __('messages.save') }}</button>
                </div>
                <input type="hidden" id="updateRouteBase" value="{{ route('admin.supplier.update', '') }}">
            </form>
        </div>
    </div>
</div>
