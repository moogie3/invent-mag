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

<script>
    (function() {
        function initTaxModal() {
            // Update modal preview when modal is shown
            const confirmModal = document.getElementById('confirmModal');
            if (confirmModal) {
                // Ensure we don't attach multiple listeners if called multiple times by Turbo
                if (!confirmModal.dataset.listenerAttached) {
                    confirmModal.dataset.listenerAttached = 'true';
                    confirmModal.addEventListener('show.bs.modal', function() {
                        // Wait a bit for the modal to fully show
                        setTimeout(() => {
                            // Try to find the tax settings form - update this selector to match your actual form
                            const form = document.querySelector('#taxSettingsForm') ||
                                document.querySelector('form[action*="tax"]') ||
                                document.querySelector('form');

                            // Multiple attempts to find tax name input
                            let taxNameInput = null;
                            const nameSelectors = [
                                'input[name="tax_name"]',
                                'input[name="name"]',
                                'input[name="taxName"]',
                                '#tax_name',
                                '#taxName',
                                '.tax-name-input'
                            ];

                            for (const selector of nameSelectors) {
                                taxNameInput = document.querySelector(selector);
                                if (taxNameInput) {
                                    break;
                                }
                            }

                            // Multiple attempts to find tax rate input
                            let taxRateInput = null;
                            const rateSelectors = [
                                'input[name="tax_rate"]',
                                'input[name="rate"]',
                                'input[name="taxRate"]',
                                '#tax_rate',
                                '#taxRate',
                                '.tax-rate-input'
                            ];

                            for (const selector of rateSelectors) {
                                taxRateInput = document.querySelector(selector);
                                if (taxRateInput) {
                                    break;
                                }
                            }

                            // Multiple attempts to find checkbox/switch
                            let isActiveInput = null;
                            const checkboxSelectors = [
                                'input[name="is_active"][type="checkbox"]',
                                'input[name="active"][type="checkbox"]',
                                'input[name="status"][type="checkbox"]',
                                '#tax_active',
                                '#taxActive',
                                '#taxSwitch',
                                '.tax-status-switch'
                            ];

                            for (const selector of checkboxSelectors) {
                                isActiveInput = document.querySelector(selector);
                                if (isActiveInput) {
                                    break;
                                }
                            }

                            // Get values with fallbacks
                            const taxName = taxNameInput ? taxNameInput.value.trim() : '';
                            const taxRate = taxRateInput ? taxRateInput.value.trim() : '';
                            const isActive = isActiveInput ? isActiveInput.checked : false;

                            // Update preview elements
                            const nameElement = document.getElementById('previewTaxName');
                            const rateElement = document.getElementById('previewTaxRate');
                            const statusElement = document.getElementById('previewTaxStatus');

                            if (nameElement) {
                                nameElement.textContent = taxName ||
                                    '{{ __('messages.tax_modal_not_specified') }}';
                            }

                            if (rateElement) {
                                rateElement.textContent = taxRate || '0';
                            }

                            if (statusElement) {
                                if (isActive) {
                                    statusElement.textContent = '{{ __('messages.tax_modal_active') }}';
                                    statusElement.className = 'badge bg-success text-white';
                                } else {
                                    statusElement.textContent = '{{ __('messages.tax_modal_inactive') }}';
                                    statusElement.className = 'badge bg-danger text-white';
                                }
                            }
                        }, 100);
                    });
                }
            }
        }
        
        // Execute immediately
        initTaxModal();
    })();
</script>
