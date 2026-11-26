/**
 * Currency formatting for the mobile PWA.
 *
 * Reads the same settings object the admin panel puts on <body
 * data-currency-settings>, so a formatted amount is identical on both
 * surfaces. Mirrors resources/js/utils/currencyFormatter.js.
 */

let cached = null;

function settings() {
    if (cached) {
        return cached;
    }

    const raw = document.body && document.body.getAttribute('data-currency-settings');
    if (!raw) {
        return null;
    }

    try {
        cached = JSON.parse(raw);
    } catch {
        cached = null;
    }

    return cached;
}

export function formatCurrency(amount) {
    const s = settings();
    const value = parseFloat(amount || 0);

    if (!s) {
        return Number.isFinite(value) ? value.toFixed(2) : '0.00';
    }

    const fixed = value.toFixed(s.decimal_places ?? 0);
    const [integerPart, decimalPart] = fixed.split('.');

    const grouped = integerPart.replace(/\B(?=(\d{3})+(?!\d))/g, s.thousand_separator || '');
    const number = decimalPart
        ? grouped + (s.decimal_separator || '.') + decimalPart
        : grouped;

    return s.position === 'suffix'
        ? `${number} ${s.currency_symbol}`
        : `${s.currency_symbol} ${number}`;
}

/** Plain number with thousands separators, no symbol (stock counts etc). */
export function formatNumber(value) {
    const s = settings();
    const fixed = Number(value || 0).toFixed(0);
    return fixed.replace(/\B(?=(\d{3})+(?!\d))/g, (s && s.thousand_separator) || ',');
}
