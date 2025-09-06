const currencySettings = {
    locale: document.querySelector('meta[name="currency-locale"]').content,
    currency_code: document.querySelector('meta[name="currency-code"]').content,
    decimal_places: parseInt(document.querySelector('meta[name="currency-decimal-places"]').content),
};

export function formatCurrency(value) {
    return new Intl.NumberFormat(currencySettings.locale, {
        style: "currency",
        currency: currencySettings.currency_code,
        maximumFractionDigits: currencySettings.decimal_places,
    }).format(parseFloat(value) || 0);
}
