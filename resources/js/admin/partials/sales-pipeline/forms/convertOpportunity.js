import { SALES_PIPELINE_ROUTES } from '../common/constants.js';

const convertOpportunityForm = document.getElementById(
    "convertOpportunityForm"
);

export function initConvertOpportunityForm() {
    if (convertOpportunityForm) {
        convertOpportunityForm.addEventListener("submit", function (e) {
            e.preventDefault();
            const form = e.target;
            const url = form.action;
            const formData = new FormData(form);
            const opportunityId = document.getElementById(
                "convertOpportunityId"
            ).value;

            fetch(url, {
                method: "POST",
                headers: {
                    "X-CSRF-TOKEN": document
                        .querySelector('meta[name="csrf-token"]')
                        .getAttribute("content"),
                    Accept: "application/json",
                },
                body: formData,
            })
                .then((response) => response.json())
                .then((data) => {
                    const convertModal = bootstrap.Modal.getInstance(
                        document.getElementById("convertOpportunityModal")
                    );
                    convertModal.hide();

                    if (data.type === "success") {
                        window.InventMagApp.showToast("Success", data.message, "success");

                        const opportunityCard = document.querySelector(
                            `.opportunity-card[data-opportunity-id='${opportunityId}']`
                        );
                        if (opportunityCard) {
                            const statusBadge =
                                opportunityCard.querySelector(".badge");
                            if (statusBadge) {
                                statusBadge.textContent = "Converted";
                                statusBadge.classList.remove(
                                    "badge-success-lt"
                                );
                                statusBadge.classList.add("badge-info-lt");
                            }

                            const convertButton = opportunityCard.querySelector(
                                ".convert-opportunity-btn"
                            );
                            if (convertButton) {
                                convertButton.remove();
                            }
                        }
                    } else {
                        window.InventMagApp.showToast("Error", data.message, "error");
                    }
                })
                .catch((error) => {
                    console.error("Error:", error);
                    const convertModal = bootstrap.Modal.getInstance(
                        document.getElementById("convertOpportunityModal")
                    );
                    convertModal.hide();
                    window.InventMagApp.showToast(
                        "Error",
                        "An unexpected error occurred.",
                        "error"
                    );
                });
        });
    }
}