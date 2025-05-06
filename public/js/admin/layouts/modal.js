document.addEventListener("DOMContentLoaded", function () {
    var errorModalElement = document.getElementById("errorModal");
    var errorModal = new bootstrap.Modal(errorModalElement);
    var backdropSelector = ".modal-backdrop-custom";

    function removeBackdrop() {
        document.querySelector(backdropSelector)?.remove();
    }

    function showModal(modal) {
        document.body.style.overflow = "hidden"; // Prevent scrollbar flicker
        modal.show();
        document.body.insertAdjacentHTML(
            "beforeend",
            '<div class="modal-backdrop fade show modal-backdrop-custom"></div>'
        );
    }

    function hideModal(modal) {
        modal.hide();
        removeBackdrop();
        document.body.style.overflow = ""; // Restore scrollbar
    }

    // Show error modal if it exists
    if (errorModalElement) {
        setTimeout(() => showModal(errorModal), 100);

        // Auto-hide after 2 seconds
        setTimeout(() => hideModal(errorModal), 2000);

        // Add close handlers for buttons with data-bs-dismiss attribute
        const closeButtons = errorModalElement.querySelectorAll(
            '[data-bs-dismiss="modal"]'
        );
        closeButtons.forEach((button) => {
            button.addEventListener("click", function () {
                hideModal(errorModal);
            });
        });

        // Add close handler for Enter key
        errorModalElement.addEventListener("keydown", function (event) {
            if (event.key === "Enter") {
                hideModal(errorModal);
            }
        });

        errorModalElement.addEventListener("hidden.bs.modal", () => {
            removeBackdrop();
            document.body.style.overflow = "";
        });
    }
});

document.addEventListener("DOMContentLoaded", function () {
    var successModalElement = document.getElementById("successModal");
    var successModal = new bootstrap.Modal(successModalElement);
    var backdropSelector = ".modal-backdrop-custom";

    function removeBackdrop() {
        document.querySelector(backdropSelector)?.remove();
    }

    function showModal(modal) {
        document.body.style.overflow = "hidden"; // Prevent scrollbar flicker
        modal.show();
        document.body.insertAdjacentHTML(
            "beforeend",
            '<div class="modal-backdrop fade show modal-backdrop-custom"></div>'
        );
    }

    function hideModal(modal) {
        modal.hide();
        removeBackdrop();
        document.body.style.overflow = ""; // Restore scrollbar
    }

    // Show success modal if it exists
    if (successModalElement) {
        setTimeout(() => showModal(successModal), 100);

        // Auto-hide after 2 seconds
        setTimeout(() => hideModal(successModal), 2000);

        // Add close handlers for buttons with data-bs-dismiss attribute
        const closeButtons = successModalElement.querySelectorAll(
            '[data-bs-dismiss="modal"]'
        );
        closeButtons.forEach((button) => {
            button.addEventListener("click", function () {
                hideModal(successModal);
            });
        });

        // Add close handler for Enter key
        successModalElement.addEventListener("keydown", function (event) {
            if (event.key === "Enter") {
                hideModal(successModal);
            }
        });

        successModalElement.addEventListener("hidden.bs.modal", () => {
            removeBackdrop();
            document.body.style.overflow = "";
        });
    }
});
