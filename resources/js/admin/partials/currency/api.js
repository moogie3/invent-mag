import { getCurrentFormValues, restoreOriginalValues, updateHiddenFields } from './form.js';
import { updateOriginalValues } from './state.js';
import { updateFormatPreview } from './preview.js';

export function handleFormSubmission() {
    const confirmSubmitButton = document.getElementById("confirmSubmit");
    const currencySettingsForm = document.getElementById("currencySettingsForm");
    const selectedCurrency = document.getElementById("selectedCurrency");
    
    if (confirmSubmitButton && currencySettingsForm) {
        if (confirmSubmitButton.dataset.listenerAttached) return;
        confirmSubmitButton.dataset.listenerAttached = 'true';
        
        confirmSubmitButton.addEventListener("click", function () {
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

            const formData = new FormData(currencySettingsForm);

            const selectedOption =
                selectedCurrency.options[selectedCurrency.selectedIndex];
            formData.set("currency_code", selectedOption.value);
            formData.set("locale", selectedOption.dataset.locale);
            formData.set("currency_symbol", selectedOption.dataset.symbol);

            fetch(currencySettingsForm.action, {
                method: currencySettingsForm.method,
                body: formData,
                headers: {
                    "X-Requested-With": "XMLHttpRequest",
                },
            })
                .then((response) => response.json())
                .then((data) => {
                    if (data.success) {
                        InventMagApp.showToast("Success", data.message, "success");
                        const newValues = getCurrentFormValues();
                        updateOriginalValues(newValues);
                        updateFormatPreview(newValues);
                    } else {
                        InventMagApp.showToast(
                            "Error",
                            data.message ||
                                "Failed to update currency settings.",
                            "error"
                        );
                        restoreOriginalValues();
                    }
                })
                .catch((error) => {
                    InventMagApp.showToast(
                        "Error",
                        "An error occurred while updating currency settings.",
                        "error"
                    );
                    restoreOriginalValues();
                });
        });
    }
}