export function extractProductDataFromRow(row) {
    try {
        const img = row.querySelector(".sort-image img");
        const nameElement = row.querySelector(".sort-name");
        const codeElement = row.querySelector(".sort-code");
        const quantityElement = row.querySelector(".sort-quantity");
        const categoryElement = row.querySelector(".sort-category");
        const unitElement = row.querySelector(".sort-unit");
        const priceElement = row.querySelector(".sort-price");
        const sellingPriceElement = row.querySelector(".sort-sellingprice");
        const supplierElement = row.querySelector(".sort-supplier");
        const lowStockThresholdElement = quantityElement?.querySelector("small");

        if (!nameElement) return null;

        const quantityText = quantityElement?.textContent?.trim() || "0";
        const stockMatch = quantityText.match(/^\d+/);
        const stock = stockMatch ? parseInt(stockMatch[0]) : 0;

        let lowStockThreshold = null;
        if (lowStockThresholdElement) {
            const thresholdText = lowStockThresholdElement.textContent.trim();
            const thresholdMatch = thresholdText.match(/\d+/);
            if (thresholdMatch) {
                lowStockThreshold = parseInt(thresholdMatch[0]);
            }
        }

        return {
            id: parseInt(row.dataset.id),
            name: nameElement.textContent.trim(),
            code: codeElement?.textContent?.trim() || "N/A",
            stock_quantity: stock,
            category: { name: categoryElement?.textContent?.trim() || "N/A" },
            unit: { symbol: unitElement?.textContent?.trim() || "N/A" },
            price: extractPriceFromText(priceElement?.textContent || "0"),
            selling_price: extractPriceFromText(
                sellingPriceElement?.textContent || "0"
            ),
            supplier: { name: supplierElement?.textContent?.trim() || "N/A" },
            image: img?.src || "/img/default_placeholder.png",
            low_stock_threshold: lowStockThreshold,
        };
    } catch (error) {
        // // console.error("Error extracting product data:", error);
        return null;
    }
}

function extractPriceFromText(priceText) {
    if (!priceText || priceText === "N/A") return 0;
    const matches = priceText.match(/[\d,]+/g);
    if (matches) {
        return parseInt(matches.join("").replace(/,/g, "")) || 0;
    }
    return 0;
}
