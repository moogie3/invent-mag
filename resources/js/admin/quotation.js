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

    const itemsContainer = form.querySelector("#itemsContainer");
    const addItemBtn = form.querySelector("#addItemBtn");
    const subtotalEl = form.querySelector("#subtotalDisplay");
    const discountInput = form.querySelector("#orderDiscount");
    const discountTypeSelect = form.querySelector("#orderDiscountType");
    const taxRateInput = form.querySelector("#taxRate");
    const taxTypeSelect = form.querySelector("#taxType");
    const totalEl = form.querySelector("#totalDisplay");

    if (addItemBtn && itemsContainer) {
        addItemBtn.addEventListener("click", () => addQuotationItemRow(itemsContainer));
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
    if (taxRateInput) taxRateInput.addEventListener("input", () => recalcQuotationTotals(form));
    if (taxTypeSelect) taxTypeSelect.addEventListener("change", () => recalcQuotationTotals(form));
}

function addQuotationItemRow(container) {
    const index = container.children.length;
    const row = document.createElement("div");
    row.className = "row g-3 item-row border-bottom pb-3 mb-3";
    row.dataset.index = index;
    row.innerHTML = `
        <div class="col-md-4">
            <select name="items[${index}][product_id]" class="form-select product-select" required>
                <option value="">Select Product</option>
            </select>
        </div>
        <div class="col-md-2">
            <input type="number" name="items[${index}][quantity]" class="form-control item-quantity" min="1" value="1" required>
        </div>
        <div class="col-md-2">
            <input type="number" step="0.01" name="items[${index}][price]" class="form-control item-price" min="0" value="0" required>
        </div>
        <div class="col-md-2">
            <div class="input-group">
                <input type="number" step="0.01" name="items[${index}][discount]" class="form-control item-discount" min="0" value="0">
                <select name="items[${index}][discount_type]" class="form-select item-discount-type" style="max-width:80px">
                    <option value="fixed">Rp</option>
                    <option value="percentage">%</option>
                </select>
            </div>
        </div>
        <div class="col-md-1">
            <span class="item-subtotal d-block mt-2 fw-semibold">0.00</span>
        </div>
        <div class="col-md-1">
            <button type="button" class="btn btn-ghost-danger btn-icon remove-item" title="Remove">
                <i class="ti ti-trash"></i>
            </button>
        </div>`;
    container.appendChild(row);

    row.querySelector(".remove-item").addEventListener("click", () => {
        row.remove();
        recalcQuotationTotals(document.querySelector("#quotationForm"));
    });
}

function recalcQuotationTotals(form) {
    let subtotal = 0;
    const items = form.querySelectorAll(".item-row");
    items.forEach((row) => {
        const qty = parseFloat(row.querySelector(".item-quantity")?.value) || 0;
        const price = parseFloat(row.querySelector(".item-price")?.value) || 0;
        const disc = parseFloat(row.querySelector(".item-discount")?.value) || 0;
        const discType = row.querySelector(".item-discount-type")?.value || "fixed";
        const lineDiscount = discType === "percentage" ? (qty * price * disc) / 100 : disc;
        const lineTotal = qty * price - lineDiscount;
        const subEl = row.querySelector(".item-subtotal");
        if (subEl) subEl.textContent = lineTotal.toFixed(2);
        subtotal += lineTotal;
    });

    const subtotalEl = form.querySelector("#subtotalDisplay");
    if (subtotalEl) subtotalEl.textContent = subtotal.toFixed(2);

    const discount = parseFloat(form.querySelector("#orderDiscount")?.value) || 0;
    const discountType = form.querySelector("#orderDiscountType")?.value || "percentage";
    const discountAmount = discountType === "percentage" ? (subtotal * discount) / 100 : discount;

    const afterDiscount = subtotal - discountAmount;

    const taxRate = parseFloat(form.querySelector("#taxRate")?.value) || 0;
    const taxType = form.querySelector("#taxType")?.value || "none";
    let taxAmount = 0;
    if (taxType === "exclusive") {
        taxAmount = afterDiscount * (taxRate / 100);
    } else if (taxType === "inclusive") {
        taxAmount = afterDiscount - afterDiscount / (1 + taxRate / 100);
    }

    const total = taxType === "exclusive" ? afterDiscount + taxAmount : afterDiscount;

    const totalEl = form.querySelector("#totalDisplay");
    if (totalEl) totalEl.textContent = Math.max(0, total).toFixed(2);
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
