import { onPageLoad } from './utils/onPageLoad.js';

onPageLoad(function () {
    if (!window.location.pathname.includes("/admin/settings/tax")) return;
    
    // Register global shortcuts
    window.shortcutManager.register('ctrl+s', () => {
        const form = document.getElementById('taxSettingsForm');
        if (form) {
            form.requestSubmit();
        }
    }, 'Save Tax Settings');

    function initTaxModal() {
        const confirmModal = document.getElementById('confirmModal');
        if (!confirmModal || confirmModal.dataset.listenerAttached) return;
        confirmModal.dataset.listenerAttached = 'true';

        confirmModal.addEventListener('show.bs.modal', function() {
            setTimeout(() => {
                const configEl = document.getElementById('tax-modal-config');
                const notSpecified = configEl ? configEl.getAttribute('data-not-specified') : 'Not Specified';
                const activeText = configEl ? configEl.getAttribute('data-active') : 'Active';
                const inactiveText = configEl ? configEl.getAttribute('data-inactive') : 'Inactive';

                const form = document.querySelector('#taxSettingsForm') ||
                    document.querySelector('form[action*="tax"]') ||
                    document.querySelector('form');

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

                const taxName = taxNameInput ? taxNameInput.value.trim() : '';
                const taxRate = taxRateInput ? taxRateInput.value.trim() : '';
                const isActive = isActiveInput ? isActiveInput.checked : false;

                const nameElement = document.getElementById('previewTaxName');
                const rateElement = document.getElementById('previewTaxRate');
                const statusElement = document.getElementById('previewTaxStatus');

                if (nameElement) {
                    nameElement.textContent = taxName || notSpecified;
                }

                if (rateElement) {
                    rateElement.textContent = taxRate || '0';
                }

                if (statusElement) {
                    if (isActive) {
                        statusElement.textContent = activeText;
                        statusElement.className = 'badge bg-success text-white';
                    } else {
                        statusElement.textContent = inactiveText;
                        statusElement.className = 'badge bg-danger text-white';
                    }
                }
            }, 100);
        });
    }

    initTaxModal();
});
