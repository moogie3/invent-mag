export function formatCurrency(value) {
    let currencySettings = {};
    try {
        const initialDataContainer = document.querySelector(
            ".card-body[data-initial-pipelines]"
        );
        if (initialDataContainer) {
            currencySettings.symbol =
                initialDataContainer.dataset.currencySymbol || "$";
            currencySettings.decimalPlaces = parseInt(
                initialDataContainer.dataset.decimalPlaces || 2
            );
            currencySettings.decimalSeparator =
                initialDataContainer.dataset.decimalSeparator || ".";
            currencySettings.thousandSeparator =
                initialDataContainer.dataset.thousandSeparator || ",";
            currencySettings.currency_code =
                initialDataContainer.dataset.currencyCode || "USD";
        }
    } catch (error) {
        console.error("Error parsing initial data for currency settings:", error);
    }

    return new Intl.NumberFormat(currencySettings.locale, {
        style: "currency",
        currency: currencySettings.currency_code,
        maximumFractionDigits: currencySettings.decimalPlaces,
    }).format(parseFloat(value) || 0);
}
