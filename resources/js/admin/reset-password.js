document.addEventListener("DOMContentLoaded", function () {
    // ============================================
    // Password Validation Logic
    // ============================================
    const passwordField = document.getElementById('password');
    const confirmPasswordField = document.getElementById('password_confirmation');
    const submitButton = document.getElementById('updatePasswordBtn');
    const strengthError = document.getElementById('password-strength-error');
    const mismatchError = document.getElementById('password-mismatch-error');

    if (passwordField && confirmPasswordField && submitButton) {
        
        /**
         * Validate password strength requirements
         * - At least 8 characters
         * - At least one uppercase letter (A-Z)
         * - At least one lowercase letter (a-z)
         * - At least one number (0-9)
         * - At least one special character from: !@#$%^&*()_+-=[]{}|;:,.<>?
         * 
         * @param {string} password - The password to validate
         * @returns {boolean} - True if password meets all requirements
         */
        function validatePasswordStrength(password) {
            if (password.length < 8) return false; // Must be at least 8 characters
            if (!/[a-z]/.test(password)) return false; // Must contain lowercase
            if (!/[A-Z]/.test(password)) return false; // Must contain uppercase
            if (!/\d/.test(password)) return false; // Must contain number
            if (!/[!@#$%^&*()_+\-=\[\]{}|;:,.<>?]/.test(password)) return false; // Must contain special char
            return true;
        }

        /**
         * Check if passwords match
         * Only validates if user has started typing in confirmation field
         * 
         * @returns {boolean} - True if passwords match or confirmation is empty
         */
        function validatePasswordMatch() {
            const password = passwordField.value;
            const confirmPassword = confirmPasswordField.value;
            
            // Only validate if user has started typing in confirmation field
            if (confirmPassword.length > 0 && password !== confirmPassword) {
                confirmPasswordField.classList.add('is-invalid');
                if (mismatchError) mismatchError.style.display = 'block';
                return false;
            } else {
                confirmPasswordField.classList.remove('is-invalid');
                if (mismatchError) mismatchError.style.display = 'none';
                return true;
            }
        }

        /**
         * Validate password strength and show feedback
         * Real-time validation on password field
         * 
         * @returns {boolean} - True if password is strong enough
         */
        function validateStrengthAndShowFeedback() {
            const password = passwordField.value;
            
            // Real-time validation on password field
            if (password.length > 0 && !validatePasswordStrength(password)) {
                passwordField.classList.add('is-invalid');
                if (strengthError) strengthError.style.display = 'block';
                return false;
            } else {
                passwordField.classList.remove('is-invalid');
                if (strengthError) strengthError.style.display = 'none';
                return true;
            }
        }

        /**
         * Enable/disable submit button based on all validations
         * Button is only enabled when:
         * - Both fields are filled
         * - Password meets strength requirements
         * - Passwords match
         */
        function updateSubmitButton() {
            const password = passwordField.value;
            const confirmPassword = confirmPasswordField.value;
            
            const isStrengthValid = validatePasswordStrength(password);
            const isMatchValid = password === confirmPassword;
            const bothFilled = password.length > 0 && confirmPassword.length > 0;
            
            if (bothFilled && isStrengthValid && isMatchValid) {
                submitButton.disabled = false;
            } else {
                submitButton.disabled = true;
            }
        }

        // ============================================
        // Event Listeners
        // ============================================

        // Validate strength in real-time as user types in password field
        passwordField.addEventListener('input', function() {
            validateStrengthAndShowFeedback();
            validatePasswordMatch(); // Also check match when password changes
            updateSubmitButton();
        });

        // Validate match only after user starts typing in confirmation field
        confirmPasswordField.addEventListener('input', function() {
            validatePasswordMatch();
            updateSubmitButton();
        });

        // Initial state - disable button until validation passes
        submitButton.disabled = true;
    }
});
