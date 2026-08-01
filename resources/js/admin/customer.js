import { initEditCustomerModal } from "./partials/customer/editModal/main.js";
import { initCrmCustomerModal } from "./partials/customer/crmModal/main.js";
import { initSelectableTable } from "./layouts/selectable-table.js";
import { onPageLoad } from "./utils/onPageLoad.js";

onPageLoad(function () {
    if (!window.location.pathname.includes("/admin/customer")) return;

    initEditCustomerModal();
    initCrmCustomerModal();
    initSelectableTable();
    initDeleteModal();
    initCustomerForms();

    if (window.shortcutManager) {
        window.shortcutManager.register("alt+n", () => {
            const createModalEl = document.getElementById("createCustomerModal");
            if (createModalEl) {
                const createModal = new bootstrap.Modal(createModalEl);
                createModal.show();
            }
        }, "New Customer");

        window.shortcutManager.register("ctrl+s", () => {
            const createModal = document.getElementById("createCustomerModal");
            const editModal = document.getElementById("editCustomerModal");

            if (createModal && createModal.classList.contains("show")) {
                const form = createModal.querySelector("form");
                if (form) form.requestSubmit();
            } else if (editModal && editModal.classList.contains("show")) {
                const form = editModal.querySelector("form");
                if (form) form.requestSubmit();
            }
        }, "Save Customer");
    }
});

function initDeleteModal() {
    const deleteModal = document.getElementById("deleteModal");
    
    if (!deleteModal) return;
    if (deleteModal.dataset.initialized) return;
    deleteModal.dataset.initialized = 'true';
    
    deleteModal.addEventListener("show.bs.modal", function(event) {
        const button = event.relatedTarget;
        if (!button) return;
        
        const customerId = button.dataset.id;
        const deleteForm = document.getElementById("deleteForm");
        if (deleteForm && customerId) {
            deleteForm.action = `/admin/customer/destroy/${customerId}`;
        }
    });
    
    const deleteForm = document.getElementById("deleteForm");
    if (deleteForm && !deleteForm.dataset.handlerAttached) {
        deleteForm.dataset.handlerAttached = 'true';
        deleteForm.addEventListener("submit", function(e) {
            e.preventDefault();
            handleDeleteSubmit(this);
        });
    }
}

function handleDeleteSubmit(form) {
    if (form.dataset.submitting === 'true') return;
    form.dataset.submitting = 'true';
    
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn?.textContent;
    if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Deleting...';
    }

    fetch(form.action, {
        method: 'POST',
        body: new FormData(form),
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
        },
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            InventMagApp.showToast('Success', data.message || 'Deleted successfully', 'success');
            const deleteModal = bootstrap.Modal.getInstance(document.getElementById("deleteModal"));
            if (deleteModal) {
                deleteModal._element.addEventListener('hidden.bs.modal', function handler() {
                    deleteModal._element.removeEventListener('hidden.bs.modal', handler);
                    document.querySelectorAll('.modal-backdrop').forEach(b => b.remove());
                    document.body.classList.remove('modal-open');
                    document.body.style.overflow = '';
                    location.reload();
                }, { once: true });
                deleteModal.hide();
            } else {
                location.reload();
            }
        } else {
            InventMagApp.showToast('Error', data.message || 'Failed to delete', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        InventMagApp.showToast('Error', 'An error occurred', 'error');
    })
    .finally(() => {
        form.dataset.submitting = 'false';
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
        }
    });
}

function initCustomerForms() {
    const createForm = document.getElementById("createCustomerForm");
    const editForm = document.getElementById("editCustomerForm");
    
    if (createForm && !createForm.dataset.handlerAttached) {
        createForm.dataset.handlerAttached = 'true';
        createForm.addEventListener("submit", function(e) {
            if (e.defaultPrevented) return;
            e.preventDefault();
            handleFormSubmit(this, document.getElementById("createCustomerModal"));
        });
    }
    
    if (editForm && !editForm.dataset.handlerAttached) {
        editForm.dataset.handlerAttached = 'true';
        editForm.addEventListener("submit", function(e) {
            if (e.defaultPrevented) return;
            e.preventDefault();
            handleFormSubmit(this, document.getElementById("editCustomerModal"));
        });
    }
}

function handleFormSubmit(form, modal) {
    if (form.dataset.submitting === 'true') return;
    form.dataset.submitting = 'true';
    
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn?.textContent;
    if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Saving...';
    }

    const formData = new FormData(form);

    fetch(form.action, {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
        },
    })
    .then(response => {
        return response.json().then(data => ({ ok: response.ok, status: response.status, data }));
    })
    .then(({ ok, status, data }) => {
        if (ok && data.success) {
            InventMagApp.showToast('Success', data.message || 'Operation successful', 'success');
            const bsModal = bootstrap.Modal.getInstance(modal);
            if (bsModal) {
                bsModal._element.addEventListener('hidden.bs.modal', function handler() {
                    bsModal._element.removeEventListener('hidden.bs.modal', handler);
                    form.reset();
                    document.querySelectorAll('.modal-backdrop').forEach(b => b.remove());
                    document.body.classList.remove('modal-open');
                    document.body.style.overflow = '';
                    location.reload();
                }, { once: true });
                bsModal.hide();
            } else {
                form.reset();
                location.reload();
            }
        } else {
            let errorMsg = '';
            
            if (data.errors) {
                Object.keys(data.errors).forEach(key => {
                    errorMsg += data.errors[key].join(', ') + '\n';
                });
            } else if (data.message) {
                errorMsg = data.message;
            } else {
                errorMsg = 'Operation failed';
            }
            
            InventMagApp.showToast('Warning', errorMsg, 'warning');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        InventMagApp.showToast('Error', 'An error occurred', 'error');
    })
    .finally(() => {
        form.dataset.submitting = 'false';
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
        }
    });
}

export function exportCustomers(exportOption = 'csv') {
    const form = document.createElement("form");
    form.method = "POST";
    form.action = "/admin/customer/export";
    form.style.display = "none";

    const csrf = document.querySelector('meta[name="csrf-token"]');
    if (csrf) {
        const token = document.createElement("input");
        token.type = "hidden";
        token.name = "_token";
        token.value = csrf.getAttribute("content");
        form.appendChild(token);
    }

    const exportOptionInput = document.createElement("input");
    exportOptionInput.type = "hidden";
    exportOptionInput.name = "export_option";
    exportOptionInput.value = exportOption;
    form.appendChild(exportOptionInput);

    document.body.appendChild(form);
    form.submit();
    setTimeout(() => document.body.removeChild(form), 2000);
}

window.exportCustomers = exportCustomers;
