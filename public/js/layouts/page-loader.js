// page-loader.js
document.addEventListener("DOMContentLoaded", function() {
    setTimeout(() => {
        document.querySelector(".page-center").style.display = "none";
        document.querySelector(".main-content").style.visibility = "visible";
    }, 300);
});
