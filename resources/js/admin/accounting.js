document.addEventListener("DOMContentLoaded", function () {
    const applyCoaTemplateBtn = document.getElementById("applyCoaTemplateBtn");
    const coaTemplateConfirmationModal = new bootstrap.Modal(
        document.getElementById("coaTemplateConfirmationModal")
    );
    const coaTemplateConfirmationModalMessage = document.getElementById(
        "coaTemplateConfirmationModalMessage"
    );
    const coaTemplateConfirmationModalConfirmBtn = document.getElementById(
        "coaTemplateConfirmationModalConfirmBtn"
    );

    if (applyCoaTemplateBtn) {
        applyCoaTemplateBtn.addEventListener("click", function () {
            const templateSelect = document.getElementById("coaTemplateSelect");
            const selectedOption = templateSelect.options[templateSelect.selectedIndex];
            const selectedTemplateDisplayName = selectedOption.textContent;
            const selectedTemplateValue = templateSelect.value;
            const originalButtonText = this.innerHTML;

            // Set modal message
            coaTemplateConfirmationModalMessage.innerHTML = `Are you sure you want to apply the <strong>${selectedTemplateDisplayName}</strong> Chart of Accounts template? This will overwrite existing accounts and cannot be undone.`;
            coaTemplateConfirmationModal.show();

            // Handle confirmation
            coaTemplateConfirmationModalConfirmBtn.onclick = () => {
                coaTemplateConfirmationModal.hide();
                this.disabled = true;
                this.innerHTML =
                    '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Applying...';

                fetch("/admin/settings/apply-coa-template", {
                    method: "POST",
                    headers: {
                        "X-CSRF-TOKEN": document
                            .querySelector('meta[name="csrf-token"]')
                            .getAttribute("content"),
                        Accept: "application/json",
                        "X-Requested-With": "XMLHttpRequest",
                        "Content-Type": "application/json",
                    },
                    body: JSON.stringify({
                        template: selectedTemplateValue,
                    }),
                })
                    .then((response) => response.json())
                    .then((data) => {
                        if (data.success) {
                            InventMagApp.showToast(
                                "Success",
                                data.message,
                                "success"
                            );
                            setTimeout(() => window.location.reload(), 1500);
                        } else {
                            InventMagApp.showToast(
                                "Error",
                                data.message || "An error occurred.",
                                "error"
                            );
                        }
                    })
                    .catch((error) => {
                        console.error("Error:", error);
                        InventMagApp.showToast(
                            "Error",
                            "An unexpected error occurred.",
                            "error"
                        );
                    })
                    .finally(() => {
                        this.disabled = false;
                        this.innerHTML = originalButtonText;
                    });
            };
        });
    }
});
