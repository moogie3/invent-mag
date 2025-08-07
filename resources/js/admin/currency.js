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
        const confirmModal = bootstrap.Modal.getInstance(document.getElementById("confirmModal"));
        if (confirmModal) {
            confirmModal.hide();
            // Listen for the 'hidden.bs.modal' event to ensure the modal is fully closed
            confirmModal._element.addEventListener('hidden.bs.modal', function handler() {
                confirmModal._element.removeEventListener('hidden.bs.modal', handler); // Remove the listener
                // Explicitly remove any remaining modal backdrops
                const backdrops = document.querySelectorAll('.modal-backdrop');
                backdrops.forEach(backdrop => backdrop.remove());
            });
        }

        const formData = new FormData(currencySettingsForm);

        // Manually set hidden fields based on selected option
        const selectedOption = document.getElementById('selectedCurrency').options[document.getElementById('selectedCurrency').selectedIndex];
        formData.set('currency_code', selectedOption.value);
        formData.set('locale', selectedOption.dataset.locale);
        formData.set('currency_symbol', selectedOption.dataset.symbol);

        fetch(currencySettingsForm.action, {
            method: currencySettingsForm.method,
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
            },
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast('Success', data.message, 'success');
            } else {
                showToast('Error', data.message || 'Failed to update currency settings.', 'error');
                console.error('Error updating currency settings:', data.errors);
            }
        })
        .catch(error => {
            console.error('Error updating currency settings:', error);
            showToast('Error', 'An error occurred while updating currency settings. Please check the console for details.', 'error');
        });
    });
});


