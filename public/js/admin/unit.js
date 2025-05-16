document.addEventListener("DOMContentLoaded", function () {
    const editUnitModal = document.getElementById("editUnitModal");

    editUnitModal.addEventListener("show.bs.modal", function (event) {
        // Get the button that triggered the modal
        const button = event.relatedTarget;

        // Get unit data from the button attributes
        const unitId = button.getAttribute("data-id");
        const unitSymbol = button.getAttribute("data-symbol");
        const unitName = button.getAttribute("data-name");

        // Populate the form fields inside the modal
        document.getElementById("unitId").value = unitId;
        document.getElementById("unitSymbolEdit").value = unitSymbol;
        document.getElementById("unitNameEdit").value = unitName;

        const routeBase = document.getElementById("updateRouteBase").value;
        document.getElementById("editUnitForm").action =
            routeBase + "/" + unitId;
    });
});
