export function handleEditUnitModal() {
    const editUnitModal = document.getElementById("editUnitModal");

    if (editUnitModal) {
        editUnitModal.addEventListener("show.bs.modal", function (event) {
            const button = event.relatedTarget;
            if (button) {
                const unitId = button.getAttribute("data-id");
                const unitName = button.getAttribute("data-name");
                const unitSymbol = button.getAttribute("data-symbol");

                const unitIdInput = document.getElementById("unitId");
                const unitNameEditInput = document.getElementById("unitNameEdit");
                const unitSymbolEditInput = document.getElementById("unitSymbolEdit");

                if (unitIdInput) unitIdInput.value = unitId;
                if (unitNameEditInput) unitNameEditInput.value = unitName;
                if (unitSymbolEditInput) unitSymbolEditInput.value = unitSymbol;

                const routeBase = document.getElementById("updateRouteBase").value;
                const editForm = document.getElementById("editUnitForm");
                if (editForm) {
                    editForm.action = `${routeBase}/${unitId}`;
                }
            }
        });
    }
}
