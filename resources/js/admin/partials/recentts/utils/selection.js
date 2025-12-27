export function clearSelection() {
    document.querySelectorAll(".row-checkbox").forEach((checkbox) => {
        checkbox.checked = false;
    });
    document.getElementById("selectAll").checked = false;
    document.getElementById("bulkActionsBar").style.display = "none";
}
