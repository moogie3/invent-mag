import '../../admin/helpers/notification.js';

function handleNotifications() {
    if (window.handleSessionNotifications) {
        window.handleSessionNotifications();
    }
}

function initLogout() {
    const logoutBtn = document.querySelector('.sidebar-footer-btn-danger');
    if (logoutBtn) {
        logoutBtn.addEventListener('click', function (e) {
            e.preventDefault();
            const form = document.getElementById('sa-logout-form');
            if (form) {
                form.submit();
            }
        });
    }
}

if (document.readyState === 'complete' || document.readyState === 'interactive') {
    handleNotifications();
    initLogout();
} else {
    document.addEventListener('DOMContentLoaded', function () {
        handleNotifications();
        initLogout();
    });
}

document.addEventListener('turbo:load', function () {
    handleNotifications();
    initLogout();
});
