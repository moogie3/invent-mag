<!-- Confirm Modal -->
<div class="modal fade" id="confirmModal" tabindex="-1" aria-labelledby="confirmModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="confirmModalLabel">
                    <i class="ti ti-receipt-tax me-2"></i>Confirm Tax Settings
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <p class="mb-2">Are you sure you want to update the tax settings with the following information?
                    </p>
                </div>

                <!-- Preview of settings -->
                <div class="alert alert-light border">
                    <div class="row g-2">
                        <div class="col-6">
                            <small class="text-muted d-block">Tax Name:</small>
                            <span id="previewTaxName" class="fw-medium">-</span>
                        </div>
                        <div class="col-6">
                            <small class="text-muted d-block">Tax Rate:</small>
                            <span id="previewTaxRate" class="fw-medium">-</span>%
                        </div>
                        <div class="col-12 mt-2">
                            <small class="text-muted d-block">Status:</small>
                            <span id="previewTaxStatus" class="badge">-</span>
                        </div>
                    </div>
                </div>

                <div class="alert alert-warning mb-0">
                    <div class="d-flex align-items-center">
                        <i class="ti ti-alert-triangle me-2"></i>
                        <div>
                            <small><strong>Note:</strong> These changes will affect all future transactions and
                                calculations.</small>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="ti ti-x me-1"></i>Cancel
                </button>
                <button type="submit" form="taxSettingsForm" class="btn btn-primary">
                    <i class="ti ti-check me-1"></i>Yes, Save Settings
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Update modal preview when modal is shown
        const confirmModal = document.getElementById('confirmModal');
        if (confirmModal) {
            confirmModal.addEventListener('show.bs.modal', function() {
                // Wait a bit for the modal to fully show
                setTimeout(() => {
                    // Debug: Log all form elements to see what's available
                    console.log('All forms:', document.forms);
                    console.log('All inputs:', document.querySelectorAll('input'));

                    // Try to find the tax settings form - update this selector to match your actual form
                    const form = document.querySelector('#taxSettingsForm') ||
                        document.querySelector('form[action*="tax"]') ||
                        document.querySelector('form');

                    if (form) {
                        console.log('Found form:', form);
                        console.log('Form inputs:', form.querySelectorAll('input, select'));
                    }

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
                            console.log('Found tax name input with selector:', selector);
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
                            console.log('Found tax rate input with selector:', selector);
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
                            console.log('Found checkbox input with selector:', selector);
                            break;
                        }
                    }

                    // Get values with fallbacks
                    const taxName = taxNameInput ? taxNameInput.value.trim() : '';
                    const taxRate = taxRateInput ? taxRateInput.value.trim() : '';
                    const isActive = isActiveInput ? isActiveInput.checked : false;

                    console.log('Retrieved values:', {
                        taxNameInput: !!taxNameInput,
                        taxRateInput: !!taxRateInput,
                        isActiveInput: !!isActiveInput,
                        taxName: taxName,
                        taxRate: taxRate,
                        isActive: isActive
                    });

                    // Update preview elements
                    const nameElement = document.getElementById('previewTaxName');
                    const rateElement = document.getElementById('previewTaxRate');
                    const statusElement = document.getElementById('previewTaxStatus');

                    if (nameElement) {
                        nameElement.textContent = taxName || 'Not specified';
                        nameElement.style.color = taxName ? '#000' : '#999';
                    }

                    if (rateElement) {
                        rateElement.textContent = taxRate || '0';
                        rateElement.style.color = taxRate ? '#000' : '#999';
                    }

                    if (statusElement) {
                        if (isActive) {
                            statusElement.textContent = 'Active';
                            statusElement.className = 'badge bg-success text-white';
                        } else {
                            statusElement.textContent = 'Inactive';
                            statusElement.className = 'badge bg-danger text-white';
                        }
                    }

                    // If no inputs were found, show debug info
                    if (!taxNameInput && !taxRateInput && !isActiveInput) {
                        console.warn('No tax form inputs found. Available inputs:',
                            Array.from(document.querySelectorAll('input')).map(input => ({
                                name: input.name,
                                id: input.id,
                                type: input.type,
                                className: input.className
                            }))
                        );
                    }
                }, 100);
            });
        }

        // Alternative: Update preview when button is clicked
        const saveButton = document.querySelector('[data-bs-target="#confirmModal"]');
        if (saveButton) {
            saveButton.addEventListener('click', function() {
                console.log('Save button clicked - checking form data');

                // Try to get form data as backup
                const forms = document.querySelectorAll('form');
                forms.forEach((form, index) => {
                    console.log(`Form ${index}:`, form);
                    const formData = new FormData(form);
                    console.log('Form data entries:');
                    for (let pair of formData.entries()) {
                        console.log(pair[0] + ': ' + pair[1]);
                    }
                });
            });
        }
    });
</script>
