export let originalValues = {
    currencyCode: "",
    locale: "",
    currencySymbol: "",
    selectedCurrencyIndex: 0,
    decimalSeparator: ".",
    thousandSeparator: ",",
    decimalPlaces: "2",
    position: "prefix",
};

export function initializeOriginalValues() {
    const selectedCurrency = document.getElementById("selectedCurrency");
    const currencyCodeInput = document.getElementById("currencyCode");
    const localeInput = document.getElementById("locale");
    const currencySymbolInput = document.getElementById("currencySymbol");
    const decimalSeparatorField = document.querySelector('input[name="decimal_separator"]');
    const thousandSeparatorField = document.querySelector('input[name="thousand_separator"]');
    const decimalPlacesField = document.querySelector('input[name="decimal_places"]');
    const positionField = document.querySelector('select[name="position"]');
    
    originalValues = {
        currencyCode: currencyCodeInput?.value || "",
        locale: localeInput?.value || "",
        currencySymbol: currencySymbolInput?.value || "",
        selectedCurrencyIndex: selectedCurrency?.selectedIndex || 0,
        decimalSeparator: decimalSeparatorField?.value || ".",
        thousandSeparator: thousandSeparatorField?.value || ",",
        decimalPlaces: decimalPlacesField?.value || "2",
        position: positionField?.value || "prefix",
    };
    
    return originalValues;
}

export function updateOriginalValues(newValues) {
    originalValues = newValues;
}
