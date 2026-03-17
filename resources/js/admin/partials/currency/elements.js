// These elements are accessed lazily in functions, not at import time
// This prevents errors on pages without these elements
export function getCurrencyElements() {
    return {
        showModalButton: document.getElementById("showModalButton"),
        confirmSubmitButton: document.getElementById("confirmSubmit"),
        currencySettingsForm: document.getElementById("currencySettingsForm"),
        selectedCurrency: document.getElementById("selectedCurrency"),
        currencyCodeInput: document.getElementById("currencyCode"),
        localeInput: document.getElementById("locale"),
        currencySymbolInput: document.getElementById("currencySymbol"),
        previewElement: document.querySelector(".badge.bg-primary.text-white"),
        decimalSeparatorField: document.querySelector('input[name="decimal_separator"]'),
        thousandSeparatorField: document.querySelector('input[name="thousand_separator"]'),
        decimalPlacesField: document.querySelector('input[name="decimal_places"]'),
        positionField: document.querySelector('select[name="position"]'),
        cancelButton: document.querySelector("#confirmModal .btn-secondary"),
        modalElement: document.getElementById("confirmModal"),
    };
}

// Lazy getters for backward compatibility - these return null if elements don't exist
export const showModalButton = document.getElementById("showModalButton");
export const confirmSubmitButton = document.getElementById("confirmSubmit");
export const currencySettingsForm = document.getElementById("currencySettingsForm");
export const selectedCurrency = document.getElementById("selectedCurrency");
export const currencyCodeInput = document.getElementById("currencyCode");
export const localeInput = document.getElementById("locale");
export const currencySymbolInput = document.getElementById("currencySymbol");
export const previewElement = document.querySelector(".badge.bg-primary.text-white");
export const decimalSeparatorField = document.querySelector('input[name="decimal_separator"]');
export const thousandSeparatorField = document.querySelector('input[name="thousand_separator"]');
export const decimalPlacesField = document.querySelector('input[name="decimal_places"]');
export const positionField = document.querySelector('select[name="position"]');
export const cancelButton = document.querySelector("#confirmModal .btn-secondary");
export const modalElement = document.getElementById("confirmModal");
