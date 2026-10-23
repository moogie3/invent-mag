import { initProductPage } from "./partials/product/main.js";
import { initShopeeImport } from "./partials/product/shopeeImport.js";
import { validateForm } from "./utils/formValidator.js";

import {
    initProductModal,
    loadExpiringSoonProductsModal,
} from "./partials/product/modals/product.js"; // Import loadExpiringSoonProductsModal
import {
    initBulkSelection,
    clearProductSelection,
} from "./partials/product/bulkActions/selection.js";
import { bulkExportProducts } from "./partials/product/bulkActions/export.js";
import { bulkUpdateStock } from "./partials/product/bulkActions/stock.js";
import { initSelectableTable } from "./layouts/selectable-table.js";
import { onPageLoad } from "./utils/onPageLoad.js";

onPageLoad(function () {
    if (!window.location.pathname.includes("/admin/product")) return;

    initProductPage();
    initDeleteModal();
    initProductForms();
    initShopeeImport();

    setTimeout(() => {
        window.shortcutManager.unregister("ctrl+s");
        const pathname = window.location.pathname;

        if (
            !pathname.includes("/admin/product/create") &&
            !pathname.includes("/admin/product/edit")
        ) {
            initSelectableTable();
        }

        if (!pathname.includes("/admin/product/edit") && !pathname.includes("/admin/product/create")) {
            window.shortcutManager.register(
                "alt+n",
                () => {
                    window.location.href = "/admin/product/create";
                },
                "New Product"
            );
        }

        if (pathname.includes("/admin/product/create")) {
            window.shortcutManager.register(
                "ctrl+s",
                () => {
                    document.getElementById("product-create-form").requestSubmit();
                },
                "Save Product"
            );
        } else if (pathname.includes("/admin/product/edit")) {
            window.shortcutManager.register(
                "ctrl+s",
                () => {
                    document.getElementById("product-edit-form").requestSubmit();
                },
                "Save Product"
            );
        }
    }, 250);
});

function initProductForms() {
    const createForm = document.getElementById("product-create-form");
    if (createForm && !createForm.dataset.handlerAttached) {
        createForm.dataset.handlerAttached = 'true';
        createForm.addEventListener("submit", function(e) {
            if (e.defaultPrevented) return;
            e.preventDefault();
            handleProductFormSubmit(this, 'create');
        });
    }
    
    const editForm = document.getElementById("product-edit-form");
    if (editForm && !editForm.dataset.handlerAttached) {
        editForm.dataset.handlerAttached = 'true';
        editForm.addEventListener("submit", function(e) {
            if (e.defaultPrevented) return;
            e.preventDefault();
            handleProductFormSubmit(this, 'edit');
        });
    }
}

function handleProductFormSubmit(form, formType) {
    if (form.dataset.submitting === 'true') return;
    form.dataset.submitting = 'true';
    
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn?.textContent;
    if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Saving...';
    }

    const formData = new FormData(form);
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    fetch(form.action, {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
            'X-Laravel-JSON': 'true',
        },
    })
    .then(response => {
        if (response.status === 419) {
            InventMagApp.showToast('Error', 'Session expired. Please refresh the page and try again.', 'error');
            setTimeout(() => window.location.reload(), 2000);
            return null;
        }
        
        const contentType = response.headers.get('content-type');
        if (contentType && contentType.includes('application/json')) {
            return response.json().then(data => ({ ok: response.ok, status: response.status, data }));
        }
        if (response.redirected) {
            window.location.href = response.url;
            return null;
        }
        return response.text().then(text => ({ 
            ok: response.ok, 
            status: response.status, 
            data: { 
                success: false,
                message: 'An error occurred. Please try again.' 
            } 
        }));
    })
    .then(result => {
        if (!result) return;
        
        const { ok, status, data } = result;
        
        if (ok && data.success) {
            InventMagApp.showToast('Success', data.message || 'Product saved successfully', 'success');
            if (data.redirect) {
                setTimeout(() => {
                    window.location.href = data.redirect;
                }, 1500);
            } else {
                setTimeout(() => {
                    window.location.href = '/admin/product';
                }, 1500);
            }
        } else {
            let errorMsg = '';
            
            if (data.errors) {
                const firstError = Object.values(data.errors)[0];
                errorMsg = Array.isArray(firstError) ? firstError[0] : firstError;
            } else if (data.message) {
                errorMsg = data.message;
            } else {
                errorMsg = 'Validation failed. Please check your inputs.';
            }
            
            InventMagApp.showToast('Warning', errorMsg, 'warning');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        InventMagApp.showToast('Error', 'An error occurred. Please try again.', 'error');
    })
    .finally(() => {
        form.dataset.submitting = 'false';
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        }
    });
}

function initDeleteModal() {
    const deleteModal = document.getElementById("deleteModal");
    
    if (!deleteModal) return;
    if (deleteModal.dataset.initialized) return;
    deleteModal.dataset.initialized = 'true';
    
    deleteModal.addEventListener("show.bs.modal", function(event) {
        const button = event.relatedTarget;
        if (!button) return;
        
        const productId = button.dataset.id;
        const deleteForm = document.getElementById("deleteForm");
        if (deleteForm && productId) {
            deleteForm.action = `/admin/product/destroy/${productId}`;
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
            'Accept': 'application/json',
            'X-Laravel-JSON': 'true',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
        },
    })
    .then(response => {
        if (response.status === 419) {
            InventMagApp.showToast('Error', 'Session expired. Please refresh the page and try again.', 'error');
            setTimeout(() => window.location.reload(), 2000);
            return null;
        }
        
        const contentType = response.headers.get('content-type');
        if (contentType && contentType.includes('application/json')) {
            return response.json().then(data => ({ ok: response.ok, status: response.status, data }));
        }
        if (response.redirected) {
            window.location.href = response.url;
            return null;
        }
        return { ok: response.ok, status: response.status, data: { success: true } };
    })
    .then(result => {
        if (!result) return;
        
        const { data } = result;
        
        if (data.success) {
            InventMagApp.showToast('Success', data.message || 'Deleted successfully', 'success');
            const deleteModal = bootstrap.Modal.getInstance(document.getElementById("deleteModal"));
            if (deleteModal) {
                deleteModal._element.addEventListener('hidden.bs.modal', function handler() {
                    deleteModal._element.removeEventListener('hidden.bs.modal', handler);
                    document.querySelectorAll('.modal-backdrop').forEach(b => b.remove());
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
            submitBtn.innerHTML = originalText;
        }
    });
}

window.bulkExportProducts = bulkExportProducts;
window.bulkUpdateStock = bulkUpdateStock;
