import { originalValues } from './state.js';
import { updateFormatPreview } from './preview.js';

export function getCurrentFormValues() {
    const selectedCurrency = document.getElementById("selectedCurrency");
    const decimalSeparatorField = document.querySelector('input[name="decimal_separator"]');
    const thousandSeparatorField = document.querySelector('input[name="thousand_separator"]');
    const decimalPlacesField = document.querySelector('input[name="decimal_places"]');
    const positionField = document.querySelector('select[name="position"]');
    
    const selectedOption = selectedCurrency?.options[selectedCurrency.selectedIndex];
    return {
        currencyCode: selectedOption?.value || "",
        locale: selectedOption?.dataset.locale || "",
        currencySymbol: selectedOption?.dataset.symbol || "$",
        selectedCurrencyIndex: selectedCurrency?.selectedIndex || 0,
        decimalSeparator: decimalSeparatorField?.value || ".",
        thousandSeparator: thousandSeparatorField?.value || ",",
        decimalPlaces: decimalPlacesField?.value || "2",
        position: positionField?.value || "prefix",
    };
}

export function updateHiddenFields() {
    const selectedCurrency = document.getElementById("selectedCurrency");
    const currencyCodeInput = document.getElementById("currencyCode");
    const localeInput = document.getElementById("locale");
    const currencySymbolInput = document.getElementById("currencySymbol");
    
    if (selectedCurrency && currencyCodeInput && localeInput && currencySymbolInput) {
        const selectedOption = selectedCurrency.options[selectedCurrency.selectedIndex];
        currencyCodeInput.value = selectedOption.value;
        localeInput.value = selectedOption.dataset.locale;
        currencySymbolInput.value = selectedOption.dataset.symbol;
    }
}

export function restoreOriginalValues() {
    const selectedCurrency = document.getElementById("selectedCurrency");
    const currencyCodeInput = document.getElementById("currencyCode");
    const localeInput = document.getElementById("locale");
    const currencySymbolInput = document.getElementById("currencySymbol");
    const decimalSeparatorField = document.querySelector('input[name="decimal_separator"]');
    const thousandSeparatorField = document.querySelector('input[name="thousand_separator"]');
    const decimalPlacesField = document.querySelector('input[name="decimal_places"]');
    const positionField = document.querySelector('select[name="position"]');
    
    if (selectedCurrency) {
        selectedCurrency.selectedIndex = originalValues.selectedCurrencyIndex;
    }
    if (currencyCodeInput) currencyCodeInput.value = originalValues.currencyCode;
    if (localeInput) localeInput.value = originalValues.locale;
    if (currencySymbolInput) currencySymbolInput.value = originalValues.currencySymbol;

    if (decimalSeparatorField) decimalSeparatorField.value = originalValues.decimalSeparator;
    if (thousandSeparatorField) thousandSeparatorField.value = originalValues.thousandSeparator;
    if (decimalPlacesField) decimalPlacesField.value = originalValues.decimalPlaces;
    if (positionField) positionField.value = originalValues.position;

    updateFormatPreview(originalValues);
}
