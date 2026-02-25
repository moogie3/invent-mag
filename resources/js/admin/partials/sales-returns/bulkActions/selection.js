let selectedSalesReturnIds = new Set();

export function getSelectedSalesReturnIds() {
    return Array.from(selectedSalesReturnIds);
}

export function clearSalesReturnSelection() {
    const selectAll = document.getElementById("selectAll");
    const checkboxes = document.querySelectorAll(".row-checkbox");
    const bulkBar = document.getElementById("bulkActionsBar");

    selectedSalesReturnIds.clear();

    if (selectAll) {
        selectAll.checked = false;
        selectAll.indeterminate = false;
    }
    checkboxes.forEach((cb) => (cb.checked = false));
    if (bulkBar) bulkBar.style.display = "none";
}

function updateSelectAllState(selectAll, rowCheckboxes) {
    const checked = document.querySelectorAll(".row-checkbox:checked").length;
    const total = rowCheckboxes.length;

    if (checked === 0) {
        selectAll.indeterminate = false;
        selectAll.checked = false;
    } else if (checked === total) {
        selectAll.indeterminate = false;
        selectAll.checked = true;
    } else {
        selectAll.indeterminate = true;
        selectAll.checked = false;
    }
}

function updateBulkActionsBar(rowCheckboxes, bulkBar, selectedCount) {
    const checked = document.querySelectorAll(".row-checkbox:checked").length;
    bulkBar.style.display = checked > 0 ? "block" : "none";
    selectedCount.textContent = checked;
}

function updateBulkUI(elements) {
    updateSelectAllState(elements.selectAll, elements.rowCheckboxes);
    updateBulkActionsBar(
        elements.rowCheckboxes,
        elements.bulkBar,
        elements.selectedCount
    );
}

function setupBulkSelectionListeners({
    selectAll,
    rowCheckboxes,
    bulkBar,
    selectedCount,
}) {
    selectAll.addEventListener("change", (e) => {
        rowCheckboxes.forEach((cb) => {
            cb.checked = e.target.checked;
            if (e.target.checked) {
                selectedSalesReturnIds.add(cb.value);
            } else {
                selectedSalesReturnIds.delete(cb.value);
            }
        });
        updateBulkUI({ selectAll, rowCheckboxes, bulkBar, selectedCount });
    });

    rowCheckboxes.forEach((cb) => {
        cb.addEventListener("change", () => {
            if (cb.checked) {
                selectedSalesReturnIds.add(cb.value);
            } else {
                selectedSalesReturnIds.delete(cb.value);
            }
            updateSelectAllState(selectAll, rowCheckboxes);
            updateBulkActionsBar(rowCheckboxes, bulkBar, selectedCount);
        });
    });
}

export function initBulkSelection() {
    let attempts = 0;
    const maxAttempts = 5;

    const tryInit = () => {
        attempts++;
        const elements = {
            selectAll: document.getElementById("selectAll"),
            rowCheckboxes: document.querySelectorAll(".row-checkbox"),
            bulkBar: document.getElementById("bulkActionsBar"),
            selectedCount: document.getElementById("selectedCount"),
        };

        if (!elements.selectAll || !elements.bulkBar) {
            if (attempts < maxAttempts) {
                setTimeout(tryInit, 300);
                return;
            }
            // console.warn("Bulk selection elements not found");
            return;
        }

        // If there are no checkboxes, we don't need to set up listeners or show the bar
        if (elements.rowCheckboxes.length === 0) {
            return;
        }

        setupBulkSelectionListeners(elements);
        updateBulkUI(elements);
        restoreCheckboxStates();
    };

    tryInit();
}

export function restoreCheckboxStates() {
    document.querySelectorAll(".row-checkbox").forEach((checkbox) => {
        if (selectedSalesReturnIds.has(checkbox.value)) {
            checkbox.checked = true;
        }
    });

    updateSelectAllCheckbox();
}

function updateSelectAllCheckbox() {
    const selectAllCheckbox = document.getElementById("selectAll");
    const rowCheckboxes = document.querySelectorAll(".row-checkbox");
    const checkedCount = document.querySelectorAll(
        ".row-checkbox:checked"
    ).length;

    if (selectAllCheckbox) {
        if (checkedCount === 0) {
            selectAllCheckbox.checked = false;
            selectAllCheckbox.indeterminate = false;
        } else if (checkedCount === rowCheckboxes.length) {
            selectAllCheckbox.checked = true;
            selectAllCheckbox.indeterminate = false;
        } else {
            selectAllCheckbox.checked = false;
            selectAllCheckbox.indeterminate = true;
        }
    }
}

export function updateBulkActionsBarVisibility() {
    const bulkActionsBar = document.getElementById("bulkActionsBar");
    const selectedCount = document.getElementById("selectedCount");

    if (bulkActionsBar && selectedCount) {
        const count = selectedSalesReturnIds.size;
        selectedCount.textContent = count;

        if (count > 0) {
            bulkActionsBar.style.display = "block";
        } else {
            bulkActionsBar.style.display = "none";
        }
    }
}