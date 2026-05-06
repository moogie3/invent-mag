document.addEventListener("turbo:load", initBackup);
document.addEventListener("DOMContentLoaded", initBackup);

function initBackup() {
    const backupButton = document.getElementById("backupButton");
    if (backupButton) {
        backupButton.addEventListener("click", createBackup);
    }
    
    // Handle restore form submission - disable button and show loading state
    const restoreForm = document.querySelector('form[action*="restore"]');
    if (restoreForm) {
        restoreForm.addEventListener('submit', function(e) {
            const submitBtn = e.target.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status"></span> Restoring...';
            }
            // Let form submit normally to show Laravel flash messages on redirect
        });
    }
}

function createBackup() {
    const btn = document.getElementById("backupButton");
    const typeSelect = document.getElementById("backupType");
    if (!btn || !typeSelect) return;

    const url = btn.dataset.url;
    
    const originalHTML = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status"></span> Creating...';

    const formData = new FormData();
    formData.append("type", typeSelect.value);

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    
    fetch(url, {
        method: "POST",
        body: formData,
        headers: {
            "X-CSRF-TOKEN": csrfToken,
            "Accept": "application/json",
        },
    })
    .then((response) => {
        // Even on error, try to get JSON
        if (response.ok) {
            return response.json().then(data => ({ ok: true, data }));
        }
        return response.json().then(err => ({ ok: false, error: err }));
    })
    .then(result => {
        if (result.ok && result.data) {
            if (result.data.success) {
                showNotification("success", result.data.message || "Backup created successfully");
                // Reload page to show new files
                window.location.reload();
            } else if (result.data.warning) {
                showNotification("warning", result.data.message);
            } else {
                showNotification("error", result.data.message);
            }
        } else if (result.error) {
            showNotification("error", result.error.message || "Backup failed");
        }
    })
    .catch((error) => {
        console.error("Backup error:", error);
        showNotification("error", "An error occurred: " + error.message);
    })
    .finally(() => {
        btn.disabled = false;
        btn.innerHTML = originalHTML;
    });
}

function showNotification(type, message) {
    if (typeof InventMagApp !== 'undefined' && typeof InventMagApp.showToast === 'function') {
        const title = type === 'success' ? 'Success' : type === 'error' ? 'Error' : 'Warning';
        InventMagApp.showToast(title, message, type);
    } else {
        const alertClass = type === "success" ? "alert-success" : type === "error" ? "alert-danger" : "alert-warning";
        const alert = document.createElement("div");
        alert.className = `alert ${alertClass} alert-dismissible fade show position-fixed top-0 end-0 m-3 backup-alert`;
        alert.style.zIndex = "9999";
        alert.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        document.body.appendChild(alert);

        setTimeout(() => {
            alert.remove();
        }, 5000);
    }
}