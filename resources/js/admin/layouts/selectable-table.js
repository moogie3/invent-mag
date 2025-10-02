export function initSelectableTable() {
    console.log("initSelectableTable called!");
    const tables = document.querySelectorAll('.table-vcenter');
    let selectionModeActive = false;
    let lastActionType = null; // 'edit' or 'delete'

    tables.forEach(table => {
        const tbody = table.querySelector('tbody');
        if (!tbody) return;

        let currentRowIndex = -1;
        const rows = Array.from(tbody.querySelectorAll('tr'));

        const updateSelection = (newIndex) => {
            if (rows.length === 0) return;

            if (currentRowIndex !== -1) {
                rows[currentRowIndex].classList.remove('selected-row');
            }

            currentRowIndex = newIndex;
            rows[currentRowIndex].classList.add('selected-row');
            rows[currentRowIndex].scrollIntoView({
                behavior: 'smooth',
                block: 'nearest'
            });

            // Trigger toast notification
            const itemNameElement = rows[currentRowIndex].querySelector('.sort-name') || rows[currentRowIndex].querySelector('td:nth-child(3)');
            const itemName = itemNameElement ? itemNameElement.textContent.trim() : 'Unknown Item';

            if (window.InventMagApp && window.InventMagApp.showToast) {
                window.InventMagApp.showToast('Selected', `Item: ${itemName}`, 'info');
            }
        };

        const exitSelectionMode = () => {
            selectionModeActive = false;
            lastActionType = null;
            if (currentRowIndex !== -1) {
                rows[currentRowIndex].classList.remove('selected-row');
                currentRowIndex = -1;
            }
            if (window.InventMagApp && window.InventMagApp.showToast) {
                window.InventMagApp.showToast('Info', 'Selection mode deactivated.', 'info');
            }
        };

        const performAction = (selectedRow, actionType) => {
            let selector;
            if (actionType === 'edit') {
                selector = 'a.dropdown-item[href*="edit"]';
            } else if (actionType === 'delete') {
                selector = 'button.dropdown-item[data-bs-target="#deleteUserModal"]';
            } else {
                selector = 'button.dropdown-item[data-bs-target="#deleteModal"]';
            }

            const actionName = actionType === 'edit' ? 'Edit' : 'Delete';

            const dropdownToggle = selectedRow.querySelector('.dropdown-toggle');
            console.log("Dropdown toggle found:", dropdownToggle);

            if (dropdownToggle) {
                const bsDropdown = new bootstrap.Dropdown(dropdownToggle);
                bsDropdown.show();

                setTimeout(() => {
                    const openDropdownMenu = document.querySelector('.dropdown-menu.show');
                    console.log("Open dropdown menu found:", openDropdownMenu);

                    if (openDropdownMenu) {
                        let actionButton = null;
                        if (actionType === 'edit') {
                            const allDropdownItems = openDropdownMenu.querySelectorAll('.dropdown-item');
                            for (const item of allDropdownItems) {
                                if (item.textContent.trim().toLowerCase().includes('edit')) {
                                    actionButton = item;
                                    break;
                                }
                            }
                        } else {
                            actionButton = openDropdownMenu.querySelector('button.dropdown-item[data-bs-target="#deleteUserModal"]');
                            if (!actionButton) {
                                actionButton = openDropdownMenu.querySelector('button.dropdown-item[data-bs-target="#deleteModal"]');
                            }
                        }

                        console.log("Attempting to find action button with selector:", selector, "in open dropdown:", openDropdownMenu);
                        console.log("Found action button:", actionButton);

                        if (actionButton) {
                            if (actionType === 'edit') {
                                if (actionButton.tagName === 'A' && actionButton.getAttribute('href') && actionButton.getAttribute('href') !== '#') {
                                    window.location.href = actionButton.href;
                                } else {
                                    actionButton.click();
                                }
                            } else {
                                actionButton.click();
                            }
                        } else {
                            if (window.InventMagApp && window.InventMagApp.showToast) {
                                window.InventMagApp.showToast('Error', `Could not find ${actionName} button for the selected item.`, 'error');
                            }
                        }
                    } else {
                        console.error("No open dropdown menu found after showing dropdown toggle.");
                        if (window.InventMagApp && window.InventMagApp.showToast) {
                            window.InventMagApp.showToast('Error', `Dropdown menu did not open correctly.`, 'error');
                        }
                    }
                    bsDropdown.hide(); // Hide the dropdown after action
                }, 200); // Increased delay
            } else {
                if (window.InventMagApp && window.InventMagApp.showToast) {
                    window.InventMagApp.showToast('Error', `Could not find action dropdown for the selected item.`, 'error');
                }
            }
            exitSelectionMode();
        };

        document.addEventListener('keydown', (e) => {
            // Ignore if in an input field, unless it's Alt+E/D
            const isInputField = e.target.isContentEditable || ['INPUT', 'TEXTAREA', 'SELECT'].includes(e.target.tagName);
            if (isInputField && !(e.altKey && (e.key === 'e' || e.key === 'd'))) {
                return;
            }

            if (selectionModeActive) {
                if (e.key === 'ArrowDown') {
                    e.preventDefault();
                    let newIndex = currentRowIndex + 1;
                    if (newIndex >= rows.length) {
                        newIndex = 0; // Wrap around to the first row
                    }
                    updateSelection(newIndex);
                } else if (e.key === 'ArrowUp') {
                    e.preventDefault();
                    let newIndex = currentRowIndex - 1;
                    if (newIndex < 0) {
                        newIndex = rows.length - 1; // Wrap around to the last row
                    }
                    updateSelection(newIndex);
                } else if (e.key === 'Enter') {
                    e.preventDefault();
                    if (currentRowIndex !== -1 && lastActionType) {
                        performAction(rows[currentRowIndex], lastActionType);
                    }
                } else if (e.key === 'Escape') {
                    e.preventDefault();
                    exitSelectionMode();
                }
            }
        });

        window.shortcutManager.register('alt+e', () => {
            if (!selectionModeActive) {
                selectionModeActive = true;
                lastActionType = 'edit';
                if (rows.length > 0) {
                    updateSelection(0); // Select the first row by default
                }
                if (window.InventMagApp && window.InventMagApp.showToast) {
                    window.InventMagApp.showToast('Info', 'Selection mode activated for Edit. Use arrow keys to navigate, Enter to confirm.', 'info');
                }
            } else if (lastActionType === 'edit' && currentRowIndex !== -1) {
                // If already in edit selection mode and a row is selected, confirm action
                performAction(rows[currentRowIndex], 'edit');
            }
        }, 'Activate Selection Mode for Edit');

        window.shortcutManager.register('alt+d', () => {
            if (!selectionModeActive) {
                selectionModeActive = true;
                lastActionType = 'delete';
                if (rows.length > 0) {
                    updateSelection(0); // Select the first row by default
                }
                if (window.InventMagApp && window.InventMagApp.showToast) {
                    window.InventMagApp.showToast('Info', 'Selection mode activated for Delete. Use arrow keys to navigate, Enter to confirm.', 'info');
                }
            } else if (lastActionType === 'delete' && currentRowIndex !== -1) {
                // If already in delete selection mode and a row is selected, confirm action
                performAction(rows[currentRowIndex], 'delete');
            }
        }, 'Activate Selection Mode for Delete');
    });
}








