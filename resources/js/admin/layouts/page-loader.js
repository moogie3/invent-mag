// page-loader.js
document.addEventListener("DOMContentLoaded", function () {
    setTimeout(() => {
        const pageLoader = document.querySelector(".page-loader");
        const mainContent = document.querySelector(".main-content");

        if (pageLoader) {
            pageLoader.style.display = "none";
        }
        if (mainContent) {
            mainContent.style.opacity = "1";
        }
    }, 100); // Added a small delay
});
