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
    initViewWarehouseButtons();

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

function initViewWarehouseButtons() {
    document.querySelectorAll('.view-warehouse-btn').forEach(btn => {
        if (btn.dataset.listenerAttached) return;
        btn.dataset.listenerAttached = 'true';
        
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const url = this.dataset.url;
            const modal = document.getElementById('viewWarehouseModal');
            const modalInstance = bootstrap.Modal.getOrCreateInstance(modal);
            
            // Reset modal content
            document.getElementById('viewWarehouseProductsList').innerHTML = '<tr><td colspan="5" class="text-center text-muted py-4 fs-4"><i class="ti ti-loader me-2 fs-4"></i>Loading products...</td></tr>';
            
            modalInstance.show();
            
            fetch(url, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                },
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const warehouse = data.warehouse;
                    
                    // Set warehouse details
                    const nameEl = document.getElementById('viewWarehouseName');
                    const addressEl = document.getElementById('viewWarehouseAddress');
                    const descEl = document.getElementById('viewWarehouseDescription');
                    const totalProductsEl = document.getElementById('viewWarehouseTotalProducts');
                    const totalStockEl = document.getElementById('viewWarehouseTotalStock');
                    
                    if (nameEl) nameEl.textContent = warehouse.name || '';
                    if (addressEl) addressEl.textContent = warehouse.address || '-';
                    if (descEl) descEl.textContent = warehouse.description || '-';
                    if (totalProductsEl) totalProductsEl.textContent = warehouse.total_products || 0;
                    if (totalStockEl) totalStockEl.textContent = warehouse.total_stock || 0;
                    
                    // Populate products table - now showing each PO item as separate row (per batch)
                    const productsList = document.getElementById('viewWarehouseProductsList');
                    if (productsList) {
                        // Check if using old products array or new po_items array
                        const items = warehouse.po_items || warehouse.products || [];
                        
                        if (items && items.length > 0) {
                            productsList.innerHTML = items.map(item => {
                                let expiryBadge = '<span class="text-muted">-</span>';
                                if (item.expiry_date && item.expiry_date !== 'null' && item.expiry_date !== null && item.expiry_date !== '') {
                                    const dateParts = item.expiry_date.split('-');
                                    if (dateParts.length === 3) {
                                        const expiryDate = new Date(dateParts[0], dateParts[1] - 1, dateParts[2]);
                                        if (!isNaN(expiryDate.getTime())) {
                                            const formattedDate = expiryDate.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
                                            const today = new Date();
                                            today.setHours(0, 0, 0, 0);
                                            const diffTime = expiryDate - today;
                                            const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
                                            
                                            if (diffDays < 0) {
                                                expiryBadge = '<span class="badge bg-danger text-white">Expired</span>';
                                            } else if (diffDays <= 7) {
                                                expiryBadge = '<span class="badge bg-danger text-white">' + formattedDate + '</span>';
                                            } else if (diffDays <= 30) {
                                                expiryBadge = '<span class="badge bg-warning text-dark">' + formattedDate + '</span>';
                                            } else {
                                                expiryBadge = '<span class="badge bg-success text-white">' + formattedDate + '</span>';
                                            }
                                        }
                                    }
                                }
                                
                                return `
                                    <tr>
                                        <td class="fw-medium fs-4">${item.name}</td>
                                        <td class="fs-4">${item.code || '-'}</td>
                                        <td class="fs-4"><a href="/admin/po/edit/${item.po_id}" class="text-decoration-none">#${item.po_id}</a></td>
                                        <td class="text-center">
                                            <span class="badge ${item.quantity > 10 ? 'bg-success' : (item.quantity > 0 ? 'bg-warning' : 'bg-danger')} text-white fs-5">
                                                ${item.quantity}
                                            </span>
                                        </td>
                                        <td class="text-center fs-4">${expiryBadge}</td>
                                    </tr>
                                `;
                            }).join('');
                        } else {
                            productsList.innerHTML = '<tr><td colspan="5" class="text-center text-muted py-4 fs-4">No products in this warehouse</td></tr>';
                        }
                    }
                } else {
                    InventMagApp.showToast('Error', data.message || 'Failed to load warehouse details.', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                InventMagApp.showToast('Error', 'An error occurred while loading warehouse details.', 'error');
            });
        });
    });
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
