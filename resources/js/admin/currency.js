document.addEventListener("DOMContentLoaded", function () {
    // Get all necessary elements
    const showModalButton = document.getElementById("showModalButton");
    const confirmSubmitButton = document.getElementById("confirmSubmit");
    const currencySettingsForm = document.getElementById(
        "currencySettingsForm"
    );
    const selectedCurrency = document.getElementById("selectedCurrency");
    const currencyCodeInput = document.getElementById("currencyCode");
    const localeInput = document.getElementById("locale");
    const currencySymbolInput = document.getElementById("currencySymbol");

    // Store original values to revert if needed
    let originalValues = {
        currencyCode: currencyCodeInput?.value || "",
        locale: localeInput?.value || "",
        currencySymbol: currencySymbolInput?.value || "",
        selectedCurrencyIndex: selectedCurrency?.selectedIndex || 0,
        decimalSeparator:
            document.querySelector('input[name="decimal_separator"]')?.value ||
            ".",
        thousandSeparator:
            document.querySelector('input[name="thousand_separator"]')?.value ||
            ",",
        decimalPlaces:
            document.querySelector('input[name="decimal_places"]')?.value ||
            "2",
        position:
            document.querySelector('select[name="position"]')?.value ||
            "prefix",
    };

    // Function to update the format preview with given values
    function updateFormatPreview(values) {
        const previewElement = document.querySelector(
            ".badge.bg-primary.text-white"
        );
        if (!previewElement) return;

        // Generate decimal places string (e.g., "00" for 2 decimal places)
        const decimalPlacesStr = "0".repeat(
            Math.max(0, Math.min(10, parseInt(values.decimalPlaces)))
        );

        // Create the preview format
        let preview = `1${values.thousandSeparator}234`;
        if (parseInt(values.decimalPlaces) > 0) {
            preview += `${values.decimalSeparator}${decimalPlacesStr}`;
        }

        // Add currency symbol based on position
        if (values.position === "prefix") {
            preview = `${values.currencySymbol}${preview}`;
        } else {
            preview = `${preview}${values.currencySymbol}`;
        }

        previewElement.textContent = preview;
    }

    // Function to get current form values
    function getCurrentFormValues() {
        const selectedOption =
            selectedCurrency?.options[selectedCurrency.selectedIndex];
        return {
            currencyCode: selectedOption?.value || "",
            locale: selectedOption?.dataset.locale || "",
            currencySymbol: selectedOption?.dataset.symbol || "$",
            selectedCurrencyIndex: selectedCurrency?.selectedIndex || 0,
            decimalSeparator:
                document.querySelector('input[name="decimal_separator"]')
                    ?.value || ".",
            thousandSeparator:
                document.querySelector('input[name="thousand_separator"]')
                    ?.value || ",",
            decimalPlaces:
                document.querySelector('input[name="decimal_places"]')?.value ||
                "2",
            position:
                document.querySelector('select[name="position"]')?.value ||
                "prefix",
        };
    }

    // Function to update hidden fields based on selected currency
    function updateHiddenFields() {
        if (
            selectedCurrency &&
            currencyCodeInput &&
            localeInput &&
            currencySymbolInput
        ) {
            const selectedOption =
                selectedCurrency.options[selectedCurrency.selectedIndex];
            currencyCodeInput.value = selectedOption.value;
            localeInput.value = selectedOption.dataset.locale;
            currencySymbolInput.value = selectedOption.dataset.symbol;
        }
    }

    // Function to restore form to original values
    function restoreOriginalValues() {
        if (selectedCurrency) {
            selectedCurrency.selectedIndex =
                originalValues.selectedCurrencyIndex;
        }
        if (currencyCodeInput)
            currencyCodeInput.value = originalValues.currencyCode;
        if (localeInput) localeInput.value = originalValues.locale;
        if (currencySymbolInput)
            currencySymbolInput.value = originalValues.currencySymbol;

        const decimalSeparatorField = document.querySelector(
            'input[name="decimal_separator"]'
        );
        const thousandSeparatorField = document.querySelector(
            'input[name="thousand_separator"]'
        );
        const decimalPlacesField = document.querySelector(
            'input[name="decimal_places"]'
        );
        const positionField = document.querySelector('select[name="position"]');

        if (decimalSeparatorField)
            decimalSeparatorField.value = originalValues.decimalSeparator;
        if (thousandSeparatorField)
            thousandSeparatorField.value = originalValues.thousandSeparator;
        if (decimalPlacesField)
            decimalPlacesField.value = originalValues.decimalPlaces;
        if (positionField) positionField.value = originalValues.position;

        // Update preview with original values
        updateFormatPreview(originalValues);
    }

    // Initialize with original values
    updateHiddenFields();
    updateFormatPreview(originalValues);

    // Update hidden fields when currency selection changes (but don't update preview)
    if (selectedCurrency) {
        selectedCurrency.addEventListener("change", function () {
            updateHiddenFields();
        });
    }

    // Show modal when save button is clicked
    if (showModalButton) {
        showModalButton.addEventListener("click", function () {
            const confirmModal = new bootstrap.Modal(
                document.getElementById("confirmModal")
            );
            confirmModal.show();
        });
    }

    // Handle form submission when confirm button is clicked
    if (confirmSubmitButton && currencySettingsForm) {
        confirmSubmitButton.addEventListener("click", function () {
            const confirmModal = bootstrap.Modal.getInstance(
                document.getElementById("confirmModal")
            );
            if (confirmModal) {
                confirmModal.hide();
                // Listen for the 'hidden.bs.modal' event to ensure the modal is fully closed
                confirmModal._element.addEventListener(
                    "hidden.bs.modal",
                    function handler() {
                        confirmModal._element.removeEventListener(
                            "hidden.bs.modal",
                            handler
                        );
                        // Explicitly remove any remaining modal backdrops
                        const backdrops =
                            document.querySelectorAll(".modal-backdrop");
                        backdrops.forEach((backdrop) => backdrop.remove());
                    }
                );
            }

            // Update hidden fields before submission
            updateHiddenFields();

            const formData = new FormData(currencySettingsForm);

            // Manually set hidden fields based on selected option (double-check)
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
                        showToast("Success", data.message, "success");

                        // Update the original values with current form values
                        originalValues = getCurrentFormValues();

                        // Update the preview with the new saved values
                        updateFormatPreview(originalValues);

                        // NO PAGE RELOAD - settings are updated successfully
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

                        // Restore form to original values on error
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

                    // Restore form to original values on error
                    restoreOriginalValues();
                });
        });
    }

    // Optional: Add cancel functionality to modal if needed
    const cancelButton = document.querySelector("#confirmModal .btn-secondary");
    if (cancelButton) {
        cancelButton.addEventListener("click", function () {
            // Restore form to original values when cancelled
            restoreOriginalValues();
        });
    }

    // Optional: Handle modal close button (X)
    const modalElement = document.getElementById("confirmModal");
    if (modalElement) {
        modalElement.addEventListener("hidden.bs.modal", function (event) {
            // Only restore if the modal was closed without confirming
            // This won't run if confirmSubmitButton was clicked because we handle it separately
            if (!event.target.classList.contains("confirmed")) {
                restoreOriginalValues();
            }
        });
    }
});
