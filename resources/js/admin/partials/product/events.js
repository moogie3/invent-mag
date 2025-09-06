export function initExpiryCheckbox() {
    const checkbox = document.getElementById("has_expiry");
    const container = document.getElementById("expiry_date_container");

    if (checkbox && container) {
        container.style.display = checkbox.checked ? "block" : "none";

        checkbox.addEventListener("change", function () {
            container.style.display = this.checked ? "block" : "none";
            if (this.checked) {
                const dateInput = container.querySelector(
                    "input[name='expiry_date']"
                );
                if (dateInput && dateInput._flatpickr) {
                    dateInput._flatpickr.redraw();
                }
            }
        });
    }
}

export function initFlatpickr() {
    if (typeof flatpickr !== "function") {
        console.error("Flatpickr is not loaded");
        return;
    }

    const expiryInput = document.querySelector("input[name='expiry_date']");
    if (expiryInput) {
        flatpickr(expiryInput, {
            dateFormat: "Y-m-d",
            altInput: true,
            altFormat: "d-m-Y",
            allowInput: true,
            defaultDate: expiryInput.value || null,
        });
    }
}

export function initializeEntriesSelector() {
    const entriesSelect = document.getElementById("entriesSelect");
    if (entriesSelect) {
        entriesSelect.addEventListener("change", function () {
            const currentUrl = new URL(window.location);
            currentUrl.searchParams.set("entries", this.value);
            currentUrl.searchParams.delete("page");
            window.location.href = currentUrl.toString();
        });
    }
}

export function initKeyboardShortcuts() {
    document.addEventListener("keydown", function (e) {
        if ((e.ctrlKey || e.metaKey) && e.key === "k") {
            e.preventDefault();
            const searchInput = document.getElementById("searchInput");
            if (searchInput) {
                searchInput.focus();
                searchInput.select();
            }
        }

        if (e.key === "Escape") {
            const searchInput = document.getElementById("searchInput");
            if (searchInput && document.activeElement === searchInput) {
                searchInput.value = "";
                const event = new Event("input", { bubbles: true });
                searchInput.dispatchEvent(event);
                searchInput.blur();
            }
        }
    });
}

export function initExport() {
    window.exportProducts = function (format = "csv") {
        const form = document.createElement("form");
        form.method = "POST";
        form.action = `/admin/product/export/${format}`;
        form.style.display = "none";

        const csrf = document.querySelector('meta[name="csrf-token"]');
        if (csrf) {
            const token = document.createElement("input");
            token.type = "hidden";
            token.name = "_token";
            token.value = csrf.getAttribute("content");
            form.appendChild(token);
        }

        const searchInput = document.getElementById("searchInput");
        if (searchInput && searchInput.value.trim()) {
            const searchQuery = document.createElement("input");
            searchQuery.type = "hidden";
            searchQuery.name = "search";
            searchQuery.value = searchInput.value.trim();
            form.appendChild(searchQuery);
        }

        document.body.appendChild(form);
        form.submit();

        setTimeout(() => {
            if (document.body.contains(form)) {
                document.body.removeChild(form);
            }
        }, 2000);
    };
}
