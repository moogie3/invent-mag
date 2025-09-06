export function initModals() {
    const modals = [
        { btnId: "viewLowStock", modalId: "lowStockModal" },
        { btnId: "viewExpiringSoon", modalId: "expiringSoonModal" },
    ];

    modals.forEach(({ btnId, modalId }) => {
        const btn = document.getElementById(btnId);
        if (btn) {
            const modal = new bootstrap.Modal(document.getElementById(modalId));
            btn.addEventListener("click", (e) => {
                e.preventDefault();
                modal.show();
            });
        }
    });
}
