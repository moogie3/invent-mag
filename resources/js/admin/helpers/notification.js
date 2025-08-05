/**
 * Displays a modern, minimal toast notification.
 * This function should be called globally via `window.showToast`.
 *
 * @param {string} title - The title of the toast.
 * @param {string} message - The main message content of the toast.
 * @param {string} [type='info'] - The type of toast ('success', 'error', 'warning', 'info'). Determines color and icon.
 * @param {number} [duration=4000] - How long the toast should be visible in milliseconds.
 */
window.showToast = function (title, message, type = "info", duration = 4000) {
    let container = document.getElementById("toast-container");
    if (!container) {
        container = document.createElement("div");
        container.id = "toast-container";
        container.className =
            "toast-container position-fixed bottom-0 end-0 p-3";
        container.style.zIndex = "1060";
        container.style.pointerEvents = "none";
        document.body.appendChild(container);
    }

    const toastElement = document.createElement("div");
    toastElement.className = `toast fade show`;
    toastElement.setAttribute("role", "alert");
    toastElement.setAttribute("aria-live", "assertive");
    toastElement.setAttribute("aria-atomic", "true");
    toastElement.setAttribute("data-bs-autohide", "false");
    toastElement.style.pointerEvents = "auto";

    const typeStyles = {
        success: {
            bg: "bg-white",
            border: "border-success",
            icon: "ti ti-check-circle",
            iconColor: "text-success",
            barColor: "#198754", // Bootstrap success color
        },
        error: {
            bg: "bg-white",
            border: "border-danger",
            icon: "ti ti-alert-circle",
            iconColor: "text-danger",
            barColor: "#dc3545", // Bootstrap danger color
        },
        warning: {
            bg: "bg-white",
            border: "border-warning",
            icon: "ti ti-alert-triangle",
            iconColor: "text-warning",
            barColor: "#ffc107", // Bootstrap warning color
        },
        info: {
            bg: "bg-white",
            border: "border-info",
            icon: "ti ti-info-circle",
            iconColor: "text-info",
            barColor: "#0dcaf0", // Bootstrap info color
        },
    };

    const currentType = typeStyles[type] || typeStyles.info;

    toastElement.innerHTML = `
        <div class="toast-content ${currentType.bg} rounded-3 shadow-sm p-3 position-relative overflow-hidden"
             style="min-width: 320px; border-radius: 0.75rem !important;">
            <div class="toast-color-bar" style="background-color: ${currentType.barColor};"></div>
            <div class="d-flex align-items-start ps-3">
                <div class="flex-shrink-0 me-3">
                    <i class="${currentType.icon} ${currentType.iconColor} fs-4"></i>
                </div>
                <div class="flex-grow-1">
                    <div class="fw-semibold text-dark mb-1" style="font-size: 0.95rem;">${title}</div>
                    <div class="text-muted" style="font-size: 0.875rem; line-height: 1.4;">${message}</div>
                </div>
                <button type="button" class="btn-close ms-3 mt-1" data-bs-dismiss="toast" aria-label="Close"
                        style="font-size: 0.75rem; opacity: 0.6;"
                        onmouseover="this.style.opacity='1'"
                        onmouseout="this.style.opacity='0.6'"></button>
            </div>
        </div>
    `;

    container.appendChild(toastElement);

    const toast = new bootstrap.Toast(toastElement, {
        delay: duration,
        autohide: true,
    });

    toastElement.addEventListener("hidden.bs.toast", () => {
        toastElement.remove();
    });

    toast.show();
};

// Global function for confirmation modal
window.showConfirmModal = function (title, message, onConfirm) {
    const confirmModalHtml = `
        <div class="modal modal-blur fade" id="confirmModal" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-body">
                        <div class="modal-title">${title}</div>
                        <div>${message}</div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-link link-secondary me-auto" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-danger" id="confirmModalBtn">Confirm</button>
                    </div>
                </div>
            </div>
        </div>
    `;

    // Remove any existing confirm modal to prevent duplicates
    const existingModal = document.getElementById("confirmModal");
    if (existingModal) {
        existingModal.remove();
    }

    document.body.insertAdjacentHTML("beforeend", confirmModalHtml);

    const confirmModalElement = document.getElementById("confirmModal");
    const confirmModal = new bootstrap.Modal(confirmModalElement);

    confirmModalElement.addEventListener("shown.bs.modal", () => {
        document.getElementById("confirmModalBtn").focus();
    });

    document.getElementById("confirmModalBtn").onclick = () => {
        onConfirm();
        confirmModal.hide();
    };

    confirmModalElement.addEventListener("hidden.bs.modal", () => {
        confirmModalElement.remove();
    });

    confirmModal.show();
};

// Global function for authentication page modals (success/error)
window.showAuthModal = function (title, message, type) {
    const iconClass =
        type === "success"
            ? "ti ti-check text-success"
            : "ti ti-alert-circle text-danger";
    const modalStatusClass = type === "success" ? "bg-success" : "bg-danger";

    const authModalHtml = `
        <div class="modal modal-blur fade" id="statusModal" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
                <div class="modal-content rounded-3 shadow-lg border-0">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    <div class="modal-status ${modalStatusClass}"></div>
                    <div class="modal-body text-center py-5 px-4">
                        <i class="${iconClass} d-block mx-auto mb-3" style="font-size: 3rem;"></i>
                        <h3 class="fw-bold mb-3" style="font-size: 1.15rem;">${title}</h3>
                        <div class="text-muted" style="font-size: 1.1rem; line-height: 1.5;">${message}</div>
                    </div>
                </div>
            </div>
        </div>
    `;

    // Remove any existing status modal to prevent duplicates
    const existingModal = document.getElementById("statusModal");
    if (existingModal) {
        existingModal.remove();
    }

    document.body.insertAdjacentHTML("beforeend", authModalHtml);

    const statusModalElement = document.getElementById("statusModal");
    const statusModal = new bootstrap.Modal(statusModalElement);

    statusModalElement.addEventListener("hidden.bs.modal", () => {
        statusModalElement.remove();
    });

    statusModal.show();
};

// Function to handle session-based notifications
window.handleSessionNotifications = function () {
    const notificationDataElement = document.getElementById(
        "session-notification-data"
    );

    if (notificationDataElement) {
        const successMessage = notificationDataElement.dataset.successMessage;
        const errorMessage = notificationDataElement.dataset.errorMessage;
        const isAuthPage =
            notificationDataElement.dataset.isAuthPage === "true";

        if (successMessage) {
            if (isAuthPage) {
                window.showAuthModal("Success", successMessage, "success");
            } else {
                window.showToast("Success", successMessage, "success");
            }
        } else if (errorMessage) {
            if (isAuthPage) {
                window.showAuthModal("Error", errorMessage, "error");
            } else {
                window.showToast("Error", errorMessage, "error");
            }
        }
    }
};

// Call handleSessionNotifications when the DOM is fully loaded
document.addEventListener(
    "DOMContentLoaded",
    window.handleSessionNotifications
);
