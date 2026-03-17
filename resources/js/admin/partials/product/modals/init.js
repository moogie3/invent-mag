export function initModals() {
    const modals = [
        { btnId: "viewLowStock", modalId: "lowStockModal" },
        { btnId: "viewExpiringSoon", modalId: "expiringSoonModal" },
    ];

    modals.forEach(({ btnId, modalId }) => {
        const btn = document.getElementById(btnId);
        const modalEl = document.getElementById(modalId);
        if (btn && modalEl) {
            const modal = new bootstrap.Modal(modalEl, {});
            btn.addEventListener("click", (e) => {
                e.preventDefault();
                modal.show();
            });
        }
    });
}
