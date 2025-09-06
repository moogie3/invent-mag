import * as elements from './elements.js';

export let originalValues = {
    currencyCode: elements.currencyCodeInput?.value || "",
    locale: elements.localeInput?.value || "",
    currencySymbol: elements.currencySymbolInput?.value || "",
    selectedCurrencyIndex: elements.selectedCurrency?.selectedIndex || 0,
    decimalSeparator:
        elements.decimalSeparatorField?.value ||
        ".",
    thousandSeparator:
        elements.thousandSeparatorField?.value ||
        ",",
    decimalPlaces:
        elements.decimalPlacesField?.value ||
        "2",
    position:
        elements.positionField?.value ||
        "prefix",
};

export function updateOriginalValues(newValues) {
    originalValues = newValues;
}
