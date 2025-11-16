export function updateBulkActions() {
    const checkedBoxes = document.querySelectorAll(".row-checkbox:checked");
    const rowCheckboxes = document.querySelectorAll(".row-checkbox");
    const bulkActionsBar = document.getElementById("bulkActionsBar");
    const selectedCount = document.getElementById("selectedCount");
    const selectAll = document.getElementById("selectAll");
    const count = checkedBoxes.length;
    // // console.log('updateBulkActions called, checked count:', count);

    if (selectedCount) selectedCount.textContent = count;

    if (bulkActionsBar) {
        bulkActionsBar.style.display = count > 0 ? "block" : "none";
    }

    if (selectAll) {
        const totalCheckboxes = rowCheckboxes.length;
        const checkedCount = checkedBoxes.length;

        selectAll.indeterminate =
            checkedCount > 0 && checkedCount < totalCheckboxes;
        selectAll.checked =
            totalCheckboxes > 0 && checkedCount === totalCheckboxes;
    }
}
