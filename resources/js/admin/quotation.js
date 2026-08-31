import { onPageLoad } from "./utils/onPageLoad.js";
import { escapeHtml } from "../utils/sanitize.js";

onPageLoad(() => {
    const isQuotationPage = window.location.pathname.startsWith("/admin/quotations");
    if (!isQuotationPage) return;

    initQuotationForm();
    initQuotationTable();
});

function initQuotationForm() {
    const form = document.querySelector("#quotationForm");
    if (!form) return;

    // Read currency settings from DOM
    const currencySettingsEl = document.getElementById("currencySettings");
    const currencySettings = currencySettingsEl ? {
        symbol: currencySettingsEl.dataset.currencySymbol || "Rp ",
        decimal_places: parseInt(currencySettingsEl.dataset.decimalPlaces) || 0,
        decimal_separator: currencySettingsEl.dataset.decimalSeparator || ".",
        thousand_separator: currencySettingsEl.dataset.thousandSeparator || ",",
        currency_code: currencySettingsEl.dataset.currencyCode || "USD",
        position: currencySettingsEl.dataset.position || "prefix"
    } : { symbol: "Rp ", decimal_places: 0, decimal_separator: ".", thousand_separator: ",", currency_code: "USD", position: "prefix" };

    const addItemBtn = form.querySelector("#addItemBtn");
    const productSelector = form.querySelector("#productSelector");
    const itemQuantity = form.querySelector("#itemQuantity");
    const itemPrice = form.querySelector("#itemPrice");
    const tableBody = form.querySelector("#quotationItemsBody");
    const subtotalEl = form.querySelector("#displaySubtotal");
    const discountInput = form.querySelector("#orderDiscount");
    const discountTypeSelect = form.querySelector("#orderDiscountType");
    const taxSelector = form.querySelector("#taxSelector");
    const totalEl = form.querySelector("#displayTotal");

    if (addItemBtn && productSelector && itemQuantity && itemPrice && tableBody) {
        addItemBtn.addEventListener("click", () => addQuotationItemRow(tableBody, productSelector, itemQuantity, itemPrice, currencySettings));
    }

    form.addEventListener("change", (e) => {
        if (e.target.closest(".item-quantity, .item-price, .item-discount, .item-discount-type")) {
            recalcQuotationTotals(form);
        }
    });

    form.addEventListener("input", (e) => {
        if (e.target.closest(".item-quantity, .item-price, .item-discount, .item-discount-type")) {
            recalcQuotationTotals(form);
        }
    });

    if (discountInput) discountInput.addEventListener("input", () => recalcQuotationTotals(form));
    if (discountTypeSelect) discountTypeSelect.addEventListener("change", () => recalcQuotationTotals(form));
    if (taxSelector) taxSelector.addEventListener("change", () => recalcQuotationTotals(form));

    const pphSelector = form.querySelector("#pphSelector");
    if (pphSelector) pphSelector.addEventListener("change", () => recalcQuotationTotals(form));
}

function addQuotationItemRow(tableBody, productSelector, itemQuantity, itemPrice, currencySettings) {
    const index = tableBody.querySelectorAll("tr").length;
    const productId = productSelector.value;
    const productName = productSelector.options[productSelector.selectedIndex]?.text || "";
    const quantity = itemQuantity.value || 1;
    const price = parseFloat(itemPrice.value || 0);

    if (!productId) {
        return;
    }

    const row = document.createElement("tr");
    row.className = "product-row";
    row.dataset.index = index;
    row.dataset.productId = productId;

    const discount = 0;
    const discountType = "percentage";
    const lineTotal = quantity * price - discount;

    const decimalPlaces = currencySettings.decimal_places ?? 0;
    const currencySymbol = currencySettings.symbol ?? "Rp ";
    const thousandSeparator = currencySettings.thousand_separator ?? ",";
    const decimalSeparator = currencySettings.decimal_separator ?? ".";
    const position = currencySettings.position ?? "prefix";

    const formatCurrency = (amount) => {
        const fixed = amount.toFixed(decimalPlaces);
        const parts = fixed.split(".");
        const integerPart = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, thousandSeparator);
        const decimalPart = parts[1] ? decimalSeparator + parts[1] : "";
        const formatted = integerPart + decimalPart;
        return position === "prefix" ? currencySymbol.trim() + " " + formatted : formatted + " " + currencySymbol.trim();
    };

    row.innerHTML = `
        <td class="text-center">${index + 1}</td>
        <td>
            <input type="hidden" name="items[${index}][product_id]" value="${escapeHtml(productId)}">
            <div class="strong">${escapeHtml(productName)}</div>
        </td>
        <td>
            <input type="number" name="items[${index}][quantity]" value="${quantity}"
                class="form-control text-end item-quantity" min="1" required />
        </td>
        <td>
            <input type="number" name="items[${index}][price]" value="${price.toFixed(decimalPlaces)}"
                class="form-control text-end item-price" step="0.01" min="0" required />
        </td>
        <td>
            <div class="input-group">
                <input type="number" name="items[${index}][discount]" value="${discount}"
                    class="form-control text-end item-discount" style="min-width: 80px;" step="0.01" min="0" />
                <select name="items[${index}][discount_type]" class="form-select item-discount-type" style="min-width: 70px;">
                    <option value="percentage" ${discountType === 'percentage' ? 'selected' : ''}>%</option>
                    <option value="fixed" ${discountType === 'fixed' ? 'selected' : ''}>${escapeHtml(currencySymbol)}</option>
                </select>
            </div>
        </td>
        <td>
            <input type="text" class="form-control text-end item-subtotal" value="${formatCurrency(lineTotal)}" readonly />
        </td>
        <td class="text-center">
            <button type="button" class="btn btn-danger btn-sm remove-item-btn" title="Remove">
                <i class="ti ti-trash"></i>
            </button>
        </td>
    `;

    tableBody.appendChild(row);

    row.querySelector(".remove-item-btn").addEventListener("click", () => {
        row.remove();
        updateRowNumbers(tableBody);
        recalcQuotationTotals(document.querySelector("#quotationForm"));
    });

    productSelector.value = "";
    itemQuantity.value = 1;
    itemPrice.value = 0;

    updateRowNumbers(tableBody);
    recalcQuotationTotals(document.querySelector("#quotationForm"));
}

function updateRowNumbers(tableBody) {
    const rows = tableBody.querySelectorAll("tr");
    rows.forEach((row, index) => {
        row.dataset.index = index;
        row.querySelectorAll("input, select").forEach((input) => {
            const name = input.name;
            if (name) {
                input.name = name.replace(/items\[\d+\]/, `items[${index}]`);
            }
        });
        const noCell = row.querySelector("td:first-child");
        if (noCell) noCell.textContent = index + 1;
    });
}

function recalcQuotationTotals(form) {
    let subtotal = 0;
    const currencySettingsEl = document.getElementById("currencySettings");
    const decimalPlaces = currencySettingsEl ? parseInt(currencySettingsEl.dataset.decimalPlaces) || 0 : 0;
    const currencySymbol = currencySettingsEl ? currencySettingsEl.dataset.currencySymbol || "Rp " : "Rp ";
    const thousandSeparator = currencySettingsEl ? currencySettingsEl.dataset.thousandSeparator || "," : ",";
    const decimalSeparator = currencySettingsEl ? currencySettingsEl.dataset.decimalSeparator || "." : ".";
    const position = currencySettingsEl ? currencySettingsEl.dataset.position || "prefix" : "prefix";

    const formatCurrency = (amount) => {
        const fixed = amount.toFixed(decimalPlaces);
        const parts = fixed.split(".");
        const integerPart = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, thousandSeparator);
        const decimalPart = parts[1] ? decimalSeparator + parts[1] : "";
        const formatted = integerPart + decimalPart;
        return position === "prefix" ? currencySymbol.trim() + " " + formatted : formatted + " " + currencySymbol.trim();
    };

    const items = form.querySelectorAll(".product-row");
    items.forEach((row) => {
        const qty = parseFloat(row.querySelector(".item-quantity")?.value) || 0;
        const price = parseFloat(row.querySelector(".item-price")?.value) || 0;
        const disc = parseFloat(row.querySelector(".item-discount")?.value) || 0;
        const discType = row.querySelector(".item-discount-type")?.value || "percentage";
        const lineDiscount = discType === "percentage" ? (qty * price * disc) / 100 : disc;
        const lineTotal = qty * price - lineDiscount;
        const subEl = row.querySelector(".item-subtotal");
        if (subEl) subEl.textContent = formatCurrency(lineTotal);
        subtotal += lineTotal;
    });

    const subtotalEl = form.querySelector("#displaySubtotal");
    if (subtotalEl) subtotalEl.textContent = formatCurrency(subtotal);

    const discount = parseFloat(form.querySelector("#orderDiscount")?.value) || 0;
    const discountType = form.querySelector("#orderDiscountType")?.value || "percentage";
    const discountAmount = discountType === "percentage" ? (subtotal * discount) / 100 : discount;

    const discountLabel = document.getElementById('discountLabel');
    if (discountLabel) {
        const base = discountLabel.dataset.base || 'Discount';
        discountLabel.textContent = discount > 0
            ? (discountType === 'percentage' ? base + ' (' + discount + '%)' : base + ' (' + formatCurrency(discount) + ')')
            : base;
    }

    const afterDiscount = subtotal - discountAmount;

    const taxSelector = form.querySelector("#taxSelector");
    const selectedOption = taxSelector?.options[taxSelector.selectedIndex];
    const taxRate = parseFloat(selectedOption?.dataset.rate || 0);
    const taxType = selectedOption?.dataset.type || "none";
    let taxAmount = 0;
    if (taxType === "exclusive") {
        taxAmount = afterDiscount * (taxRate / 100);
    } else if (taxType === "inclusive") {
        taxAmount = afterDiscount - afterDiscount / (1 + taxRate / 100);
    }

    const total = taxType === "exclusive" ? afterDiscount + taxAmount : afterDiscount;

    const pphSelector = document.getElementById('pphSelector');
    let pphAmount = 0;
    if (pphSelector && pphSelector.value) {
        const pphOpt = pphSelector.options[pphSelector.selectedIndex];
        const pphRate = parseFloat(pphOpt.dataset.rate) || 0;
        pphAmount = Math.round(afterDiscount * (pphRate / 100) * 100) / 100;
    }
    const finalTotal = Math.max(0, total - pphAmount);

    const taxLabel = document.getElementById("taxLabel");
    if (taxLabel && selectedOption) {
        const base = taxLabel.dataset.base || 'Quotation Tax';
        const rate = parseFloat(selectedOption.dataset.rate) || 0;
        taxLabel.textContent = selectedOption.value && selectedOption.dataset.name ? base + ' (' + selectedOption.dataset.name + ' ' + rate + '%)' : base;
    }

    const totalEl = form.querySelector("#displayTotal");
    if (totalEl) totalEl.textContent = formatCurrency(finalTotal);
}

function initQuotationTable() {
    const table = document.querySelector("#quotationTable");
    if (!table) return;

    const sortableHeaders = table.querySelectorAll(".sortable");
    sortableHeaders.forEach((header) => {
        header.addEventListener("click", () => {
            const sortKey = header.dataset.sort;
            const currentDir = header.dataset.dir || "asc";
            const newDir = currentDir === "asc" ? "desc" : "asc";
            header.dataset.dir = newDir;
            sortQuotationTable(table, sortKey, newDir);
        });
    });
}

function sortQuotationTable(table, key, dir) {
    const tbody = table.querySelector("tbody");
    const rows = Array.from(tbody.querySelectorAll("tr"));
    const multiplier = dir === "asc" ? 1 : -1;

    rows.sort((a, b) => {
        const aVal = a.dataset[key] || a.querySelector(`td[data-${key}]`)?.dataset[key] || "";
        const bVal = b.dataset[key] || b.querySelector(`td[data-${key}]`)?.dataset[key] || "";
        return aVal.localeCompare(bVal, undefined, { numeric: true }) * multiplier;
    });

    rows.forEach((row) => tbody.appendChild(row));
}