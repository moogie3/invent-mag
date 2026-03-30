export function updateFormatPreview(values) {
    const previewElement = document.querySelector(".badge.bg-primary.text-white");
    if (!previewElement) return;

    const decimalPlacesStr = "0".repeat(
        Math.max(0, Math.min(10, parseInt(values.decimalPlaces)))
    );

    let preview = `1${values.thousandSeparator}234`;
    if (parseInt(values.decimalPlaces) > 0) {
        preview += `${values.decimalSeparator}${decimalPlacesStr}`;
    }

    if (values.position === "prefix") {
        preview = `${values.currencySymbol}${preview}`;
    } else {
        preview = `${preview}${values.currencySymbol}`;
    }

    previewElement.textContent = preview;
}
