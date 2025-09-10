import { originalProductData } from './state.js';
import { initBulkSelection, restoreCheckboxStates, updateBulkActionsBarVisibility, getSelectedProductIds } from '../bulkActions/selection.js';
import { formatCurrency } from '../../../../utils/currencyFormatter.js';

export function renderSearchResults(products) {
    const tableBody = document.querySelector("table tbody");
    if (!products.length) {
        showNoResults();
        return;
    }

    products.forEach((product) => {
        originalProductData.set(product.id.toString(), product);
    });

    const selectedProductIds = new Set(getSelectedProductIds());

    const html = products
        .map((product, index) => {
            const isLowStock =
                product.stock_quantity <= (product.low_stock_threshold || 10);
            const isSelected = selectedProductIds.has(product.id.toString());

            return `
            <tr data-id="${product.id}">
                <td><input type="checkbox" class="form-check-input row-checkbox" value="${
                    product.id
                }" ${isSelected ? "checked" : ""}></td>
                <td class="sort-no">${index + 1}</td>
                <td class="sort-image" style="width:120px">
                    <img src="${product.image || "/img/default_placeholder.png"}"
                         width="80px" height="80px" alt="${product.name}"
                         onerror="this.src='/img/default_placeholder.png'">
                </td>
                <td class="sort-code no-print">${product.code || "N/A"}</td>
                <td class="sort-name">${product.name}</td>
                <td class="sort-quantity no-print text-center">
                    ${product.stock_quantity}
                    ${
                        isLowStock
                            ? '<span class="badge bg-red-lt">Low Stock</span>'
                            : ""
                    }
                </td>
                <td class="sort-category no-print">${
                    product.category?.name || "N/A"
                }</td>
                <td class="sort-unit">${product.unit?.symbol || "N/A"}</td>
                <td class="sort-price text-center">${formatCurrency(
                    product.price
                )}</td>
                <td class="sort-sellingprice text-center">${formatCurrency(
                    product.selling_price
                )}</td>
                <td class="sort-supplier text-center">${
                    product.supplier?.name || "N/A"
                }</td>
                <td class="no-print" style="text-align:center">
                    <div class="dropdown">
                        <button class="btn dropdown-toggle" data-bs-toggle="dropdown">Actions</button>
                        <div class="dropdown-menu">
                            <a href="javascript:void(0)" onclick="loadProductDetails('${
                                product.id
                            }')"
                               data-bs-toggle="modal" data-bs-target="#viewProductModal" class="dropdown-item">
                                <i class="ti ti-zoom-scan me-2"></i> View
                            </a>
                            <a href="/admin/product/edit/${
                                product.id
                            }" class="dropdown-item">
                                <i class="ti ti-edit me-2"></i> Edit
                            </a>
                            <button type="button" class="dropdown-item text-danger" data-bs-toggle="modal"
                                    data-bs-target="#deleteModal" onclick="setDeleteFormAction('/admin/product/destroy/${
                                        product.id
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
        initBulkSelection();
        restoreCheckboxStates();
        updateBulkActionsBarVisibility();
    }, 100);
}

export function showNoResults(message = "No products found matching your search.") {
    document.querySelector("table tbody").innerHTML = `
        <tr><td colspan="100%" class="text-center py-5">
            <div class="spinner-border text-primary"></div>
            <p class="mt-3 text-muted">${message}</p>
        </td></tr>
    `;

    const bulkActionsBar = document.getElementById("bulkActionsBar");
    if (bulkActionsBar && getSelectedProductIds().length === 0) {
        bulkActionsBar.style.display = "none";
    }
}

export function showSearchError(errorMessage = "Search error occurred.") {
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
