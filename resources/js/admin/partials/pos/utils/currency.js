const currencySettings = {
    locale: document
        .querySelector('meta[name="currency-locale"]')
        .getAttribute("content"),
    currency_code: document
        .querySelector('meta[name="currency-code"]')
        .getAttribute("content"),
    decimal_places: parseInt(
        document
            .querySelector('meta[name="currency-decimal-places"]')
            .getAttribute("content")
    ),
};

export function formatCurrency(amount) {
    return new Intl.NumberFormat(currencySettings.locale, {
        style: "currency",
        currency: currencySettings.currency_code,
        minimumFractionDigits: currencySettings.decimal_places,
        maximumFractionDigits: currencySettings.decimal_places,
    }).format(amount);
}
