let initialized = false;

document.addEventListener('turbo:before-cache', () => {
    initialized = false;
});

export function initModals() {
    if (initialized) return;
    initialized = true;
    
    const modals = [
        { btnId: "viewLowStock", modalId: "lowStockModal" },
        { btnId: "viewExpiringSoon", modalId: "expiringSoonModal" },
    ];

    modals.forEach(({ btnId, modalId }) => {
        const btn = document.getElementById(btnId);
        const modalEl = document.getElementById(modalId);
        if (btn && modalEl && !btn.dataset.handlerAttached) {
            btn.dataset.handlerAttached = 'true';
            const modal = new bootstrap.Modal(modalEl, {});
            btn.addEventListener("click", (e) => {
                e.preventDefault();
                modal.show();
            });
        }
    });
}
