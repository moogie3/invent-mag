export function validateForm(formElement) {
    if (!formElement) return true;

    const requiredFields = formElement.querySelectorAll('[required]');
    let isValid = true;
    const invalidFields = [];

    requiredFields.forEach(field => {
        try {
            if (field.disabled) return;

            if (field.type === 'hidden' && !field._flatpickr) return;

            let value = '';
            let fieldElement = field;

            if (field._flatpickr) {
                if (field._flatpickr.altInput) {
                    value = field._flatpickr.altInput.value.trim();
                    fieldElement = field._flatpickr.altInput;
                } else {
                    value = (field.value || '').trim();
                }
            } else if (field.tagName === 'SELECT') {
                value = field.value;
            } else {
                value = (field.value || '').trim();
            }

            if (!value) {
                fieldElement.classList.add('is-invalid');
                invalidFields.push(fieldElement);
                isValid = false;
            } else {
                fieldElement.classList.remove('is-invalid');
            }
        } catch (e) {
            isValid = false;
        }
    });

    if (!isValid) {
        if (window.InventMagApp && typeof window.InventMagApp.showToast === 'function') {
            window.InventMagApp.showToast('Warning', 'Please fill out all required fields correctly.', 'warning');
        }
        if (invalidFields.length > 0) {
            invalidFields[0].focus();
            invalidFields[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    }

    return isValid;
}

export function initFormValidation() {
    document.addEventListener('submit', function (e) {
        const form = e.target;
        if (!form.hasAttribute('data-validate-form')) return;
        if (e.defaultPrevented) return;

        if (!validateForm(form)) {
            e.preventDefault();
        }
    }, true);
}
