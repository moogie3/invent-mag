import { initializeOriginalValues, updateOriginalValues, originalValues } from './partials/currency/state.js';
import { updateFormatPreview } from './partials/currency/preview.js';
import { updateHiddenFields } from './partials/currency/form.js';
import { setupModalEventListeners } from './partials/currency/modal.js';
import { handleFormSubmission } from './partials/currency/api.js';
import { onPageLoad } from './utils/onPageLoad.js';

onPageLoad(function () {
    if (!window.location.pathname.includes("/admin/settings/currency")) return;
    
    const currentValues = initializeOriginalValues();
    
    updateHiddenFields();
    updateFormatPreview(currentValues);

    const selectedCurrency = document.getElementById("selectedCurrency");
    if (selectedCurrency) {
        selectedCurrency.addEventListener("change", function () {
            updateHiddenFields();
        });
    }

    setupModalEventListeners();
    handleFormSubmission();

    window.shortcutManager.register('ctrl+s', () => {
        const form = document.getElementById('currencySettingsForm');
        if (form) {
            form.requestSubmit();
        }
    }, 'Save Currency Settings');
});
