import { getDeleteUserModal, deleteUserModal } from '../common/elements.js';
import { setCurrentUserId } from '../common/state.js';

export function initDeleteUserModal() {
    const deleteUserModalEl = document.getElementById("deleteUserModal");
    if (!deleteUserModalEl) return;
    
    document.querySelectorAll(".delete-user-btn").forEach((button) => {
        button.addEventListener("click", function () {
            setCurrentUserId(this.getAttribute("data-user-id"));

            const userRow = this.closest("tr");
            const userName = userRow
                ? userRow.children[0].textContent
                : "this user";
            const modal = getDeleteUserModal();
            if (modal && modal._element) {
                const modalBody = modal._element.querySelector(".modal-body p");
                if (modalBody) {
                    modalBody.textContent = `Are you sure you want to delete ${userName}?`;
                }
            }
        });
    });
}
