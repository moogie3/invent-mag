export function initSelectableTable(options = {}) {
    const { registerEditShortcut = true, registerDeleteShortcut = true } = options;
    // console.log("initSelectableTable called!");
    const tables = document.querySelectorAll(".table-vcenter");

    // Exactly one table may own the keyboard shortcuts and the keydown
    // handler. A page can carry several .table-vcenter tables (the list
    // table plus hidden modal tables such as lowStockModal /
    // expiringSoonModal); registering per table let shortcutManager's
    // overwrite semantics hand ownership to whichever table came last in
    // the DOM — and on the product, sales and PO indexes that last table is
    // a modal with zero rows, so Alt+E/D activated against an empty set and
    // appeared to do nothing. Prefer the list table, then any table that
    // actually has rows.
    const ownerTable =
        Array.from(tables).find((t) => t.closest("#invoiceTableContainer")) ||
        Array.from(tables).find((t) => t.querySelector("tbody tr")) ||
        tables[0] ||
        null;

    let selectionModeActive = false;
    let lastActionType = null; // 'edit' or 'delete'

    tables.forEach((table) => {
        if (table !== ownerTable) return;
        // Resolved lazily, never captured. A server-side search swaps the
        // table region out from under us (partials/sorting/serverSearch.js),
        // which detaches the table node handed to us here - a snapshot taken
        // at init would keep pointing at rows no longer in the document and
        // Alt+E/D would walk ghost rows. Re-resolving on every access keeps
        // the shortcut working against whatever markup is current.
        const resolveRows = () => {
            let root = table;
            if (!table.isConnected) {
                const container =
                    document.getElementById("invoiceTableContainer");
                root = (container && container.querySelector("table")) || table;
            }
            const tbody = root.querySelector("tbody");
            const found = tbody ? Array.from(tbody.querySelectorAll("tr")) : [];

            // A swap can shrink the row set; keep the selection index inside
            // the new bounds instead of letting it point past the array.
            if (currentRowIndex >= found.length) {
                currentRowIndex = found.length - 1;
            }

            return found;
        };

        let currentRowIndex = -1;

        const updateSelection = (newIndex) => {
            const rows = resolveRows();
            if (rows.length === 0) return;

            if (currentRowIndex !== -1 && rows[currentRowIndex]) {
                rows[currentRowIndex].classList.remove(
                    "selected-row",
                    "selected-delete"
                );
            }

            currentRowIndex = newIndex;
            rows[currentRowIndex].classList.add("selected-row");
            // Delete mode wears the same marker in the danger hue (app.css),
            // so the two modes are never confusable at a glance.
            rows[currentRowIndex].classList.toggle(
                "selected-delete",
                lastActionType === "delete"
            );
            rows[currentRowIndex].scrollIntoView({
                behavior: "smooth",
                block: "nearest",
            });

            // Toast follows the mode: "Editing" for Alt+E, "Deleting" for
            // Alt+D - one toast stack that reads edit and one that reads
            // delete, never a generic "Selected" for both.
            const itemNameElement =
                rows[currentRowIndex].querySelector(".sort-name") ||
                rows[currentRowIndex].querySelector("td:nth-child(3)");
            const itemName = itemNameElement
                ? itemNameElement.textContent.trim()
                : "Unknown Item";

            if (window.InventMagApp && window.InventMagApp.showToast) {
                const deleting = lastActionType === "delete";
                window.InventMagApp.showToast(
                    deleting ? "Deleting" : "Editing",
                    `Item: ${itemName}`,
                    deleting ? "error" : "info"
                );
            }
        };

        // Announced on activation and on an edit<->delete switch, with the
        // same colour split as the row toast so the stack stays unambiguous.
        const announceMode = (mode) => {
            if (window.InventMagApp && window.InventMagApp.showToast) {
                const label = mode === "delete" ? "Delete" : "Edit";
                window.InventMagApp.showToast(
                    `${label} mode`,
                    "Selection mode activated. Use arrow keys to navigate, Enter to confirm.",
                    mode === "delete" ? "error" : "info"
                );
            }
        };

        const exitSelectionMode = () => {
            const rows = resolveRows();
            const wasDelete = lastActionType === "delete";
            selectionModeActive = false;
            lastActionType = null;
            if (currentRowIndex !== -1 && rows[currentRowIndex]) {
                rows[currentRowIndex].classList.remove(
                    "selected-row",
                    "selected-delete"
                );
            }
            currentRowIndex = -1;
            if (window.InventMagApp && window.InventMagApp.showToast) {
                window.InventMagApp.showToast(
                    "Info",
                    wasDelete
                        ? "Delete selection mode deactivated."
                        : "Edit selection mode deactivated.",
                    "info"
                );
            }
        };

        const performAction = (selectedRow, actionType) => {
            const actionName = actionType === "edit" ? "Edit" : "Delete";

            const dropdownToggle =
                selectedRow.querySelector(".dropdown-toggle");
            // console.log("Dropdown toggle found:", dropdownToggle);

            if (dropdownToggle) {
                const bsDropdown = new bootstrap.Dropdown(dropdownToggle);
                bsDropdown.show();

                setTimeout(() => {
                    const openDropdownMenu = document.querySelector(
                        ".dropdown-menu.show"
                    );
                    // console.log("Open dropdown menu found:", openDropdownMenu);

                    if (openDropdownMenu) {
                        let actionButton = null;
                        if (actionType === "edit") {
                            const allDropdownItems =
                                openDropdownMenu.querySelectorAll(
                                    ".dropdown-item"
                                );
                            for (const item of allDropdownItems) {
                                if (
                                    item.textContent
                                        .trim()
                                        .toLowerCase()
                                        .includes("edit")
                                ) {
                                    actionButton = item;
                                    break;
                                }
                            }
                        } else {
                            // The delete item is not a fixed shape: most pages
                            // use a <button data-bs-target="#delete...Modal">,
                            // but the id varies per page (#deleteModal,
                            // #deleteTaxModal, #deletePeriodModal1, ...) and
                            // some (quotations) use an <a>. The old lookup only
                            // knew two ids on <button> and failed everywhere
                            // else. Match on target, then href, then label.
                            const allDropdownItems =
                                openDropdownMenu.querySelectorAll(
                                    ".dropdown-item"
                                );
                            const wantsDelete = (item) => {
                                const target = (
                                    item.getAttribute("data-bs-target") || ""
                                ).toLowerCase();
                                const href = (
                                    item.getAttribute("href") || ""
                                ).toLowerCase();
                                const text = item.textContent
                                    .trim()
                                    .toLowerCase();
                                return (
                                    target.includes("delete") ||
                                    href.includes("delete") ||
                                    text.includes("delete")
                                );
                            };
                            for (const item of allDropdownItems) {
                                if (wantsDelete(item)) {
                                    actionButton = item;
                                    break;
                                }
                            }
                        }

                        // console.log("Found action button:", actionButton);

                        if (actionButton) {
                            if (actionType === "edit") {
                                if (
                                    actionButton.tagName === "A" &&
                                    actionButton.getAttribute("href") &&
                                    actionButton.getAttribute("href") !== "#"
                                ) {
                                    window.location.href = actionButton.href;
                                } else {
                                    actionButton.click();
                                }
                            } else {
                                actionButton.click();
                            }
                        } else {
                            if (
                                window.InventMagApp &&
                                window.InventMagApp.showToast
                            ) {
                                window.InventMagApp.showToast(
                                    "Error",
                                    `Could not find ${actionName} button for the selected item.`,
                                    "error"
                                );
                            }
                        }
                    } else {
                        // console.error(
                        //     "No open dropdown menu found after showing dropdown toggle."
                        // );
                        if (
                            window.InventMagApp &&
                            window.InventMagApp.showToast
                        ) {
                            window.InventMagApp.showToast(
                                "Error",
                                `Dropdown menu did not open correctly.`,
                                "error"
                            );
                        }
                    }
                    bsDropdown.hide(); // Hide the dropdown after action
                }, 200); // Increased delay
            } else {
                // No dropdown on this row: the actions are plain buttons in
                // the cells (users index: .edit-user-btn / .delete-user-btn).
                // Alt+D used to stop here with "Could not find action
                // dropdown" - find the button in the row itself instead.
                const matches = (item) => {
                    // data-bs-target / href first: #deleteUserModal and
                    // .../users/{id}/edit are the unambiguous signals; class
                    // and title (edit-user-btn, "Delete") are the fallback.
                    const primary = (
                        (item.getAttribute("data-bs-target") || "") +
                        " " +
                        (item.getAttribute("href") || "")
                    ).toLowerCase();
                    const secondary = (
                        (item.getAttribute("class") || "") +
                        " " +
                        (item.getAttribute("title") || "") +
                        " " +
                        item.textContent
                    ).toLowerCase();
                    const want = actionType === "edit" ? "edit" : "delete";
                    return primary.includes(want) || secondary.includes(want);
                };
                const candidates = selectedRow.querySelectorAll(
                    "button, a[href]"
                );
                let actionButton = null;
                for (const item of candidates) {
                    if (matches(item)) {
                        actionButton = item;
                        break;
                    }
                }

                if (actionButton) {
                    if (
                        actionButton.tagName === "A" &&
                        actionButton.getAttribute("href") &&
                        actionButton.getAttribute("href") !== "#"
                    ) {
                        window.location.href = actionButton.href;
                    } else {
                        actionButton.click();
                    }
                } else if (
                    window.InventMagApp &&
                    window.InventMagApp.showToast
                ) {
                    window.InventMagApp.showToast(
                        "Error",
                        `Could not find ${actionName} button for the selected item.`,
                        "error"
                    );
                }
            }
            exitSelectionMode();
        };

        document.addEventListener("keydown", (e) => {
            // Ignore if in an input field, unless it's Alt+E/D
            const isInputField =
                e.target.isContentEditable ||
                ["INPUT", "TEXTAREA", "SELECT"].includes(e.target.tagName);
            if (
                isInputField &&
                !(e.altKey && (e.key === "e" || e.key === "d"))
            ) {
                return;
            }

            if (selectionModeActive) {
                const rows = resolveRows();
                if (e.key === "ArrowDown") {
                    e.preventDefault();
                    let newIndex = currentRowIndex + 1;
                    if (newIndex >= rows.length) {
                        newIndex = 0; // Wrap around to the first row
                    }
                    updateSelection(newIndex);
                } else if (e.key === "ArrowUp") {
                    e.preventDefault();
                    let newIndex = currentRowIndex - 1;
                    if (newIndex < 0) {
                        newIndex = rows.length - 1; // Wrap around to the last row
                    }
                    updateSelection(newIndex);
                } else if (e.key === "Enter") {
                    e.preventDefault();
                    if (currentRowIndex !== -1 && lastActionType) {
                        performAction(rows[currentRowIndex], lastActionType);
                    }
                } else if (e.key === "Escape") {
                    e.preventDefault();
                    exitSelectionMode();
                }
            }
        });

        if (registerEditShortcut && window.shortcutManager) {
            window.shortcutManager.register(
                "alt+e",
                () => {
                    const rows = resolveRows();
                    if (!selectionModeActive) {
                        selectionModeActive = true;
                        lastActionType = "edit";
                        if (rows.length > 0) {
                            updateSelection(0); // Select the first row by default
                        }
                        announceMode("edit");
                    } else if (lastActionType !== "edit") {
                        // Alt+D owns the selection: Alt+E switches it to edit,
                        // keeping the highlighted row (was silently ignored).
                        lastActionType = "edit";
                        if (currentRowIndex !== -1 && rows[currentRowIndex]) {
                            updateSelection(currentRowIndex);
                        }
                        announceMode("edit");
                    } else if (currentRowIndex !== -1) {
                        // Already in edit selection mode with a row selected:
                        // confirm the action.
                        performAction(rows[currentRowIndex], "edit");
                    } else if (rows.length > 0) {
                        updateSelection(0);
                    }
                },
                "Activate Selection Mode for Edit"
            );
        }

        if (registerDeleteShortcut && window.shortcutManager) {
            window.shortcutManager.register(
                "alt+d",
                () => {
                    const rows = resolveRows();
                    if (!selectionModeActive) {
                        selectionModeActive = true;
                        lastActionType = "delete";
                        if (rows.length > 0) {
                            updateSelection(0); // Select the first row by default
                        }
                        announceMode("delete");
                    } else if (lastActionType !== "delete") {
                        // Alt+E owns the selection: Alt+D switches it to
                        // delete - red line, "Deleting" toasts from here on
                        // (was silently ignored, leaving edit mode active).
                        lastActionType = "delete";
                        if (currentRowIndex !== -1 && rows[currentRowIndex]) {
                            updateSelection(currentRowIndex);
                        }
                        announceMode("delete");
                    } else if (currentRowIndex !== -1) {
                        // Already in delete selection mode with a row selected:
                        // confirm the action.
                        performAction(rows[currentRowIndex], "delete");
                    } else if (rows.length > 0) {
                        updateSelection(0);
                    }
                },
                "Activate Selection Mode for Delete"
            );
        }
    });
}
