import { PurchaseReturnCreate } from "./partials/purchase-returns/create/PurchaseReturnCreate.js";
import { PurchaseReturnEdit } from "./partials/purchase-returns/edit/PurchaseReturnEdit.js";
import {
    initBulkSelection,
    getSelectedPurchaseReturnIds,
    clearPurchaseReturnSelection,
} from "./partials/purchase-returns/bulkActions/selection.js";

document.addEventListener("DOMContentLoaded", function () {
    const pathname = window.location.pathname;

    if (sessionStorage.getItem("purchaseReturnBulkDeleteSuccess")) {
        InventMagApp.showToast(
            "Success",
            sessionStorage.getItem("purchaseReturnBulkDeleteSuccess"),
            "success",
        );
        sessionStorage.removeItem("purchaseReturnBulkDeleteSuccess");
    }
    if (sessionStorage.getItem("purchaseReturnBulkCompleteSuccess")) {
        InventMagApp.showToast(
            "Success",
            sessionStorage.getItem("purchaseReturnBulkCompleteSuccess"),
            "success",
        );
        sessionStorage.removeItem("purchaseReturnBulkCompleteSuccess");
    }
    if (sessionStorage.getItem("purchaseReturnBulkCancelSuccess")) {
        InventMagApp.showToast(
            "Success",
            sessionStorage.getItem("purchaseReturnBulkCancelSuccess"),
            "success",
        );
        sessionStorage.removeItem("purchaseReturnBulkCancelSuccess");
    }

    if (pathname.includes("/admin/por/create")) {
        new PurchaseReturnCreate();
    } else if (pathname.includes("/admin/por") && pathname.includes("/edit")) {
        new PurchaseReturnEdit();

        // Logic for prStatusWarningModal
        const editForm = document.getElementById("purchase-return-edit-form");
        if (editForm) {
            const isCompletedOrCanceled =
                editForm.dataset.isCompletedOrCanceled === "true";
            const status = editForm.dataset.status;

            if (isCompletedOrCanceled) {
                var prStatusWarningModal = new bootstrap.Modal(
                    document.getElementById("prStatusWarningModal"),
                );
                var message = "";

                if (status === "Completed") {
                    message =
                        window.translations
                            ?.pr_modal_completed_warning_message ||
                        "This purchase return is completed and cannot be edited.";
                } else if (status === "Canceled") {
                    message =
                        window.translations
                            ?.pr_modal_canceled_warning_message ||
                        "This purchase return is canceled and cannot be edited.";
                }

                document.getElementById("prStatusWarningMessage").innerHTML =
                    message;
                prStatusWarningModal.show();

                // Make form fields readonly
                const formElements = document.querySelectorAll(
                    "#purchase-return-edit-form input, #purchase-return-edit-form select, #purchase-return-edit-form textarea",
                );
                formElements.forEach((element) => {
                    element.setAttribute("readonly", true);
                    if (element.tagName === "SELECT") {
                        element.setAttribute("disabled", true);
                    }
                });

                // Hide submit buttons
                const submitButtons = document.querySelectorAll(
                    '#purchase-return-edit-form button[type="submit"]',
                );
                submitButtons.forEach((button) => {
                    button.style.display = "none";
                });
            }
        }
    } else if (pathname.includes("/admin/por")) {
        initBulkSelection();
        
        // Initialize search functionality for POR index page
        const searchInput = document.getElementById("searchInput");
        if (searchInput) {
            searchInput.addEventListener("keyup", function () {
                const searchTerm = searchInput.value.toLowerCase().trim();
                const tableRows = document.querySelectorAll("#invoiceTableBody tr");
                
                tableRows.forEach((row) => {
                    // Skip empty state rows
                    if (row.querySelector('.empty-state') || row.querySelector('.empty')) {
                        return;
                    }
                    
                    // Get all text content from the row including nested elements
                    const rowText = row.innerText.toLowerCase();
                    const searchText = searchTerm;
                    
                    // Check if any text in the row includes the search term
                    if (rowText.includes(searchText)) {
                        row.style.display = "";
                    } else {
                        row.style.display = "none";
                    }
                });
            });
        }
    }

    const purchaseReturnDetailModal = document.getElementById(
        "purchaseReturnDetailModal",
    );
    if (purchaseReturnDetailModal) {
        purchaseReturnDetailModal.addEventListener(
            "show.bs.modal",
            function (event) {
                const button = event.relatedTarget;
                const prId = button.getAttribute("data-pr-id");
                const modalContent = purchaseReturnDetailModal.querySelector(
                    "#purchaseReturnDetailModalContent",
                );

                const loadingText = window.translations?.loading || "Loading";
                const closeText = window.translations?.close || "Close";

                modalContent.innerHTML = `
                <div class="modal-header">
                    <h5 class="modal-title">${loadingText}...</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="${closeText}"></button>
                </div>
                <div class="modal-body">
                    <div class="d-flex justify-content-center align-items-center" style="min-height: 100px;">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">${loadingText}...</span>
                        </div>
                    </div>
                </div>
            `;

                fetch(`/admin/por/${prId}/modal-view`)
                    .then((response) => {
                        if (!response.ok) {
                            throw new Error("Network response was not ok");
                        }
                        return response.text();
                    })
                    .then((html) => {
                        modalContent.innerHTML = html;
                    })
                    .catch((error) => {
                        console.error(
                            "Error loading purchase return details:",
                            error,
                        );

                        const errorText = window.translations?.error || "Error";
                        const closeText = window.translations?.close || "Close";
                        const failedToLoadDetailsText =
                            window.translations?.failed_to_load_details ||
                            "Failed to load details.";

                        modalContent.innerHTML = `
                        <div class="modal-header">
                            <h5 class="modal-title text-danger">${errorText}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="${closeText}"></button>
                        </div>
                        <div class="modal-body">
                            <p class="text-danger">${failedToLoadDetailsText}</p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">${closeText}</button>
                        </div>
                    `;
                    });

                // Set href for Full View and Edit buttons
                const prModalFullView =
                    purchaseReturnDetailModal.querySelector("#prModalFullView");
                const prModalEdit =
                    purchaseReturnDetailModal.querySelector("#prModalEdit");

                if (prModalFullView) {
                    prModalFullView.href = `/admin/por/${prId}`; // Corrected to match resource route
                }
                if (prModalEdit) {
                    prModalEdit.href = `/admin/por/${prId}/edit`; // Assuming 'edit' route
                }
            },
        );

        // Add event listener for print button inside the modal
        purchaseReturnDetailModal.addEventListener("click", function (event) {
            if (event.target.id === "prModalPrint") {
                const prId =
                    purchaseReturnDetailModal.querySelector(
                        "[data-pr-id-storage]",
                    )?.dataset.prIdStorage ||
                    event.target
                        .closest(".modal")
                        .querySelector("#prModalEdit")
                        ?.href.split("/")
                        .slice(-2)[0] ||
                    event.target
                        .closest(".modal")
                        .querySelector("#prModalFullView")
                        ?.href.split("/")
                        .slice(-1)[0];

                if (prId) {
                    window.open(`/admin/por/print/${prId}`, "_blank");
                } else {
                    window.print();
                }
            }
        });
    }
});

window.bulkDeletePurchaseReturns = function () {
    const selectedIds = getSelectedPurchaseReturnIds();
    if (selectedIds.length === 0) {
        InventMagApp.showToast(
            "Warning",
            window.translations?.select_one_to_delete ||
                "Please select at least one purchase return to delete.",
            "warning",
        );
        return;
    }

    // Set the count in the modal
    document.getElementById("bulkDeleteCount").textContent = selectedIds.length;

    // Show the modal
    var bulkDeleteModal = new bootstrap.Modal(
        document.getElementById("bulkDeletePurchaseReturnModal"),
    );
    bulkDeleteModal.show();

    // Handle confirm button click
    document.getElementById("confirmBulkDeleteBtn").onclick = function () {
        fetch("/admin/por/bulk-delete", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": document
                    .querySelector('meta[name="csrf-token"]')
                    .getAttribute("content"),
            },
            body: JSON.stringify({ ids: selectedIds }),
        })
            .then((response) => response.json())
            .then((data) => {
                if (data.success) {
                    sessionStorage.setItem(
                        "purchaseReturnBulkDeleteSuccess",
                        data.message ||
                            "Purchase returns deleted successfully.",
                    );
                    window.location.reload();
                } else {
                    InventMagApp.showToast(
                        "Error",
                        data.message || "Failed to delete purchase returns.",
                        "error",
                    );
                }
            })
            .catch((error) => {
                InventMagApp.showToast(
                    "Error",
                    "An error occurred while deleting purchase returns.",
                    "error",
                );
            })
            .finally(() => {
                bulkDeleteModal.hide();
            });
    };
};

window.bulkMarkCompletedPurchaseReturns = function () {
    const selectedIds = getSelectedPurchaseReturnIds();
    if (selectedIds.length === 0) {
        InventMagApp.showToast(
            "Warning",
            window.translations?.select_one_to_mark_completed ||
                "Please select at least one purchase return to mark as completed.",
            "warning",
        );
        return;
    }

    // Set the count in the modal
    document.getElementById("bulkCompletedCount").textContent =
        selectedIds.length;

    // Show the modal
    var bulkMarkCompletedModal = new bootstrap.Modal(
        document.getElementById("bulkMarkCompletedPurchaseReturnModal"),
    );
    bulkMarkCompletedModal.show();

    // Handle confirm button click
    document.getElementById("confirmBulkCompletedBtn").onclick = function () {
        fetch("/admin/por/bulk-complete", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": document
                    .querySelector('meta[name="csrf-token"]')
                    .getAttribute("content"),
            },
            body: JSON.stringify({ ids: selectedIds }),
        })
            .then((response) => response.json())
            .then((data) => {
                if (data.success) {
                    sessionStorage.setItem(
                        "purchaseReturnBulkCompleteSuccess",
                        data.message || "Purchase returns marked as completed.",
                    );
                    window.location.reload();
                } else {
                    InventMagApp.showToast(
                        "Error",
                        data.message ||
                            "Failed to mark purchase returns as completed.",
                        "error",
                    );
                }
            })
            .catch((error) => {
                InventMagApp.showToast("Error", "An error occurred.", "error");
            })
            .finally(() => {
                bulkMarkCompletedModal.hide();
            });
    };
};

window.bulkMarkCanceledPurchaseReturns = function () {
    const selectedIds = getSelectedPurchaseReturnIds();
    if (selectedIds.length === 0) {
        InventMagApp.showToast(
            "Warning",
            window.translations?.select_one_to_mark_canceled ||
                "Please select at least one purchase return to mark as canceled.",
            "warning",
        );
        return;
    }

    // Set the count in the modal
    document.getElementById("bulkCanceledCount").textContent =
        selectedIds.length;

    // Show the modal
    var bulkMarkCanceledModal = new bootstrap.Modal(
        document.getElementById("bulkMarkCanceledPurchaseReturnModal"),
    );
    bulkMarkCanceledModal.show();

    // Handle confirm button click
    document.getElementById("confirmBulkCanceledBtn").onclick = function () {
        fetch("/admin/por/bulk-cancel", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": document
                    .querySelector('meta[name="csrf-token"]')
                    .getAttribute("content"),
            },
            body: JSON.stringify({ ids: selectedIds }),
        })
            .then((response) => response.json())
            .then((data) => {
                if (data.success) {
                    sessionStorage.setItem(
                        "purchaseReturnBulkCancelSuccess",
                        data.message || "Purchase returns marked as canceled.",
                    );
                    window.location.reload();
                } else {
                    InventMagApp.showToast(
                        "Error",
                        data.message ||
                            "Failed to mark purchase returns as canceled.",
                        "error",
                    );
                }
            })
            .catch((error) => {
                InventMagApp.showToast("Error", "An error occurred.", "error");
            })
            .finally(() => {
                bulkMarkCanceledModal.hide();
            });
    };
};

export function bulkExportPurchaseReturns(exportOption = "csv") {
    const selected = getSelectedPurchaseReturnIds();
    if (!selected.length) {
        InventMagApp.showToast(
            "Warning",
            "Please select purchase returns to export.",
            "warning",
        );
        return;
    }

    const form = document.createElement("form");
    form.method = "POST";
    form.action = "/admin/por/bulk-export";
    form.style.display = "none";

    const csrf = document.querySelector('meta[name="csrf-token"]');
    if (csrf) {
        const token = document.createElement("input");
        token.type = "hidden";
        token.name = "_token";
        token.value = csrf.getAttribute("content");
        form.appendChild(token);
    }

    const exportOptionInput = document.createElement("input");
    exportOptionInput.type = "hidden";
    exportOptionInput.name = "export_option";
    exportOptionInput.value = exportOption;
    form.appendChild(exportOptionInput);

    selected.forEach((id) => {
        const input = document.createElement("input");
        input.type = "hidden";
        input.name = "ids[]";
        input.value = id;
        form.appendChild(input);
    });

    document.body.appendChild(form);
    form.submit();
    setTimeout(() => document.body.removeChild(form), 2000);
}

export function exportAllPurchaseReturns(exportOption = "csv") {
    const form = document.createElement("form");
    form.method = "POST";
    form.action = "/admin/por/bulk-export";
    form.style.display = "none";

    const csrf = document.querySelector('meta[name="csrf-token"]');
    if (csrf) {
        const token = document.createElement("input");
        token.type = "hidden";
        token.name = "_token";
        token.value = csrf.getAttribute("content");
        form.appendChild(token);
    }

    const exportOptionInput = document.createElement("input");
    exportOptionInput.type = "hidden";
    exportOptionInput.name = "export_option";
    exportOptionInput.value = exportOption;
    form.appendChild(exportOptionInput);

    // Add filters from the page
    const monthSelect = document.querySelector('select[name="month"]');
    const yearSelect = document.querySelector('select[name="year"]');
    const searchInput = document.getElementById("searchInput");

    if (monthSelect && monthSelect.value) {
        const input = document.createElement("input");
        input.type = "hidden";
        input.name = "month";
        input.value = monthSelect.value;
        form.appendChild(input);
    }

    if (yearSelect && yearSelect.value) {
        const input = document.createElement("input");
        input.type = "hidden";
        input.name = "year";
        input.value = yearSelect.value;
        form.appendChild(input);
    }

    if (searchInput && searchInput.value) {
        const input = document.createElement("input");
        input.type = "hidden";
        input.name = "search";
        input.value = searchInput.value;
        form.appendChild(input);
    }

    document.body.appendChild(form);
    form.submit();
    setTimeout(() => document.body.removeChild(form), 2000);
}

window.bulkExportPurchaseReturns = bulkExportPurchaseReturns;
window.exportAllPurchaseReturns = exportAllPurchaseReturns;
window.clearPurchaseReturnSelection = clearPurchaseReturnSelection;
