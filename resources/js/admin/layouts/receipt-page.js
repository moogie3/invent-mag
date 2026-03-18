// receipt-page.js
// Specific JavaScript for receipt page to handle page loader

document.addEventListener('DOMContentLoaded', function() {
    // Immediately hide the global loader on receipt page
    var loader = document.getElementById('global-loader');
    if (loader) {
        loader.style.display = 'none';
        loader.style.opacity = '0';
        loader.style.pointerEvents = 'none';
    }
    document.body.classList.remove('is-loading-page');
});

// Also handle turbo:load in case it's a turbo navigation
document.addEventListener('turbo:load', function() {
    var loader = document.getElementById('global-loader');
    if (loader) {
        loader.style.display = 'none';
        loader.style.opacity = '0';
    }
});
