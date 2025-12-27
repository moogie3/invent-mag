export function togglePasswordVisibility() {
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
}
