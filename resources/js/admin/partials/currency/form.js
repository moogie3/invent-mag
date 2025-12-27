import * as elements from './elements.js';
import { originalValues } from './state.js';
import { updateFormatPreview } from './preview.js';

export function getCurrentFormValues() {
    const selectedOption =
        elements.selectedCurrency?.options[elements.selectedCurrency.selectedIndex];
    return {
        currencyCode: selectedOption?.value || "",
        locale: selectedOption?.dataset.locale || "",
        currencySymbol: selectedOption?.dataset.symbol || "$",
        selectedCurrencyIndex: elements.selectedCurrency?.selectedIndex || 0,
        decimalSeparator:
            elements.decimalSeparatorField?.value || ".",
        thousandSeparator:
            elements.thousandSeparatorField?.value || ",",
        decimalPlaces:
            elements.decimalPlacesField?.value ||
            "2",
        position:
            elements.positionField?.value ||
            "prefix",
    };
}

export function updateHiddenFields() {
    if (
        elements.selectedCurrency &&
        elements.currencyCodeInput &&
        elements.localeInput &&
        elements.currencySymbolInput
    ) {
        const selectedOption =
            elements.selectedCurrency.options[elements.selectedCurrency.selectedIndex];
        elements.currencyCodeInput.value = selectedOption.value;
        elements.localeInput.value = selectedOption.dataset.locale;
        elements.currencySymbolInput.value = selectedOption.dataset.symbol;
    }
}

export function restoreOriginalValues() {
    if (elements.selectedCurrency) {
        elements.selectedCurrency.selectedIndex =
            originalValues.selectedCurrencyIndex;
    }
    if (elements.currencyCodeInput)
        elements.currencyCodeInput.value = originalValues.currencyCode;
    if (elements.localeInput) elements.localeInput.value = originalValues.locale;
    if (elements.currencySymbolInput)
        elements.currencySymbolInput.value = originalValues.currencySymbol;

    if (elements.decimalSeparatorField)
        elements.decimalSeparatorField.value = originalValues.decimalSeparator;
    if (elements.thousandSeparatorField)
        elements.thousandSeparatorField.value = originalValues.thousandSeparator;
    if (elements.decimalPlacesField)
        elements.decimalPlacesField.value = originalValues.decimalPlaces;
    if (elements.positionField) elements.positionField.value = originalValues.position;

    // Update preview with original values
    updateFormatPreview(originalValues);
}
