import { originalSupplierData } from './state.js';

export function renderSearchResults(suppliers) {
    const tableBody = document.querySelector("table tbody");
    if (!suppliers.length) {
        showNoResults();
        return;
    }

    suppliers.forEach((supplier) => {
        originalSupplierData.set(supplier.id.toString(), supplier);
    });

    const html = suppliers
        .map((supplier, index) => {
            return `
            <tr data-id="${supplier.id}">
                <td class="sort-no no-print">${index + 1}</td>
                <td class="sort-image">
                    <img src="${supplier.image || '/img/default_placeholder.png'}" alt="Supplier Image" class="avatar avatar-sm">
                </td>
                <td class="sort-code">${supplier.code}</td>
                <td class="sort-name">${supplier.name}</td>
                <td class="sort-address">${supplier.address}</td>
                <td class="sort-location">${supplier.location}</td>
                <td class="sort-paymentterms">${supplier.payment_terms}</td>
                <td class="sort-email">${supplier.email || "N/A"}</td>
                <td class="no-print" style="text-align:center">
                    <div class="dropdown">
                        <button class="btn dropdown-toggle align-text-top"
                            data-bs-toggle="dropdown" data-bs-boundary="viewport">
                            Actions
                        </button>
                        <div class="dropdown-menu">
                            <a href="#" class="dropdown-item srm-supplier-btn"
                                                                data-id="${supplier.id}" data-bs-toggle="modal"
                                                                data-bs-target="#srmSupplierModal">
                                                                <i class="ti ti-user-search me-2"></i> View SRM
                                                            </a>
                            <a href="#" class="dropdown-item"
                                data-bs-toggle="modal"
                                data-bs-target="#editSupplierModal"
                                data-id="${supplier.id}"
                                data-code="${supplier.code}"
                                data-name="${supplier.name}"
                                data-address="${supplier.address}"
                                data-phone_number="${supplier.phone_number || ''}"
                                data-location="${supplier.location}"
                                data-payment_terms="${supplier.payment_terms}"
                                data-email="${supplier.email || ''}"
                                data-image="${supplier.image || ''}">
                                <i class="ti ti-edit me-2"></i> Edit
                            </a>
                            <button type="button" class="dropdown-item text-danger"
                                data-bs-toggle="modal" data-bs-target="#deleteModal"
                                onclick="setDeleteFormAction('/admin/supplier/destroy/${supplier.id}')">
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
}

export function showNoResults(
    message = "No suppliers found matching your search."
) {
    document.querySelector("table tbody").innerHTML = `
        <tr><td colspan="100%" class="text-center py-5">
            <i class="ti ti-search-off fs-1 text-muted"></i>
            <p class="mt-3 text-muted">${message}</p>
        </td></tr>
    `;
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
