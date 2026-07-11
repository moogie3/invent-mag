import { onPageLoad } from './utils/onPageLoad.js';

/**
 * Notifications page — mark-as-read and clear-all functionality.
 *
 * Server-side URLs are passed via a hidden config div:
 *   #notifications-config
 *     data-mark-read-base-url  — base URL for mark-read (notifId appended at runtime)
 *     data-clear-all-url       — full URL for clear-all route
 *
 * CSRF token is read from <meta name="csrf-token">.
 */

function getConfig() {
    const el = document.getElementById('notifications-config');
    if (!el) return null;
    return {
        markReadBaseUrl: el.dataset.markReadBaseUrl,
        clearAllUrl: el.dataset.clearAllUrl,
        csrfToken: document.querySelector('meta[name="csrf-token"]')?.content ?? '',
    };
}

function initMarkAsRead(config) {
    document.querySelectorAll('.mark-as-read-btn').forEach(btn => {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();

            const notifId = this.getAttribute('data-id');
            const targetId = this.getAttribute('data-target');
            const targetEl = document.getElementById(targetId);

            if (!notifId) return;

            // Disable button to prevent double-clicks.
            this.disabled = true;
            this.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>';

            fetch(`${config.markReadBaseUrl}/${notifId}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': config.csrfToken,
                    'Accept': 'application/json',
                },
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        if (targetEl) {
                            targetEl.style.transition = 'all 0.4s ease';
                            targetEl.style.opacity = '0';
                            targetEl.style.transform = 'translateX(20px)';

                            setTimeout(() => {
                                targetEl.remove();

                                // Update notification count badge if present.
                                const badge = document.querySelector('#notification-bell .badge');
                                if (badge) {
                                    const currentCount = parseInt(badge.innerText);
                                    if (!isNaN(currentCount) && currentCount > 1) {
                                        badge.innerText = currentCount - 1;
                                    } else {
                                        badge.remove();
                                        const dot = document.getElementById('notification-dot');
                                        if (dot) dot.remove();
                                    }
                                }

                                // Show empty-state placeholder if a tab has no items left.
                                ['financial', 'lowstock', 'expiring'].forEach(type => {
                                    const tab = document.getElementById(`tab-${type}`);
                                    if (tab && tab.querySelectorAll('.notification-item').length === 0 && !tab.querySelector('.empty')) {
                                        const icon = type === 'financial' ? 'ti-receipt' : (type === 'lowstock' ? 'ti-box' : 'ti-calendar-check');
                                        const msg  = type === 'financial' ? 'No financial notifications' : (type === 'lowstock' ? 'No low stock products' : 'No expiring products');
                                        tab.innerHTML = `
                                            <div class="empty py-5">
                                                <div class="empty-img"><i class="ti ${icon} text-muted opacity-50" style="font-size: 6rem;"></i></div>
                                                <p class="empty-title mt-3 fs-3">${msg}</p>
                                                <p class="empty-subtitle text-muted">You're all caught up here.</p>
                                            </div>
                                        `;
                                    }
                                });
                            }, 400);
                        }
                    }
                })
                .catch(error => {
                    console.error('Error marking as read:', error);
                    this.disabled = false;
                    this.innerHTML = '<i class="ti ti-x"></i>';
                });
        });
    });
}

function initClearAll(config) {
    const clearAllBtn = document.getElementById('clear-all-btn');
    const confirmClearAllBtn = document.getElementById('confirm-clear-all-btn');

    if (clearAllBtn) {
        clearAllBtn.addEventListener('click', function (e) {
            e.preventDefault();
            const bs = window.bootstrap || (window.tabler && window.tabler.bootstrap);
            const modalEl = document.getElementById('clearAllNotificationsModal');
            if (bs && bs.Modal) {
                new bs.Modal(modalEl).show();
            } else {
                // Fallback for environments where Bootstrap modal is unavailable.
                modalEl.classList.add('show');
                modalEl.style.display = 'block';
                document.body.classList.add('modal-open');
            }
        });
    }

    if (confirmClearAllBtn) {
        confirmClearAllBtn.addEventListener('click', function () {
            const btn = this;
            const originalText = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> Clearing...';

            fetch(config.clearAllUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': config.csrfToken,
                    'Accept': 'application/json',
                },
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        window.location.reload();
                    }
                })
                .catch(error => {
                    console.error('Error clearing notifications:', error);
                    btn.disabled = false;
                    btn.innerHTML = originalText;
                });
        });
    }
}

onPageLoad(function () {
    if (!window.location.pathname.includes('/notifications')) return;

    const config = getConfig();
    if (!config) return;

    initMarkAsRead(config);
    initClearAll(config);
});
