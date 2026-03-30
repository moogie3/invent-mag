import { getCreateUserModal, getCreateUserForm, getCreateRolesContainer, getCreatePermissionsContainer } from '../common/elements.js';
import { setupRolePermissionSync, resetPermissionStates, syncPermissionsFromRoles } from '../rolesPermissions/sync.js';
import { fetchRolePermissions } from '../rolesPermissions/api.js';

export function setupCreateUserModal() {
    const createModalElement = document.getElementById("createUserModal");
    if (!createModalElement) return;
    
    const createRolesContainer = getCreateRolesContainer();
    const createPermissionsContainer = getCreatePermissionsContainer();
    const createUserForm = getCreateUserForm();
    
    if (createRolesContainer && createPermissionsContainer) {
        setupRolePermissionSync(
            createRolesContainer,
            createPermissionsContainer
        );

        createModalElement.addEventListener("shown.bs.modal", function () {
            syncPermissionsFromRoles(
                createRolesContainer,
                createPermissionsContainer
            );
        });

        createModalElement.addEventListener("hidden.bs.modal", function () {
            resetPermissionStates(createPermissionsContainer);
        });
    }
    
    if (createUserForm && !createUserForm.dataset.ajaxHandlerAttached) {
        createUserForm.dataset.ajaxHandlerAttached = 'true';
        
        createUserForm.addEventListener("submit", function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const submitBtn = this.querySelector('button[type="submit"]');
            if (!submitBtn) return;
            const originalText = submitBtn.textContent;
            
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Creating...';
            
            fetch(this.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
            })
            .then(response => response.json().then(data => ({ ok: response.ok, data })))
            .then(({ ok, data }) => {
                if (data.success || data.message) {
                    const modal = getCreateUserModal();
                    if (modal) modal.hide();
                    
                    InventMagApp.showToast('Success', data.message || 'User created successfully', 'success');
                    
                    setTimeout(() => {
                        window.location.reload();
                    }, 500);
                } else {
                    if (data.errors) {
                        let errorMsg = '';
                        Object.keys(data.errors).forEach(key => {
                            errorMsg += data.errors[key].join(', ') + '\n';
                        });
                        InventMagApp.showToast('Error', errorMsg, 'error');
                    } else {
                        InventMagApp.showToast('Error', data.message || 'Failed to create user', 'error');
                    }
                }
            })
            .catch(error => {
                console.error('Error creating user:', error);
                InventMagApp.showToast('Error', 'An error occurred', 'error');
            })
            .finally(() => {
                submitBtn.disabled = false;
                submitBtn.textContent = originalText;
            });
        });
    }
}
