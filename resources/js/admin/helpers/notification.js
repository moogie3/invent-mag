import { playNotificationSound, playSuccessSound, playErrorSound } from '../utils/sound.js';
import { escapeHtml } from '../../utils/sanitize.js';

/**
 * Displays a modern, minimal toast notification.
 * This function should be called globally via `window.showToast`.
 *
 * @param {string} title - The title of the toast.
 * @param {string} message - The main message content of the toast.
 * @param {string} [type='info'] - The type of toast ('success', 'error', 'warning', 'info'). Determines color and icon.
 * @param {number} [duration=4000] - How long the toast should be visible in milliseconds.
 */
window.InventMagApp = window.InventMagApp || {};

window.InventMagApp.showToast = function (
    title,
    message,
    type = "info",
    duration
) {
    if (
        type === "success" &&
        window.userSettings &&
        !window.userSettings.show_success_messages
    ) {
        return; // Do not show success messages if disabled
    }

    const userDuration = window.userSettings
        ? parseInt(window.userSettings.notification_duration, 10) * 1000
        : 4000;
    const notificationDuration =
        duration !== undefined ? duration : userDuration;

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
    toastElement.style.backgroundColor = "transparent"; // Ensure transparent background
    toastElement.style.border = "none"; // Ensure no border

    const typeStyles = {
        success: {
            bg: "bg-success",
            border: "border-success",
            icon: "ti ti-check",
            iconColor: "text-white",
            textColor: "text-white",
            mutedColor: "text-white-50"
        },
        error: {
            bg: "bg-danger",
            border: "border-danger",
            icon: "ti ti-alert-circle",
            iconColor: "text-white",
            textColor: "text-white",
            mutedColor: "text-white-50"
        },
        warning: {
            bg: "bg-warning",
            border: "border-warning",
            icon: "ti ti-alert-triangle",
            iconColor: "text-white",
            textColor: "text-white",
            mutedColor: "text-white-50"
        },
        info: {
            bg: "bg-info",
            border: "border-info",
            icon: "ti ti-info-circle",
            iconColor: "text-white",
            textColor: "text-white",
            mutedColor: "text-white-50"
        },
    };

    const currentType = typeStyles[type] || typeStyles.info;

    // Sanitize user input to prevent XSS attacks
    const safeTitle = escapeHtml(title);
    const safeMessage = escapeHtml(message);

    toastElement.innerHTML = `
        <div class="toast-content ${currentType.bg} text-white p-3 position-relative overflow-hidden shadow-lg"
             style="min-width: 320px; border-radius: 8px; border: none;">
            <div class="d-flex align-items-center">
                <div class="flex-shrink-0 me-3 d-flex align-items-center justify-content-center rounded-circle" style="width: 32px; height: 32px; background: rgba(255,255,255,0.2);">
                    <i class="${currentType.icon} ${currentType.iconColor} fs-3"></i>
                </div>
                <div class="flex-grow-1 pe-2">
                    <div class="fw-bold ${currentType.textColor} mb-1" style="font-size: 1rem;">${safeTitle}</div>
                    <div class="${currentType.mutedColor}" style="font-size: 0.875rem; line-height: 1.4;">${safeMessage}</div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"
                        style="opacity: 0.8;"></button>
            </div>
        </div>
    `;

    container.appendChild(toastElement);

    let toastOptions = {
        delay: notificationDuration,
        autohide: true,
    };

    if (notificationDuration === 0) {
        toastOptions.autohide = false;
        toastOptions.delay = 9999999; // A very large number to keep it visible indefinitely
    }

    const toast = new bootstrap.Toast(toastElement, toastOptions);

    toastElement.addEventListener("hidden.bs.toast", () => {
        toastElement.remove();
    });

    toast.show();

    // Play sound using the centralized sound utility
    playNotificationSound(type);

    // Show browser notification if enabled and supported
    if (window.userSettings && window.userSettings.enable_browser_notifications && "Notification" in window) {
        if (Notification.permission === "granted") {
            new Notification(title, { body: message, icon: '/favicon.ico' });
        } else if (Notification.permission !== "denied") {
            Notification.requestPermission().then(permission => {
                if (permission === "granted") {
                    new Notification(title, { body: message, icon: '/favicon.ico' });
                }
            });
        }
    }
};

// Global function for confirmation modal
window.showConfirmModal = function (title, message, onConfirm) {
    // Sanitize inputs to prevent XSS
    const safeTitle = escapeHtml(title);
    const safeMessage = escapeHtml(message);

    const confirmModalHtml = `
        <div class="modal modal-blur fade" id="confirmModal" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
                <div class="modal-content border-0 shadow-lg rounded-3">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="ti ti-alert-triangle me-1"></i>${safeTitle}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-status bg-danger"></div>
                    <div class="modal-body text-center py-4">
                        <i class="ti ti-alert-triangle text-danger" style="font-size: 3rem;"></i>
                        <h3 class="mt-3">${safeTitle}</h3>
                        <div class="text-muted">${safeMessage}</div>
                    </div>
                    <div class="modal-footer">
                        <div class="w-100">
                            <div class="row">
                                <div class="col">
                                    <button type="button" class="btn w-100" data-bs-dismiss="modal">Cancel</button>
                                </div>
                                <div class="col">
                                    <button type="button" class="btn btn-danger w-100" id="confirmModalBtn">Confirm</button>
                                </div>
                            </div>
                        </div>
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

    // Clean up any existing backdrops first
    document.querySelectorAll('.modal-backdrop').forEach(b => b.remove());

    document.body.insertAdjacentHTML("beforeend", confirmModalHtml);

    const confirmModalElement = document.getElementById("confirmModal");
    const confirmModal = new bootstrap.Modal(confirmModalElement, {});

    const cleanupBackdrop = () => {
        document.querySelectorAll('.modal-backdrop').forEach(b => b.remove());
        document.body.classList.remove('modal-open');
        document.body.style.overflow = '';
    };

    confirmModalElement.addEventListener("shown.bs.modal", () => {
        const btn = document.getElementById("confirmModalBtn");
        if (btn) btn.focus();
    });

    const confirmBtn = document.getElementById("confirmModalBtn");
    if (confirmBtn) {
        confirmBtn.onclick = () => {
            cleanupBackdrop();
            onConfirm();
            confirmModal.hide();
        };
    }

    confirmModalElement.addEventListener("hidden.bs.modal", () => {
        cleanupBackdrop();
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

    // Play sound based on type
    if (type === "success") {
        playSuccessSound();
    } else if (type === "error") {
        playErrorSound();
    }

    // Sanitize inputs to prevent XSS
    const safeTitle = escapeHtml(title);
    const safeMessage = escapeHtml(message);

    // Remove any existing status modal to prevent duplicates
    const existingModal = document.getElementById("statusModal");
    if (existingModal) {
        existingModal.remove();
    }
    document.querySelectorAll('.modal-backdrop').forEach(b => b.remove());

    const authModalHtml = `
        <div class="modal modal-blur fade show" id="statusModal" tabindex="-1" role="dialog" aria-modal="true" style="display:block;">
            <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
                <div class="modal-content rounded-3 shadow-lg border-0">
                    <button type="button" class="btn-close" id="statusModalClose" aria-label="Close"></button>
                    <div class="modal-status ${modalStatusClass}"></div>
                    <div class="modal-body text-center py-5 px-4">
                        <i class="${iconClass} d-block mx-auto mb-3" style="font-size: 3rem;"></i>
                        <h3 class="fw-bold mb-3" style="font-size: 1.15rem;">${safeTitle}</h3>
                        <div class="text-muted" style="font-size: 1.1rem; line-height: 1.5;">${safeMessage}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-backdrop fade show"></div>
    `;

    document.body.insertAdjacentHTML("beforeend", authModalHtml);
    document.body.classList.add('modal-open');

    // Close button handler
    const closeBtn = document.getElementById("statusModalClose");
    if (closeBtn) {
        closeBtn.addEventListener("click", () => {
            const modal = document.getElementById("statusModal");
            const backdrop = document.querySelector(".modal-backdrop");
            if (modal) modal.remove();
            if (backdrop) backdrop.remove();
            document.body.classList.remove('modal-open');
            document.body.style.overflow = '';
        });
    }

    // Also close on backdrop click
    const backdrop = document.querySelector(".modal-backdrop");
    if (backdrop) {
        backdrop.addEventListener("click", () => {
            const modal = document.getElementById("statusModal");
            if (modal) modal.remove();
            backdrop.remove();
            document.body.classList.remove('modal-open');
            document.body.style.overflow = '';
        });
    }
};

// Function to handle session-based notifications
window.handleSessionNotifications = function () {
    const notificationDataElement = document.getElementById(
        "session-notification-data"
    );

    if (notificationDataElement && !notificationDataElement.dataset.processed) {
        const successMessage = notificationDataElement.dataset.successMessage;
        const errorMessage = notificationDataElement.dataset.errorMessage;
        const warningMessage = notificationDataElement.dataset.warningMessage;
        const isAuthPage =
            notificationDataElement.dataset.isAuthPage === "true";

        // Priority: error > warning > success
        if (errorMessage && errorMessage !== 'null' && errorMessage !== '') {
            if (isAuthPage) {
                window.showAuthModal("Error", errorMessage, "error");
            } else {
                InventMagApp.showToast("Error", errorMessage, "error");
            }
        } else if (warningMessage && warningMessage !== 'null' && warningMessage !== '') {
            if (isAuthPage) {
                window.showAuthModal("Warning", warningMessage, "warning");
            } else {
                InventMagApp.showToast("Warning", warningMessage, "warning");
            }
        } else if (successMessage && successMessage !== 'null' && successMessage !== '') {
            if (isAuthPage) {
                window.showAuthModal("Success", successMessage, "success");
            } else {
                InventMagApp.showToast("Success", successMessage, "success");
            }
        }
        
        // Mark as processed so it doesn't fire again for the same element
        notificationDataElement.dataset.processed = "true";
    }
};

// The call to handleSessionNotifications is now triggered from settings.js after user settings are loaded.