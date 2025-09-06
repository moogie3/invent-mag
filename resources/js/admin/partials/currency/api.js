import * as elements from './elements.js';
import { getCurrentFormValues, restoreOriginalValues, updateHiddenFields } from './form.js';
import { updateOriginalValues } from './state.js';
import { updateFormatPreview } from './preview.js';

export function handleFormSubmission() {
    if (elements.confirmSubmitButton && elements.currencySettingsForm) {
        elements.confirmSubmitButton.addEventListener("click", function () {
            const confirmModal = bootstrap.Modal.getInstance(
                document.getElementById("confirmModal")
            );
            if (confirmModal) {
                confirmModal.hide();
                confirmModal._element.addEventListener(
                    "hidden.bs.modal",
                    function handler() {
                        confirmModal._element.removeEventListener(
                            "hidden.bs.modal",
                            handler
                        );
                        const backdrops =
                            document.querySelectorAll(".modal-backdrop");
                        backdrops.forEach((backdrop) => backdrop.remove());
                    }
                );
            }

            updateHiddenFields();

            const formData = new FormData(elements.currencySettingsForm);

            const selectedOption =
                elements.selectedCurrency.options[elements.selectedCurrency.selectedIndex];
            formData.set("currency_code", selectedOption.value);
            formData.set("locale", selectedOption.dataset.locale);
            formData.set("currency_symbol", selectedOption.dataset.symbol);

            fetch(elements.currencySettingsForm.action, {
                method: elements.currencySettingsForm.method,
                body: formData,
                headers: {
                    "X-Requested-With": "XMLHttpRequest",
                },
            })
                .then((response) => response.json())
                .then((data) => {
                    if (data.success) {
                        showToast("Success", data.message, "success");
                        const newValues = getCurrentFormValues();
                        updateOriginalValues(newValues);
                        updateFormatPreview(newValues);
                    } else {
                        showToast(
                            "Error",
                            data.message ||
                                "Failed to update currency settings.",
                            "error"
                        );
                        console.error(
                            "Error updating currency settings:",
                            data.errors
                        );
                        restoreOriginalValues();
                    }
                })
                .catch((error) => {
                    console.error("Error updating currency settings:", error);
                    showToast(
                        "Error",
                        "An error occurred while updating currency settings. Please check the console for details.",
                        "error"
                    );
                    restoreOriginalValues();
                });
        });
    }
}
