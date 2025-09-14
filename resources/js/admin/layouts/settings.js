document.addEventListener('DOMContentLoaded', function () {
    const navigationTypeSelect = document.querySelector('select[name="navigation_type"]');
    const navbarOptionsWrapper = document.getElementById('navbar-options-wrapper');
    const navbarInputs = navbarOptionsWrapper.querySelectorAll('input, select');

    const sidebarOptionsWrapper = document.getElementById('sidebar-options-wrapper');
    const sidebarInputs = sidebarOptionsWrapper.querySelectorAll('input, select');

    function toggleNavigationOptions() {
        const selectedNavigationType = navigationTypeSelect.value;

        // Handle Navbar Options
        if (selectedNavigationType === 'sidebar') {
            navbarOptionsWrapper.classList.add('disabled-option');
            navbarInputs.forEach(input => {
                input.setAttribute('disabled', 'disabled');
                if (input.type === 'checkbox') {
                    input.checked = false;
                }
            });
        } else {
            navbarOptionsWrapper.classList.remove('disabled-option');
            navbarInputs.forEach(input => {
                input.removeAttribute('disabled');
            });
        }

        // Handle Sidebar Options
        if (selectedNavigationType === 'navbar') {
            sidebarOptionsWrapper.classList.add('disabled-option');
            sidebarInputs.forEach(input => {
                input.setAttribute('disabled', 'disabled');
                if (input.type === 'checkbox') {
                    input.checked = false;
                }
            });
        } else {
            sidebarOptionsWrapper.classList.remove('disabled-option');
            sidebarInputs.forEach(input => {
                input.removeAttribute('disabled');
            });
        }
    }

    // Initial check on page load
    toggleNavigationOptions();

    // Add event listener for changes
    navigationTypeSelect.addEventListener('change', toggleNavigationOptions);
});