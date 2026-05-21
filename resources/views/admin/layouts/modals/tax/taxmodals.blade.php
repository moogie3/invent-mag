<!-- Confirm Modal -->
<div class="modal fade" id="confirmModal" tabindex="-1" aria-labelledby="confirmModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-3">
            <div class="modal-header">
                <h5 class="modal-title" id="confirmModalLabel">
                    <i class="ti ti-receipt-tax me-2"></i>{{ __('messages.tax_modal_confirm_tax_settings_title') }}
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <p class="mb-2">{{ __('messages.tax_modal_confirm_tax_settings_message') }}
                    </p>
                </div>

                <!-- Preview of settings -->
                <div class="alert alert-light border">
                    <div class="row g-2">
                        <div class="col-6">
                            <small class="text-muted d-block">{{ __('messages.tax_modal_tax_name') }}</small>
                            <span id="previewTaxName" class="fw-medium text-body">-</span>
                        </div>
                        <div class="col-6">
                            <small class="text-muted d-block">{{ __('messages.tax_modal_tax_rate') }}</small>
                            <span id="previewTaxRate" class="fw-medium text-body">-</span>%
                        </div>
                        <div class="col-12 mt-2">
                            <small class="text-muted d-block">{{ __('messages.tax_modal_status') }}</small>
                            <span id="previewTaxStatus" class="badge">-</span>
                        </div>
                    </div>
                </div>

                <div class="alert alert-warning mb-0">
                    <div class="d-flex align-items-center">
                        <i class="ti ti-alert-triangle me-2"></i>
                        <div>
                            <small><strong>{{ __('messages.tax_modal_note') }}</strong>
                                {{ __('messages.tax_modal_note_message') }}</small>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="ti ti-x me-1"></i>{{ __('messages.cancel') }}
                </button>
                <button type="submit" form="taxSettingsForm" class="btn btn-primary">
                    <i class="ti ti-check me-1"></i>{{ __('messages.tax_modal_yes_save_settings') }}
                </button>
            </div>
        </div>
    </div>
</div>

<div id="tax-modal-config"
     data-not-specified="{{ __('messages.tax_modal_not_specified') }}"
     data-active="{{ __('messages.tax_modal_active') }}"
     data-inactive="{{ __('messages.tax_modal_inactive') }}"
     class="d-none"></div>
