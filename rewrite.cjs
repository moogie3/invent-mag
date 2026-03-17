const fs = require('fs');
const file = 'resources/js/admin/bootstrap-init.js';
let code = fs.readFileSync(file, 'utf8');

code = code.replace(
    /var popovers = document.querySelectorAll\('\[data-bs-toggle="popover"\]:not\(\.bs-init-done\)'\);/,
    `var popovers = document.querySelectorAll('[data-bs-toggle="popover"]:not(.bs-init-done)');\n            var modals = document.querySelectorAll('.modal:not(.bs-init-done)');`
);

code = code.replace(
    /if \(dropdowns.length === 0 && tooltips.length === 0 && popovers.length === 0\) \{/,
    `if (dropdowns.length === 0 && tooltips.length === 0 && popovers.length === 0 && modals.length === 0) {`
);

code = code.replace(
    /\(dropdowns.length \+ tooltips.length \+ popovers.length\) \+ ' components\.\.\.'\);/,
    `(dropdowns.length + tooltips.length + popovers.length + modals.length) + ' components...');`
);

const modalInitCode = `
            // Initialize all modals explicitly to prevent 'backdrop' undefined error in Tabler
            modals.forEach(function(modal) {
                if (!bootstrap.Modal.getInstance(modal)) {
                    try {
                        new bootstrap.Modal(modal, {});
                        modal.classList.add('bs-init-done');
                    } catch(e) {}
                }
            });
`;

code = code.replace(
    /console\.log\('Bootstrap initialization complete'\);/,
    modalInitCode + "\n            console.log('Bootstrap initialization complete');"
);

fs.writeFileSync(file, code);
