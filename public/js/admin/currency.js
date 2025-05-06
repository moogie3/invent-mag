document.addEventListener("DOMContentLoaded", function () {
    const showModalButton = document.getElementById("showModalButton");
    const confirmSubmitButton = document.getElementById("confirmSubmit");
    const currencySettingsForm = document.getElementById(
        "currencySettingsForm"
    );

    showModalButton.addEventListener("click", function () {
        const confirmModal = new bootstrap.Modal(
            document.getElementById("confirmModal")
        );
        confirmModal.show();
    });

    confirmSubmitButton.addEventListener("click", function () {
        currencySettingsForm.submit();
    });
});
