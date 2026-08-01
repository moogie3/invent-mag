export function handleFormSubmission(event, modalElement, isCreate = false) {
    if (event.defaultPrevented) return;
    event.preventDefault();

    const form = event.target;
    
    if (form.dataset.submitting === 'true') return;
    form.dataset.submitting = 'true';
    
    InventMagApp.toastShown = false;
    
    const originalButtonText = form.querySelector('button[type="submit"]')?.textContent;
    if (form.querySelector('button[type="submit"]')) {
        form.querySelector('button[type="submit"]').disabled = true;
    }

    const formData = new FormData(form);
    const actionUrl = form.action;
    const method = form.method;

    fetch(actionUrl, {
        method: method === 'GET' ? 'GET' : 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
        },
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            if (!InventMagApp.toastShown) {
                InventMagApp.toastShown = true;
                InventMagApp.showToast('Success', data.message, 'success');
            }

            const bsModal = bootstrap.Modal.getInstance(modalElement);
            if (bsModal) {
                bsModal._element.addEventListener('hidden.bs.modal', function handler() {
                    bsModal._element.removeEventListener('hidden.bs.modal', handler);
                    form.reset();
                    const backdrops = document.querySelectorAll('.modal-backdrop');
                    backdrops.forEach(backdrop => backdrop.remove());
                    location.reload();
                }, { once: true });
                bsModal.hide();
            } else {
                form.reset();
                location.reload();
            }
        } else {
            if (!InventMagApp.toastShown) {
                InventMagApp.toastShown = true;
                InventMagApp.showToast('Warning', data.message || 'Operation failed.', 'warning');
            }
        }
    })
    .catch(error => {
        console.error('Error during fetch:', error);
        if (!InventMagApp.toastShown) {
            InventMagApp.toastShown = true;
            InventMagApp.showToast('Error', 'An error occurred.', 'error');
        }
    })
    .finally(() => {
        form.dataset.submitting = 'false';
        if (form.querySelector('button[type="submit"]')) {
            form.querySelector('button[type="submit"]').disabled = false;
            form.querySelector('button[type="submit"]').textContent = originalButtonText;
        }
        InventMagApp.toastShown = false;
    });
}