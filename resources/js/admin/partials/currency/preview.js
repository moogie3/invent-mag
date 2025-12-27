import * as elements from './elements.js';

export function updateFormatPreview(values) {
    if (!elements.previewElement) return;

    // Generate decimal places string (e.g., "00" for 2 decimal places)
    const decimalPlacesStr = "0".repeat(
        Math.max(0, Math.min(10, parseInt(values.decimalPlaces)))
    );

    // Create the preview format
    let preview = `1${values.thousandSeparator}234`;
    if (parseInt(values.decimalPlaces) > 0) {
        preview += `${values.decimalSeparator}${decimalPlacesStr}`;
    }

    // Add currency symbol based on position
    if (values.position === "prefix") {
        preview = `${values.currencySymbol}${preview}`;
    } else {
        preview = `${preview}${values.currencySymbol}`;
    }

    elements.previewElement.textContent = preview;
}
