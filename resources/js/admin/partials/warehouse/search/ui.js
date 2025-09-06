import { originalWarehouseData } from './state.js';

export function renderSearchResults(warehouses) {
    const tableBody = document.querySelector("table tbody");
    if (!warehouses.length) {
        showNoResults();
        return;
    }

    warehouses.forEach((warehouse) => {
        originalWarehouseData.set(warehouse.id.toString(), warehouse);
    });

    const html = warehouses
        .map((warehouse, index) => {
            return `
            <tr data-id="${warehouse.id}">
                <td class="sort-no no-print">${index + 1}</td>
                <td class="sort-name">${warehouse.name}</td>
                <td class="sort-address">${warehouse.address}</td>
                <td class="sort-description">${warehouse.description}</td>
                <td class="sort-is-main">
                    ${warehouse.is_main ? '<span class="badge bg-green-lt">Main</span>' : '<span class="badge bg-secondary-lt">Sub</span>'}
                </td>
                <td class="no-print" style="text-align:center">
                    <div class="dropdown">
                        <button class="btn dropdown-toggle align-text-top"
                            data-bs-toggle="dropdown" data-bs-boundary="viewport">
                            Actions
                        </button>
                        <div class="dropdown-menu">
                            <a href="#" class="dropdown-item"
                                data-bs-toggle="modal"
                                data-bs-target="#editWarehouseModal"
                                data-id="${warehouse.id}"
                                data-name="${warehouse.name}"
                                data-address="${warehouse.address}"
                                data-description="${warehouse.description}"
                                data-is_main="${warehouse.is_main}">
                                <i class="ti ti-edit me-2"></i> Edit
                            </a>
                            <button type="button" class="dropdown-item text-danger"
                                data-bs-toggle="modal" data-bs-target="#deleteModal"
                                onclick="setDeleteFormAction('/admin/warehouse/destroy/${warehouse.id}')" ${warehouse.is_main ? 'disabled' : ''}>
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

export function showNoResults(message = "No warehouses found matching your search.") {
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
