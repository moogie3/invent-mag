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
});