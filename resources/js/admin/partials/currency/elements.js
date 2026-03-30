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


