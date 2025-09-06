export function handleEditCategoryModal() {
    const editCategoryModal = document.getElementById("editCategoryModal");

    if (editCategoryModal) {
        editCategoryModal.addEventListener("show.bs.modal", function (event) {
            const button = event.relatedTarget;
            if (button) {
                const categoryId = button.getAttribute("data-id");
                const categoryName = button.getAttribute("data-name");
                const categoryDescription = button.getAttribute("data-description");

                // Populate the modal's form fields
                const categoryIdInput = document.getElementById("categoryId");
                const categoryNameEditInput = document.getElementById("categoryNameEdit");
                const categoryDescriptionEditInput = document.getElementById("categoryDescriptionEdit");

                if (categoryIdInput) categoryIdInput.value = categoryId;
                if (categoryNameEditInput) categoryNameEditInput.value = categoryName;
                if (categoryDescriptionEditInput) categoryDescriptionEditInput.value = categoryDescription;

                // Update the form's action URL
                const routeBase = document.getElementById("updateRouteBase").value;
                const editForm = document.getElementById("editCategoryForm");
                if (editForm) {
                    editForm.action = `${routeBase}/${categoryId}`;
                }
            }
        });
    }
}
