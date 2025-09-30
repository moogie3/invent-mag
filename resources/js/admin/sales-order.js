import { SalesOrderCreate } from './partials/sales-order/create/SalesOrderCreate.js';
import { SalesOrderEdit } from './partials/sales-order/edit/SalesOrderEdit.js';
import { SalesOrderView } from './partials/sales-order/view/SalesOrderView.js';
import { SalesOrderBulkSelection } from './partials/sales-order/bulkActions/SalesOrderBulkSelection.js';
import { bulkDeleteSales, bulkExportSales, bulkMarkAsPaidSales } from './partials/sales-order/bulkActions/actions.js';
import { initSelectableTable } from "./layouts/selectable-table.js";

// Expose global functions for inline event handlers
window.clearSalesSelection = function () {
    if (window.salesBulkSelection) {
        window.salesBulkSelection.clearSelection();
    }
};
window.getSalesSelectedIds = function () {
    return window.salesBulkSelection ? window.salesBulkSelection.getSelectedIds() : [];
};
window.bulkDeleteSales = bulkDeleteSales;
window.bulkExportSales = bulkExportSales;
window.bulkMarkAsPaidSales = bulkMarkAsPaidSales;

document.addEventListener("DOMContentLoaded", function () {
    initSelectableTable();
    if (sessionStorage.getItem("salesOrderBulkDeleteSuccess")) {
        InventMagApp.showToast(
            "Success",
            sessionStorage.getItem("salesOrderBulkDeleteSuccess"),
            "success"
        );
        sessionStorage.removeItem("salesOrderBulkDeleteSuccess");
    }

    if (sessionStorage.getItem("salesOrderBulkMarkAsPaidSuccess")) {
        InventMagApp.showToast(
            "Success",
            sessionStorage.getItem("salesOrderBulkMarkAsPaidSuccess"),
            "success"
        );
        sessionStorage.removeItem("salesOrderBulkMarkAsPaidSuccess");
    }

    setTimeout(() => {
        const pathname = window.location.pathname;

        try {
            if (pathname.includes("/admin/sales/create")) {
                window.salesApp = new SalesOrderCreate();
                window.shortcutManager.register('ctrl+s', () => {
                    document.getElementById('invoiceForm').submit();
                }, 'Save Sales Order');
                window.shortcutManager.register('alt+n', () => {
                    document.getElementById('addProduct').click();
                }, 'Add Product');
                window.shortcutManager.register('alt+c', () => {
                    document.getElementById('clearProducts').click();
                }, 'Clear All Products');
            } else if (
                pathname.includes("/admin/sales/edit/") ||
                pathname.match(/\/admin\/sales\/edit\/\d+$/)
            ) {
                window.salesApp = new SalesOrderEdit();
                 window.shortcutManager.register('ctrl+s', () => {
                    document.getElementById('edit-sales-form').submit();
                }, 'Save Sales Order');
            } else if (
                pathname.includes("/admin/sales/modal") ||
                (pathname.includes("/admin/sales") &&
                    pathname.match(/\/\d+$/)) ||
                pathname.includes("/admin/sales/show")
            ) {
                window.salesApp = new SalesOrderView();
            } else if (
                pathname === "/admin/sales" ||
                pathname.includes("/admin/sales?") ||
                pathname.includes("/admin/sales/")
            ) {
                window.salesApp = new SalesOrderView();

                const rowCheckboxes =
                    document.querySelectorAll(".row-checkbox");
                if (
                    typeof SalesOrderBulkSelection !== "undefined" &&
                    rowCheckboxes.length > 0
                ) {
                    window.salesBulkSelection = new SalesOrderBulkSelection();
                } else {
                    const bulkActionsBar =
                        document.getElementById("bulkActionsBar");
                    if (bulkActionsBar) {
                        bulkActionsBar.style.display = "none";
                    }
                }
            }

            if (pathname.includes("edit") && pathname.includes("sales")) {
                console.log(
                    "Force initializing SalesOrderEdit due to edit page detection"
                );
                window.salesApp = new SalesOrderEdit();
            }
        } catch (error) {
            console.error("Error initializing Sales Order App:", error);

            if (pathname.includes("edit") && pathname.includes("sales")) {
                try {
                    console.log(
                        "Emergency fallback: Attempting to initialize SalesOrderEdit"
                    );
                    window.salesApp = new SalesOrderEdit();
                } catch (fallbackError) {
                    console.error(
                        "Fallback initialization also failed:",
                        fallbackError
                    );
                }
            }
        }
    }, 250);
});

// Fallback for setDeleteFormAction if initialization fails
if (typeof window.setDeleteFormAction === "undefined") {
    window.setDeleteFormAction = function (url) {
        const deleteForm = document.getElementById("deleteForm");
        if (deleteForm) {
            deleteForm.action = url;
            console.log("Fallback: Delete form action set to:", url);
        } else {
            console.error("Fallback: Delete form not found");
        }
    };
}

// Fallback for loadSalesDetails if initialization fails
if (typeof window.loadSalesDetails === "undefined") {
    window.loadSalesDetails = function (id) {
        console.log("Fallback loadSalesDetails called for ID:", id);
    };
}

// JavaScript for Sales Expiring Soon functionality
document.addEventListener('DOMContentLoaded', function () {
    const expiringSalesModalElement = document.getElementById('expiringSalesModal');
    if (expiringSalesModalElement) {
        expiringSalesModalElement.addEventListener('show.bs.modal', function () {
            const tableBody = document.getElementById('expiringSalesTableBody');
            tableBody.innerHTML = '<tr><td colspan="5" class="text-center py-4"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div><p class="mt-3 text-muted">Loading expiring sales invoices...</p></td></tr>';

            fetch('/admin/sales/expiring-soon') // This endpoint needs to be defined in web.php
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok ' + response.statusText);
                    }
                    return response.json();
                })
                .then(data => {
                    tableBody.innerHTML = ''; // Clear loading indicator
                    if (data.length === 0) {
                        tableBody.innerHTML = '<tr><td colspan="5" class="text-center py-4 text-muted">No sales invoices expiring soon.</td></tr>';
                        return;
                    }

                    data.forEach(sale => {
                        const row = document.createElement('tr');
                        row.innerHTML = `
                            <td>${sale.invoice}</td>
                            <td>${sale.customer ? sale.customer.name : 'N/A'}</td>
                            <td class="text-center">${sale.due_date}</td>
                            <td class="text-end">${sale.total}</td>
                            <td class="text-end">
                                <a href="/admin/sales/view/${sale.id}" class="btn btn-sm btn-primary">
                                    <i class="ti ti-eye me-1"></i> View
                                </a>
                            </td>
                        `;
                        tableBody.appendChild(row);
                    });
                })
                .catch(error => {
                    console.error('Error fetching expiring sales invoices:', error);
                    tableBody.innerHTML = '<tr><td colspan="5" class="text-center py-4 text-danger">Error loading data. Please try again.</td></tr>';
                });
        });
    }
});
