/**
 * Formats a number as currency based on global currency settings.
 * Assumes window.currencySettings is available and correctly populated.
 *
 * @param {number} amount The number to format.
 * @returns {string} The formatted currency string.
 */
export function formatCurrency(amount) {
    if (typeof window.currencySettings === 'undefined') {
        console.error('window.currencySettings is not defined. Cannot format currency.');
        // Fallback to a simple format if settings are not available
        return `$${parseFloat(amount || 0).toFixed(2)}`;
    }

    const settings = window.currencySettings;
    const numAmount = parseFloat(amount || 0);

    // Format the number part using custom decimal and thousand separators
    // This is a simplified version of number_format from PHP
    let formattedNumber = numAmount.toFixed(settings.decimal_places);

    // Split into integer and decimal parts
    const parts = formattedNumber.split('.');
    let integerPart = parts[0];
    const decimalPart = parts.length > 1 ? parts[1] : '';

    // Add thousand separators to the integer part
    integerPart = integerPart.replace(/\B(?=(\d{3})+(?!\d))/g, settings.thousand_separator);

    // Recombine with custom decimal separator
    formattedNumber = integerPart + (decimalPart ? settings.decimal_separator + decimalPart : '');

    // Apply currency symbol and position
    if (settings.position === 'prefix') {
        return `${settings.currency_symbol} ${formattedNumber}`;
    } else {
        return `${formattedNumber} ${settings.currency_symbol}`;
    }
}