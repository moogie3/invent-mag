export function handleEditTaxModal() {
    const editTaxModal = document.getElementById("editTaxModal");

    if (editTaxModal) {
        editTaxModal.addEventListener("show.bs.modal", function (event) {
            const button = event.relatedTarget;
            if (button) {
                const id = button.getAttribute("data-id");
                const name = button.getAttribute("data-name");
                const rate = button.getAttribute("data-rate");
                const type = button.getAttribute("data-type");
                const description = button.getAttribute("data-description");
                const isActive = button.getAttribute("data-is-active") === "1";
                const isDefault = button.getAttribute("data-is-default") === "1";

                const idInput = document.getElementById("editTaxId");
                const nameInput = document.getElementById("editTaxName");
                const rateInput = document.getElementById("editTaxRate");
                const typeSelect = document.getElementById("editTaxType");
                const descriptionInput = document.getElementById("editTaxDescription");
                const activeCheckbox = document.getElementById("editTaxActive");
                const defaultCheckbox = document.getElementById("editTaxDefault");

                if (idInput) idInput.value = id;
                if (nameInput) nameInput.value = name;
                if (rateInput) rateInput.value = rate;
                if (typeSelect) typeSelect.value = type;
                if (descriptionInput) descriptionInput.value = description || "";
                if (activeCheckbox) activeCheckbox.checked = isActive;
                if (defaultCheckbox) defaultCheckbox.checked = isDefault;

                const base = document.getElementById("updateTaxRouteBase");
                const editForm = document.getElementById("editTaxForm");
                if (base && editForm) {
                    editForm.action = base.value + "/" + id;
                }
            }
        });
    }
}
