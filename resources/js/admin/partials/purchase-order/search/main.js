import { PurchaseOrderBulkSelection } from '../bulkActions/PurchaseOrderBulkSelection.js';

let searchTimeout;
let currentRequest = null;
let isSearchActive = false;
let originalTableContent = null;
let originalPoData = new Map();

function initializeSearch() {
    const searchInput = document.getElementById("searchInput");
    if (!searchInput) return;

    storeOriginalTable();

    searchInput.addEventListener("input", function () {
        clearTimeout(searchTimeout);
        if (currentRequest) {
            currentRequest.abort();
            currentRequest = null;
        }

        const query = this.value.trim();

        searchTimeout = setTimeout(() => {
            if (query.length === 0) {
                if (isSearchActive) {
                    restoreOriginalTable();
                }
                isSearchActive = false;
            } else {
                performSearch(query);
                isSearchActive = true;
            }
        }, 500);
    });
}

function storeOriginalTable() {
    if (!originalTableContent) {
        const tableBody = document.querySelector("table tbody");
        if (tableBody) {
            originalTableContent = tableBody.innerHTML;

            const rows = tableBody.querySelectorAll("tr[data-id]");
            rows.forEach((row) => {
                const poId = row.dataset.id;
                const poData = extractPoDataFromRow(row);
                if (poData) {
                    originalPoData.set(poId, poData);
                }
            });
        }
    }
}

function extractPoDataFromRow(row) {
    try {
        const invoiceElement = row.querySelector(".sort-invoice");
        const supplierElement = row.querySelector(".sort-supplier");
        const orderDateElement = row.querySelector(".sort-orderdate");
        const dueDateElement = row.querySelector(".sort-duedate");
        const amountElement = row.querySelector(".sort-amount");
        const paymentElement = row.querySelector(".sort-payment");
        const statusElement = row.querySelector(".sort-status");

        if (!invoiceElement) return null;

        return {
            id: parseInt(row.dataset.id),
            invoice: invoiceElement.textContent.trim(),
            supplier_name: supplierElement?.textContent?.trim() || "N/A",
            order_date: orderDateElement?.textContent?.trim() || "N/A",
            due_date: dueDateElement?.textContent?.trim() || "N/A",
            amount: amountElement?.textContent?.trim() || "N/A",
            payment_type: paymentElement?.textContent?.trim() || "N/A",
            status: statusElement?.textContent?.trim() || "N/A",
        };
    } catch (error) {
        console.error("Error extracting PO data:", error);
        return null;
    }
}

function restoreOriginalTable() {
    if (originalTableContent) {
        const tableBody = document.querySelector("table tbody");
        if (tableBody) {
            tableBody.innerHTML = originalTableContent;
            setTimeout(() => {
                const bulkSelection = new PurchaseOrderBulkSelection();
                bulkSelection.init();
                bulkSelection.updateUI();
            }, 100);
        }
    }
}

function performSearch(query) {
    storeOriginalTable();

    const tableBody = document.querySelector("table tbody");

    if (!query) {
        restoreOriginalTable();
        return;
    }

    tableBody.innerHTML = `
        <tr><td colspan="100%" class="text-center py-5">
            <div class="spinner-border text-primary"></div>
            <p class="mt-3 text-muted">Searching...</p>
        </td></tr>
    `;

    const controller = new AbortController();
    currentRequest = controller;

    fetch(`/admin/po/search?q=${encodeURIComponent(query)}`, {
        signal: controller.signal,
        headers: {
            Accept: "application/json",
            "X-Requested-With": "XMLHttpRequest",
        },
    })
        .then((response) => response.json())
        .then((data) => {
            currentRequest = null;
            if (data.success) {
                renderSearchResults(data.pos);
            } else {
                showNoResults(data.message);
            }
        })
        .catch((error) => {
            currentRequest = null;
            if (error.name !== "AbortError") {
                showSearchError(error.message);
            }
        });
}

import { formatCurrency } from '../../../../utils/currencyFormatter.js';

function renderSearchResults(pos) {
    const tableBody = document.querySelector("table tbody");
    if (!pos.length) {
        showNoResults();
        return;
    }

    pos.forEach((po) => {
        originalPoData.set(po.id.toString(), po);
    });

    const html = pos
        .map((po, index) => {
            const statusClass = po.status_class;
            const statusText = po.status_text;

            return `
            <tr class="table-row" data-id="${po.id}">
                <td>
                    <input type="checkbox" class="form-check-input row-checkbox" value="${
                        po.id
                    }">
                </td>
                <td class="sort-no no-print">${index + 1}</td>
                <td class="sort-invoice">${po.invoice}</td>
                <td class="sort-supplier">${po.supplier_name}</td>
                <td class="sort-orderdate">${po.order_date}</td>
                <td class="sort-duedate" data-date="${po.due_date_raw}">
                    ${po.due_date}
                </td>
                <td class="sort-amount" data-amount="${po.total_raw}">
                    ${formatCurrency(po.total_raw)}
                    <span class="raw-amount" style="display: none;">${
                        po.total_raw
                    }</span>
                </td>
                <td class="sort-payment no-print">${po.payment_type}</td>
                <td class="sort-status">
                    <span class="${statusClass}">
                        ${statusText}
                    </span>
                </td>
                <td class="no-print" style="text-align:center">
                    <div class="dropdown">
                        <button class="btn dropdown-toggle align-text-top"
                            data-bs-toggle="dropdown" data-bs-boundary="viewport">
                            Actions
                        </button>
                        <div class="dropdown-menu">
                            <a href="javascript:void(0)" onclick="loadPoDetails('${
                                po.id
                            }')"
                               data-bs-toggle="modal" data-bs-target="#viewPoModal" class="dropdown-item">
                                <i class="ti ti-zoom-scan me-2"></i> View
                            </a>
                            <a href="/admin/po/edit/${
                                po.id
                            }" class="dropdown-item">
                                <i class="ti ti-edit me-2"></i> Edit
                            </a>
                            <button type="button" class="dropdown-item text-danger" data-bs-toggle="modal"
                                    data-bs-target="#deleteModal" onclick="setDeleteFormAction('/admin/po/destroy/${
                                        po.id
                                    }')">
                                <i class="ti ti-trash me-2"></i> Delete
                            </button>
                        </div>
                    </div>
                </td>
            </tr>
        `;
        })
        .join("");

    tableBody.innerHTML = html;

    setTimeout(() => {
        const bulkSelection = new PurchaseOrderBulkSelection();
        bulkSelection.init();
        bulkSelection.updateUI();
    }, 100);
}

function showNoResults(
    message = "No purchase orders found matching your search."
) {
    document.querySelector("table tbody").innerHTML = `
        <tr><td colspan="100%" class="text-center py-5">
            <i class="ti ti-search-off fs-1 text-muted"></i>
            <p class="mt-3 text-muted">${message}</p>
        </td></tr>
    `;

    const bulkActionsBar = document.getElementById("bulkActionsBar");
    if (bulkActionsBar) {
        bulkActionsBar.style.display = "none";
    }
}

function showSearchError(errorMessage = "Search error occurred.") {
    document.querySelector("table tbody").innerHTML = `
        <tr><td colspan="100%" class="text-center py-5">
            <i class="ti ti-alert-circle fs-1 text-danger"></i>
            <p class="mt-3 text-danger">${errorMessage}</p>
            <button class="btn btn-outline-primary mt-2" onclick="window.location.reload()">
                <i class="ti ti-refresh me-2"></i> Refresh
            </button>
        </td></tr>
    `;
}

export function initSearch() {
    initializeSearch();
}
