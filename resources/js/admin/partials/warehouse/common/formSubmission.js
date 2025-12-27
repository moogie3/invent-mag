export function handleFormSubmission(event, modalElement, isCreate = false) {
    event.preventDefault();

    const form = event.target;
    const formData = new FormData(form);
    const actionUrl = form.action;
    const method = form.method;

    fetch(actionUrl, {
        method: method === 'GET' ? 'GET' : 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
        },
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            InventMagApp.showToast('Success', data.message, 'success');
            const bsModal = bootstrap.Modal.getInstance(modalElement);
            if (bsModal) {
                bsModal._element.addEventListener('hidden.bs.modal', function handler() {
                    bsModal._element.removeEventListener('hidden.bs.modal', handler);
                    form.reset();
                    const backdrops = document.querySelectorAll('.modal-backdrop');
                    backdrops.forEach(backdrop => backdrop.remove());
                    location.reload();
                });
                bsModal.hide();
            } else {
                form.reset();
                location.reload();
            }
        } else {
            InventMagApp.showToast('Error', data.message || 'Operation failed.', 'error');
            console.error('Form submission error:', data.errors);
        }
    })
    .catch(error => {
        console.error('Error during fetch:', error);
        InventMagApp.showToast('Error', 'An error occurred. Please check the console.', 'error');
    });
}