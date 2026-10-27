{{-- MODAL --}}
<div class="modal modal-blur fade" id="deleteModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
        <div class="modal-content border-0 shadow-lg rounded-3">
            <div class="modal-header">
                <h5 class="modal-title">{{ __('messages.customer_modal_delete_title') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-status bg-danger"></div>
            <div class="modal-body text-center py-4">
                <i class="ti ti-alert-triangle text-danger" style="font-size: 3rem;"></i>
                <h3>{{ __('messages.are_you_sure') }}</h3>
                <div class="text-muted">{{ __('messages.customer_modal_delete_warning') }}</div>
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

<div class="modal modal-blur fade" id="createCustomerModal" tabindex="-1" aria-labelledby="createCustomerModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-3">
            <div class="modal-header">
                <h5 class="modal-title" id="createCustomerModalLabel">
                    <i class="ti ti-plus me-1"></i>{{ __('messages.customer_create_customer') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="createCustomerForm" action="{{ route('admin.customer.store') }}" method="POST"
                enctype="multipart/form-data" novalidate data-validate-form>
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="customerName" class="form-label">{{ __('messages.table_name') }}</label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" id="customerName" name="name" required>
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label for="customerAddress" class="form-label">{{ __('messages.table_address') }}</label>
                        <input type="text" class="form-control @error('address') is-invalid @enderror" id="customerAddress" name="address">
                        @error('address')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label for="customerPhone" class="form-label">{{ __('messages.supplier_modal_phone_number') }}</label>
                        <input type="text" class="form-control @error('phone_number') is-invalid @enderror" id="customerPhone" name="phone_number">
                        @error('phone_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label for="customerEmail" class="form-label">{{ __('messages.table_email') }}</label>
                        <input type="email" class="form-control @error('email') is-invalid @enderror" id="customerEmail" name="email">
                        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label for="customerImage" class="form-label">{{ __('messages.table_image') }}</label>
                        <input type="file" class="form-control @error('image') is-invalid @enderror" id="customerImage" name="image">
                        @error('image')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label for="customerPaymentTerms" class="form-label">{{ __('messages.table_payment_terms') }}</label>
                        <input type="text" class="form-control @error('payment_terms') is-invalid @enderror" id="customerPaymentTerms" name="payment_terms">
                        @error('payment_terms')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    @php
                        $currentTenant = app('currentTenant');
                        $showPpnFields = $currentTenant instanceof \App\Models\Tenant && $currentTenant->isIndonesian() && $currentTenant->hasFeature('ppn');
                    @endphp
                    @if($showPpnFields)
                    <hr class="my-3">
                    <h6 class="mb-3 text-muted"><i class="ti ti-file-invoice me-1"></i>{{ __('messages.customer_tax_data') }}</h6>
                    <div class="mb-3">
                        <label for="customerNpwp" class="form-label">{{ __('messages.customer_npwp') }}</label>
                        <input type="text" class="form-control @error('npwp') is-invalid @enderror" id="customerNpwp" name="npwp" placeholder="00.000.000.0-000.000" maxlength="20">
                        @error('npwp')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('messages.customer_pkp_status') }}</label>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="customerPkpStatus" name="pkp_status" value="1">
                            <label class="form-check-label" for="customerPkpStatus">{{ __('messages.customer_pkp_registered') }}</label>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="customerPkpDate" class="form-label">{{ __('messages.customer_pkp_date') }}</label>
                        <input type="date" class="form-control @error('pkp_date') is-invalid @enderror" id="customerPkpDate" name="pkp_date">
                        @error('pkp_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label for="customerAlamatNpwp" class="form-label">{{ __('messages.customer_alamat_npwp') }}</label>
                        <textarea class="form-control @error('alamat_npwp') is-invalid @enderror" id="customerAlamatNpwp" name="alamat_npwp" rows="2"></textarea>
                        @error('alamat_npwp')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('messages.cancel') }}</button>
                    <button type="submit" class="btn btn-primary">{{ __('messages.save') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal modal-blur fade" id="editCustomerModal" tabindex="-1" aria-labelledby="editCustomerModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-3">
            <div class="modal-header">
                <h5 class="modal-title" id="editCustomerModalLabel"><i class="ti ti-edit me-1"></i> {{ __('messages.customer_modal_edit_title') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editCustomerForm" method="POST" enctype="multipart/form-data" novalidate data-validate-form>
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <input type="hidden" id="customerId" name="id">
                    <div class="mb-3">
                        <label for="customerNameEdit" class="form-label">{{ __('messages.table_name') }}</label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" id="customerNameEdit" name="name" required>
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label for="customerAddressEdit" class="form-label">{{ __('messages.table_address') }}</label>
                        <input type="text" class="form-control @error('address') is-invalid @enderror" id="customerAddressEdit" name="address">
                        @error('address')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label for="customerPhoneEdit" class="form-label">{{ __('messages.supplier_modal_phone_number') }}</label>
                        <input type="text" class="form-control @error('phone_number') is-invalid @enderror" id="customerPhoneEdit" name="phone_number">
                        @error('phone_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label for="customerEmailEdit" class="form-label">{{ __('messages.table_email') }}</label>
                        <input type="email" class="form-control @error('email') is-invalid @enderror" id="customerEmailEdit" name="email">
                        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3 text-center" id="currentCustomerImageContainer">
                        {{-- Image or icon will be displayed here by JavaScript --}}
                    </div>
                    <div class="mb-3">
                        <label for="customerImageEdit" class="form-label">{{ __('messages.table_image') }}</label>
                        <input type="file" class="form-control @error('image') is-invalid @enderror" id="customerImageEdit" name="image">
                        @error('image')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label for="customerPaymentTermsEdit" class="form-label">{{ __('messages.table_payment_terms') }}</label>
                        <input type="text" class="form-control @error('payment_terms') is-invalid @enderror" id="customerPaymentTermsEdit"
                            name="payment_terms">
                        @error('payment_terms')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    @if($showPpnFields)
                    <hr class="my-3">
                    <h6 class="mb-3 text-muted"><i class="ti ti-file-invoice me-1"></i>{{ __('messages.customer_tax_data') }}</h6>
                    <div class="mb-3">
                        <label for="customerNpwpEdit" class="form-label">{{ __('messages.customer_npwp') }}</label>
                        <input type="text" class="form-control @error('npwp') is-invalid @enderror" id="customerNpwpEdit" name="npwp" placeholder="00.000.000.0-000.000" maxlength="20">
                        @error('npwp')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('messages.customer_pkp_status') }}</label>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="customerPkpStatusEdit" name="pkp_status" value="1">
                            <label class="form-check-label" for="customerPkpStatusEdit">{{ __('messages.customer_pkp_registered') }}</label>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="customerPkpDateEdit" class="form-label">{{ __('messages.customer_pkp_date') }}</label>
                        <input type="date" class="form-control @error('pkp_date') is-invalid @enderror" id="customerPkpDateEdit" name="pkp_date">
                        @error('pkp_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label for="customerAlamatNpwpEdit" class="form-label">{{ __('messages.customer_alamat_npwp') }}</label>
                        <textarea class="form-control @error('alamat_npwp') is-invalid @enderror" id="customerAlamatNpwpEdit" name="alamat_npwp" rows="2"></textarea>
                        @error('alamat_npwp')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('messages.cancel') }}</button>
                    <button type="submit" class="btn btn-primary">{{ __('messages.save') }}</button>
                </div>
                <input type="hidden" id="updateRouteBase" value="{{ route('admin.customer.update', '') }}">
            </form>
        </div>
    </div>
</div>
