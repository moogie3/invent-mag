// page-loader.js
document.addEventListener("DOMContentLoaded", function () {
    setTimeout(() => {
        const pageLoader = document.querySelector(".page-loader");
        if (pageLoader) {
            pageLoader.style.display = "none";
        }
        
        const frameLoader = document.querySelector(".turbo-frame-loader");
        if (frameLoader) {
            frameLoader.style.display = "none";
        }
    }, 100);
});

// Turbo events
if (typeof Turbo !== 'undefined') {
    document.addEventListener("turbo:click", function (e) {
        console.log('[Turbo] Click:', e.detail.url);
    });
    
    document.addEventListener("turbo:loading", function () {
        console.log('[Turbo] Loading started');
        const frameLoader = document.querySelector(".turbo-frame-loader");
        if (frameLoader) {
            frameLoader.style.display = "flex";
        }
    });

    document.addEventListener("turbo:frame-load", function (e) {
        console.log('[Turbo] Frame loaded:', e.target.id);
        const frameLoader = document.querySelector(".turbo-frame-loader");
        if (frameLoader) {
            frameLoader.style.display = "none";
        }
    });
    
    document.addEventListener("turbo:load", function () {
        console.log('[Turbo] Page loaded');
        // Hide both loaders
        const pageLoader = document.querySelector(".page-loader");
        const frameLoader = document.querySelector(".turbo-frame-loader");
        
        if (pageLoader) {
            pageLoader.style.display = "none";
        }
        if (frameLoader) {
            frameLoader.style.display = "none";
        }
    });
}
