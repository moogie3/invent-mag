/**
 * Entry point for the mobile PWA shells under /m.
 *
 * Which page module runs is decided by <body data-page>, set by the Blade
 * layout — no router, no client-side navigation, just full page loads that
 * the service worker can fall back to when the device is offline.
 */

import { initLoginPage } from './mobile/pages/login.js';
import { initDashboardPage } from './mobile/pages/dashboard.js';
import { initPosPage } from './mobile/pages/pos.js';
import { initProductsPage } from './mobile/pages/products.js';

function registerServiceWorker() {
    if (!('serviceWorker' in navigator)) {
        return;
    }
    // Registered on load so it never competes with first paint.
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js').catch(() => {
            // Private browsing or a disabled SW: the app still works online.
        });
    });
}

function boot() {
    const page = (document.body && document.body.dataset.page) || 'dashboard';

    switch (page) {
        case 'login':
            initLoginPage();
            break;
        case 'dashboard':
            initDashboardPage();
            break;
        case 'pos':
            initPosPage();
            break;
        case 'products':
            initProductsPage();
            break;
        default:
            initDashboardPage();
    }

    registerServiceWorker();
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
} else {
    boot();
}
