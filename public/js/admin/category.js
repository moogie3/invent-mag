document.addEventListener("DOMContentLoaded", function () {
    const editCategoryModal = document.getElementById("editCategoryModal");

    editCategoryModal.addEventListener("show.bs.modal", function (event) {
        // Get the button that triggered the modal
        const button = event.relatedTarget;

        // Get category data from the button attributes
        const categoryId = button.getAttribute("data-id");
        const categoryName = button.getAttribute("data-name");
        const categoryDescription = button.getAttribute("data-description");

        // Populate the form fields inside the modal
        document.getElementById("categoryId").value = categoryId;
        document.getElementById("categoryNameEdit").value = categoryName;
        document.getElementById("categoryDescriptionEdit").value =
            categoryDescription;

        const routeBase = document.getElementById("updateRouteBase").value;
        document.getElementById("editCategoryForm").action =
            routeBase + "/" + categoryId;
    });
});
