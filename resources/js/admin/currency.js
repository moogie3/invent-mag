import { originalValues } from './partials/currency/state.js';
import { updateFormatPreview } from './partials/currency/preview.js';
import { updateHiddenFields } from './partials/currency/form.js';
import { setupModalEventListeners } from './partials/currency/modal.js';
import { handleFormSubmission } from './partials/currency/api.js';
import * as elements from './partials/currency/elements.js';

document.addEventListener("DOMContentLoaded", function () {
    updateHiddenFields();
    updateFormatPreview(originalValues);

    if (elements.selectedCurrency) {
        elements.selectedCurrency.addEventListener("change", function () {
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
