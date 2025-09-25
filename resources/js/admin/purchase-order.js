import { PurchaseOrderCreate } from "./partials/purchase-order/create/PurchaseOrderCreate.js";
import { PurchaseOrderEdit } from "./partials/purchase-order/edit/PurchaseOrderEdit.js";
import { PurchaseOrderView } from "./partials/purchase-order/view/PurchaseOrderView.js";
import { PurchaseOrderBulkSelection } from "./partials/purchase-order/bulkActions/PurchaseOrderBulkSelection.js";
import { initSearch } from "./partials/purchase-order/search/main.js";
import {
    bulkDeletePO,
    bulkExportPO,
    bulkMarkAsPaidPO,
} from "./partials/purchase-order/bulkActions/actions.js";

// Expose global functions for inline event handlers
window.bulkDeletePO = bulkDeletePO;
window.bulkExportPO = bulkExportPO;
window.bulkMarkAsPaidPO = bulkMarkAsPaidPO;

// Keep the existing DOMContentLoaded initialization
document.addEventListener("DOMContentLoaded", function () {
    if (sessionStorage.getItem("purchaseOrderBulkDeleteSuccess")) {
        InventMagApp.showToast(
            "Success",
            sessionStorage.getItem("purchaseOrderBulkDeleteSuccess"),
            "success"
        );
        sessionStorage.removeItem("purchaseOrderBulkDeleteSuccess");
    }

    if (sessionStorage.getItem("purchaseOrderBulkMarkAsPaidSuccess")) {
        InventMagApp.showToast(
            "Success",
            sessionStorage.getItem("purchaseOrderBulkMarkAsPaidSuccess"),
            "success"
        );
        sessionStorage.removeItem("purchaseOrderBulkMarkAsPaidSuccess");
    }

    setTimeout(() => {
        const pathname = window.location.pathname;

        try {
            if (pathname.includes("/admin/po/create")) {
                window.poApp = new PurchaseOrderCreate();
            } else if (
                pathname.includes("/admin/po/edit") ||
                (pathname.includes("/admin/po") &&
                    pathname.match(/\/\d+\/edit$/))
            ) {
                window.poApp = new PurchaseOrderEdit();
            } else if (
                pathname.includes("/admin/po/modal") ||
                (pathname.includes("/admin/po") && pathname.match(/\/\d+$/)) ||
                pathname.includes("/admin/po/show")
            ) {
                window.poApp = new PurchaseOrderView();
            } else if (
                pathname === "/admin/po" ||
                pathname.includes("/admin/po?") ||
                pathname.includes("/admin/po/")
            ) {
                window.poApp = new PurchaseOrderView();

                const rowCheckboxes =
                    document.querySelectorAll(".row-checkbox");
                if (
                    typeof PurchaseOrderBulkSelection !== "undefined" &&
                    rowCheckboxes.length > 0
                ) {
                    window.bulkSelection = new PurchaseOrderBulkSelection();
                } else {
                    const bulkActionsBar =
                        document.getElementById("bulkActionsBar");
                    if (bulkActionsBar) {
                        bulkActionsBar.style.display = "none";
                    }
                }

                initSearch();
            }
        } catch (error) {
            console.error("Error initializing Purchase Order App:", error);
            window.setDeleteFormAction = function (url) {
                const deleteForm = document.getElementById("deleteForm");
                if (deleteForm) {
                    deleteForm.action = url;
                    console.log("Fallback: Delete form action set to:", url);
                } else {
                    console.error("Fallback: Delete form not found");
                }
            };

            window.loadPoDetails = function (id) {
                console.log("Fallback loadPoDetails called for ID:", id);
            };
        }
    }, 250);
});

// JavaScript for Purchase Order Expiring Soon functionality
document.addEventListener('DOMContentLoaded', function () {
    const expiringPurchaseModalElement = document.getElementById('expiringPurchaseModal');
    if (expiringPurchaseModalElement) {
        expiringPurchaseModalElement.addEventListener('show.bs.modal', function () {
            const tableBody = document.getElementById('expiringPurchaseTableBody');
            tableBody.innerHTML = '<tr><td colspan="5" class="text-center py-4"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div><p class="mt-3 text-muted">Loading expiring purchase orders...</p></td></tr>';

            fetch('/admin/po/expiring-soon') // This endpoint needs to be defined in web.php
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok ' + response.statusText);
                    }
                    return response.json();
                })
                .then(data => {
                    tableBody.innerHTML = ''; // Clear loading indicator
                    if (data.length === 0) {
                        tableBody.innerHTML = '<tr><td colspan="5" class="text-center py-4 text-muted">No purchase orders expiring soon.</td></tr>';
                        return;
                    }

                    data.forEach(po => {
                        const row = document.createElement('tr');
                        row.innerHTML = `
                            <td>${po.invoice}</td>
                            <td>${po.supplier ? po.supplier.name : 'N/A'}</td>
                            <td class="text-center">${po.due_date}</td>
                            <td class="text-end">${po.total}</td>
                            <td class="text-end">
                                <a href="/admin/po/view/${po.id}" class="btn btn-sm btn-primary">
                                    <i class="ti ti-eye me-1"></i> View
                                </a>
                            </td>
                        `;
                        tableBody.appendChild(row);
                    });
                })
                .catch(error => {
                    console.error('Error fetching expiring purchase orders:', error);
                    tableBody.innerHTML = '<tr><td colspan="5" class="text-center py-4 text-danger">Error loading data. Please try again.</td></tr>';
                });
        });
    }
});
