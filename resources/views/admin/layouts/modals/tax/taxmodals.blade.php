{{-- SET DEFAULT MODAL --}}
<div class="modal modal-blur fade" id="defaultTaxModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
        <div class="modal-content border-0 shadow-lg rounded-3">
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            <div class="modal-status bg-primary"></div>
            <div class="modal-body text-center py-4">
                <i class="ti ti-star text-primary mb-3" style="font-size: 3rem;"></i>
                <h3>{{ __('messages.tax_set_as_default') }}</h3>
                <div class="text-muted mb-3">{{ __('messages.tax_confirm_set_default') }}</div>
                <div class="d-flex flex-column gap-2 text-start bg-light p-3 rounded">
                    <div>
                        <small class="text-muted d-block">Current Default:</small>
                        <strong class="text-danger">{{ $defaultTax ? $defaultTax->name . ' (' . $defaultTax->rate . '%)' : 'None' }}</strong>
                    </div>
                    <div>
                        <small class="text-muted d-block">New Default:</small>
                        <strong class="text-success" id="newDefaultTaxName"></strong>
                    </div>
                </div>
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
                            <form id="setDefaultTaxFormModal" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-primary w-100">{{ __('messages.save') }}</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- DELETE MODAL --}}
<div class="modal modal-blur fade" id="deleteTaxModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
        <div class="modal-content border-0 shadow-lg rounded-3">
            <div class="modal-header">
                <h5 class="modal-title">{{ __('messages.tax_modal_delete_title') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-status bg-danger"></div>
            <div class="modal-body text-center py-4">
                <i class="ti ti-alert-triangle text-danger" style="font-size: 3rem;"></i>
                <h3>{{ __('messages.are_you_sure') }}</h3>
                <div class="text-muted">{{ __('messages.tax_modal_delete_warning') }} <strong id="deleteTaxName"></strong></div>
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
                            <form id="deleteTaxForm" method="POST">
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

{{-- CREATE MODAL --}}
<div class="modal fade" id="createTaxModal" tabindex="-1" aria-labelledby="createTaxModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-3">
            <div class="modal-header">
                <h5 class="modal-title" id="createTaxModalLabel">
                    <i class="ti ti-plus me-1"></i>{{ __('messages.tax_modal_create_title') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="createTaxForm" action="{{ route('admin.setting.tax.store') }}" method="POST" novalidate data-validate-form>
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="createTaxName" class="form-label">{{ __('messages.tax_name') }}</label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" id="createTaxName" name="name" required>
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label for="createTaxRate" class="form-label">{{ __('messages.tax_rate') }}</label>
                        <input type="number" class="form-control @error('rate') is-invalid @enderror" id="createTaxRate" name="rate" step="0.01" min="0" max="100" required>
                        @error('rate')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label for="createTaxType" class="form-label">{{ __('messages.tax_type') }}</label>
                        <select class="form-select @error('type') is-invalid @enderror" id="createTaxType" name="type" required>
                            <option value="exclusive">{{ __('messages.tax_type_exclusive') }}</option>
                            <option value="inclusive">{{ __('messages.tax_type_inclusive') }}</option>
                            <option value="none">{{ __('messages.tax_type_none') }}</option>
                        </select>
                        @error('type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label for="createTaxDescription" class="form-label">{{ __('messages.tax_description') }}</label>
                        <textarea class="form-control @error('description') is-invalid @enderror" id="createTaxDescription" name="description" rows="2"></textarea>
                        @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="is_active" id="createTaxActive" value="1" checked>
                            <label class="form-check-label" for="createTaxActive">{{ __('messages.tax_active') }}</label>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="is_default" id="createTaxDefault" value="1">
                            <label class="form-check-label" for="createTaxDefault">{{ __('messages.tax_set_as_default') }}</label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary"
                        data-bs-dismiss="modal">{{ __('messages.cancel') }}</button>
                    <button type="submit" class="btn btn-primary">{{ __('messages.save') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- EDIT MODAL --}}
<div class="modal fade" id="editTaxModal" tabindex="-1" aria-labelledby="editTaxModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-3">
            <div class="modal-header">
                <h5 class="modal-title" id="editTaxModalLabel"><i class="ti ti-edit me-1"></i> {{ __('messages.tax_modal_edit_title') }}
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editTaxForm" method="POST" novalidate data-validate-form>
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <input type="hidden" id="editTaxId" name="id">
                    <div class="mb-3">
                        <label for="editTaxName" class="form-label">{{ __('messages.tax_name') }}</label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" id="editTaxName" name="name" required>
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label for="editTaxRate" class="form-label">{{ __('messages.tax_rate') }}</label>
                        <input type="number" class="form-control @error('rate') is-invalid @enderror" id="editTaxRate" name="rate" step="0.01" min="0" max="100" required>
                        @error('rate')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label for="editTaxType" class="form-label">{{ __('messages.tax_type') }}</label>
                        <select class="form-select @error('type') is-invalid @enderror" id="editTaxType" name="type" required>
                            <option value="exclusive">{{ __('messages.tax_type_exclusive') }}</option>
                            <option value="inclusive">{{ __('messages.tax_type_inclusive') }}</option>
                            <option value="none">{{ __('messages.tax_type_none') }}</option>
                        </select>
                        @error('type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label for="editTaxDescription" class="form-label">{{ __('messages.tax_description') }}</label>
                        <textarea class="form-control @error('description') is-invalid @enderror" id="editTaxDescription" name="description" rows="2"></textarea>
                        @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="is_active" id="editTaxActive" value="1">
                            <label class="form-check-label" for="editTaxActive">{{ __('messages.tax_active') }}</label>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="is_default" id="editTaxDefault" value="1">
                            <label class="form-check-label" for="editTaxDefault">{{ __('messages.tax_set_as_default') }}</label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary"
                        data-bs-dismiss="modal">{{ __('messages.cancel') }}</button>
                    <button type="submit" class="btn btn-primary">{{ __('messages.save') }}</button>
                </div>
                <input type="hidden" id="updateTaxRouteBase" value="{{ route('admin.setting.tax.update', '') }}">
            </form>
        </div>
    </div>
</div>

