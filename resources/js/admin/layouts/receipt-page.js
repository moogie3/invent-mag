// receipt-page.js
// Specific JavaScript for receipt page to handle page loader

function hideLoader() {
    var loader = document.getElementById('global-loader');
    if (loader) {
        loader.style.display = 'none';
        loader.style.opacity = '0';
        loader.style.pointerEvents = 'none';
    }
    document.body.classList.remove('is-loading-page');
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', hideLoader);
} else {
    hideLoader();
}

// Also handle turbo:load in case it's a turbo navigation
document.addEventListener('turbo:load', hideLoader);
