import { initEditWarehouseModal } from './partials/warehouse/edit/main.js';
import { initCreateWarehouseForm } from './partials/warehouse/create/main.js';
import { initSelectableTable } from "./layouts/selectable-table.js";
import { onPageLoad } from './utils/onPageLoad.js';

onPageLoad(function () {
    if (!window.location.pathname.includes("/admin/warehouses")) return;
    
    initEditWarehouseModal();
    initCreateWarehouseForm();
    initSelectableTable();
    initDeleteModal();
    initWarehouseSearch();
    initSetMainLinks();
    initUnsetMainLinks();

    if (window.shortcutManager) {
        window.shortcutManager.register('ctrl+s', () => {
            const createModal = document.getElementById('createWarehouseModal');
            const editModal = document.getElementById('editWarehouseModal');

            if (createModal && createModal.classList.contains('show')) {
                const form = createModal.querySelector('form');
                if (form) form.requestSubmit();
            } else if (editModal && editModal.classList.contains('show')) {
                const form = editModal.querySelector('form');
                if (form) form.requestSubmit();
            }
        }, 'Save Warehouse');

        window.shortcutManager.register('alt+n', () => {
            const createModalEl = document.getElementById('createWarehouseModal');
            if (createModalEl) {
                const createModal = new bootstrap.Modal(createModalEl);
                createModal.show();
            }
        }, 'New Warehouse');
    }
});

function initSetMainLinks() {
    document.querySelectorAll('.set-main-btn').forEach(btn => {
        if (btn.dataset.listenerAttached) return;
        btn.dataset.listenerAttached = 'true';
        
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            if (this.disabled || this.dataset.processing) return;
            this.dataset.processing = 'true';
            this.disabled = true;
            
            const url = this.dataset.url;
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            
            fetch(url, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken || '',
                },
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    InventMagApp.showToast('Success', data.message, 'success');
                    setTimeout(() => location.reload(), 500);
                } else {
                    InventMagApp.showToast('Error', data.message || 'Failed to set main warehouse.', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                InventMagApp.showToast('Error', 'An error occurred.', 'error');
            })
            .finally(() => {
                this.dataset.processing = 'false';
                this.disabled = false;
            });
        });
    });
}

function initUnsetMainLinks() {
    document.querySelectorAll('.unset-main-btn').forEach(btn => {
        if (btn.dataset.listenerAttached) return;
        btn.dataset.listenerAttached = 'true';
        
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            if (this.disabled || this.dataset.processing) return;
            this.dataset.processing = 'true';
            this.disabled = true;
            
            const url = this.dataset.url;
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            
            fetch(url, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken || '',
                },
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    InventMagApp.showToast('Success', data.message, 'success');
                    setTimeout(() => location.reload(), 500);
                } else {
                    InventMagApp.showToast('Error', data.message || 'Failed to unset main warehouse.', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                InventMagApp.showToast('Error', 'An error occurred.', 'error');
            })
            .finally(() => {
                this.dataset.processing = 'false';
                this.disabled = false;
            });
        });
    });
}

function initDeleteModal() {
    const deleteModal = document.getElementById('deleteModal');
    if (deleteModal) {
        if (deleteModal.dataset.handlerAttached) return;
        deleteModal.dataset.handlerAttached = 'true';
        deleteModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const targetRow = button?.closest('tr');
            const isMainCell = targetRow?.querySelector('.sort-is-main');
            const warning = document.getElementById('mainWarehouseWarning');
            const deleteBtn = document.querySelector('#deleteForm button[type="submit"]');
            
            if (isMainCell && warning && deleteBtn) {
                if (isMainCell.querySelector('.badge.bg-green-lt')) {
                    warning.style.display = 'block';
                    deleteBtn.disabled = true;
                } else {
                    warning.style.display = 'none';
                    deleteBtn.disabled = false;
                }
            }
        });
    }
}

function initWarehouseSearch() {
    const searchInput = document.getElementById('searchInput');
    const entriesSelect = document.getElementById('entriesSelect');
    
    if (searchInput) {
        searchInput.addEventListener('keyup', function() {
            const value = this.value.toLowerCase();
            const tableBody = document.querySelector('table tbody');
            if (tableBody) {
                tableBody.querySelectorAll('tr').forEach(row => {
                    row.style.display = row.textContent.toLowerCase().includes(value) ? '' : 'none';
                });
            }
        });
    }
    
    if (entriesSelect) {
        entriesSelect.addEventListener('change', function() {
            window.location.href = '?entries=' + this.value;
        });
    }
}

function setDeleteFormAction(action) {
    const deleteForm = document.getElementById('deleteForm');
    if (deleteForm) deleteForm.action = action;
}

window.setDeleteFormAction = setDeleteFormAction;

export function exportWarehouses(exportOption = 'csv') {
    const form = document.createElement("form");
    form.method = "POST";
    form.action = "/admin/warehouses/export";
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

window.exportWarehouses = exportWarehouses;
