document.addEventListener("DOMContentLoaded", function () {
    // Toggle password visibility
    if (document.getElementById("toggle-password")) {
        const passwordField = document.getElementById("password");
        const togglePassword = document.getElementById("toggle-password");
        const toggleIcon = togglePassword.querySelector("i");

        togglePassword.addEventListener("click", function (e) {
            e.preventDefault();
            if (passwordField.type === "password") {
                passwordField.type = "text";
                toggleIcon.classList.remove("ti-eye");
                toggleIcon.classList.add("ti-eye-off");
            } else {
                passwordField.type = "password";
                toggleIcon.classList.remove("ti-eye-off");
                toggleIcon.classList.add("ti-eye");
            }
        });
    }

    // Show content after loading
    setTimeout(function () {
        const loadingContainer = document.getElementById("loading-container");
        const authContent = document.getElementById("auth-content");

        if (loadingContainer) loadingContainer.style.display = "none";
        if (authContent) authContent.style.display = "block";
    }, 800);
});
