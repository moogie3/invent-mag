export function clearSelection() {
    document.querySelectorAll(".row-checkbox").forEach((checkbox) => {
        checkbox.checked = false;
    });
    const selectAll = document.getElementById("selectAll");
    const bulkActionsBar = document.getElementById("bulkActionsBar");
    if (selectAll) selectAll.checked = false;
    if (bulkActionsBar) bulkActionsBar.style.display = "none";
}
