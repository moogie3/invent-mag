document.addEventListener("DOMContentLoaded", function () {

    window.shortcutManager.register('ctrl+s', () => {
        const profileForm = document.getElementById('profileForm');
        if (profileForm) {
            profileForm.requestSubmit();
        }
    }, 'Save Profile');

    // Avatar Management Logic
    const avatarInput = document.getElementById('avatarInput');
    const deleteInput = document.getElementById('deleteAvatarInput');
    const removeBtn = document.getElementById('removeAvatarBtn');
    const avatarPreview = document.querySelector('.avatar-xl');

    if (avatarInput && deleteInput && removeBtn && avatarPreview) {
        // Handle File Selection
        avatarInput.addEventListener('change', function(event) {
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    avatarPreview.style.backgroundImage = `url('${e.target.result}')`;
                    // Ensure it looks like an image avatar
                    avatarPreview.classList.remove('avatar-initial');
                    avatarPreview.innerHTML = ''; 
                    
                    // Show remove button since we have a new image
                    removeBtn.style.display = 'inline-block';
                    // Reset delete flag, we are uploading new one
                    deleteInput.value = '0';
                }
                reader.readAsDataURL(file);
            }
        });

        // Handle Remove Button
        removeBtn.addEventListener('click', function() {
            // Clear input
            avatarInput.value = '';
            
            // Set delete flag
            deleteInput.value = '1';
            
            // Update UI to default placeholder
            avatarPreview.style.backgroundImage = 'none';
            avatarPreview.classList.add('avatar-initial');
            avatarPreview.innerHTML = '<i class="ti ti-person" style="font-size: 4rem;"></i>';
            
            // Hide remove button
            this.style.display = 'none';
        });
    }

    // ============================================
    // Password Validation Logic
    // ============================================
    const passwordField = document.getElementById('password');
    const confirmPasswordField = document.getElementById('password_confirmation');
    const passwordForm = document.getElementById('passwordForm');
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
                mismatchError.style.display = 'block';
                return false;
            } else {
                confirmPasswordField.classList.remove('is-invalid');
                mismatchError.style.display = 'none';
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
                strengthError.style.display = 'block';
                return false;
            } else {
                passwordField.classList.remove('is-invalid');
                strengthError.style.display = 'none';
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