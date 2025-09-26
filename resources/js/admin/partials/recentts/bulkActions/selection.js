import { updateBulkActions } from '../utils/dom.js';

export function initBulkSelection() {
    console.log('initBulkSelection called');
    const selectAll = document.getElementById("selectAll");
    const rowCheckboxes = document.querySelectorAll(".row-checkbox");

    selectAll?.addEventListener("change", function () {
        rowCheckboxes.forEach((checkbox) => {
            checkbox.checked = this.checked;
        });
        updateBulkActions();
    });

    rowCheckboxes.forEach((checkbox) => {
        checkbox.addEventListener("change", updateBulkActions);
    });
}

export function smartSelectUnpaidOnly() {
    const rowCheckboxes = document.querySelectorAll(".row-checkbox");
    let excludedCount = 0;

    rowCheckboxes.forEach((checkbox) => {
        const row = checkbox.closest("tr");
        const statusBadge = row.querySelector(".badge");
        const status = statusBadge ? statusBadge.textContent.trim() : "";

        const isPaid =
            status === "Paid" ||
            status.toLowerCase().includes("paid") ||
            statusBadge?.classList.contains("bg-success-lt") ||
            statusBadge?.classList.contains("bg-success");

        if (isPaid) {
            checkbox.checked = false;
            row.classList.add("table-warning");
            setTimeout(() => {
                row.classList.remove("table-warning");
            }, 2000);
            excludedCount++;
        } else {
            checkbox.checked = true;
        }
    });

    updateBulkActions();

    if (excludedCount > 0) {
        InventMagApp.showToast(
            "Info",
            `${excludedCount} paid transaction(s) were excluded from selection.`,
            "info",
            3000
        );
    }
}

export function bulkMarkAsPaid() {
    const selected = Array.from(
        document.querySelectorAll(".row-checkbox:checked")
    );

    if (selected.length === 0) {
        smartSelectUnpaidOnly();

        const newSelected = Array.from(
            document.querySelectorAll(".row-checkbox:checked")
        );

        if (newSelected.length === 0) {
            InventMagApp.showToast(
                "Info",
                "No unpaid transactions available to mark as paid.",
                "info"
            );
            return;
        }
    } else {
        const selectedPaidTransactions = selected.filter((checkbox) => {
            const row = checkbox.closest("tr");
            const statusBadge = row.querySelector(".badge");
            const status = statusBadge ? statusBadge.textContent.trim() : "";
            const isPaid =
                status === "Paid" ||
                status.toLowerCase().includes("paid") ||
                statusBadge?.classList.contains("bg-success-lt") ||
                statusBadge?.classList.contains("bg-success");
            return isPaid;
        });

        if (selectedPaidTransactions.length > 0) {
            selectedPaidTransactions.forEach((checkbox) => {
                checkbox.checked = false;
                const row = checkbox.closest("tr");
                row.classList.add("table-warning");
                setTimeout(() => {
                    row.classList.remove("table-warning");
                }, 2000);
            });

            updateBulkActions();

            InventMagApp.showToast(
                "Warning",
                `${selectedPaidTransactions.length} paid transaction(s) were excluded from selection.`,
                "warning"
            );

            const remainingSelected = Array.from(
                document.querySelectorAll(".row-checkbox:checked")
            );

            if (remainingSelected.length === 0) {
                return;
            }
        }
    }

    const finalSelected = Array.from(
        document.querySelectorAll(".row-checkbox:checked")
    ).map((cb) => cb.value);

    document.getElementById("bulkMarkPaidCount").textContent =
        finalSelected.length;

    const modal = new bootstrap.Modal(
        document.getElementById("bulkMarkAsPaidModal")
    );
    modal.show();
}

export function clearSelection() {
    const selectAll = document.getElementById("selectAll");
    if (selectAll) {
        selectAll.checked = false;
    }

    const rowCheckboxes = document.querySelectorAll(".row-checkbox");
    rowCheckboxes.forEach((checkbox) => {
        checkbox.checked = false;
    });

    updateBulkActions();
}