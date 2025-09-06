import { SalesOrderCreate } from './partials/sales-order/create/SalesOrderCreate.js';
import { SalesOrderEdit } from './partials/sales-order/edit/SalesOrderEdit.js';
import { SalesOrderView } from './partials/sales-order/view/SalesOrderView.js';
import { SalesOrderBulkSelection } from './partials/sales-order/bulkActions/SalesOrderBulkSelection.js';
import { bulkDeleteSales, bulkExportSales, bulkMarkAsPaidSales } from './partials/sales-order/bulkActions/actions.js';

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
    if (sessionStorage.getItem("salesOrderBulkDeleteSuccess")) {
        showToast(
            "Success",
            sessionStorage.getItem("salesOrderBulkDeleteSuccess"),
            "success"
        );
        sessionStorage.removeItem("salesOrderBulkDeleteSuccess");
    }

    if (sessionStorage.getItem("salesOrderBulkMarkAsPaidSuccess")) {
        showToast(
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
            } else if (
                pathname.includes("/admin/sales/edit/") ||
                pathname.match(/\/admin\/sales\/edit\/\d+$/)
            ) {
                window.salesApp = new SalesOrderEdit();
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