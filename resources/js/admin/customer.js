import { initEditCustomerModal } from "./partials/customer/editModal/main.js";
import { initCrmCustomerModal } from "./partials/customer/crmModal/main.js";
import { initSelectableTable } from "./layouts/selectable-table.js";

document.addEventListener("DOMContentLoaded", function () {
    initEditCustomerModal();
    initCrmCustomerModal();
    initSelectableTable();

    window.shortcutManager.register(
        "alt+n",
        () => {
            const createModal = new bootstrap.Modal(
                document.getElementById("createCustomerModal")
            );
            createModal.show();
        },
        "New Customer"
    );

    window.shortcutManager.register(
        "ctrl+s",
        () => {
            const createModal = document.getElementById("createCustomerModal");
            const editModal = document.getElementById("editCustomerModal");

            if (createModal && createModal.classList.contains("show")) {
                const form = createModal.querySelector("form");
                if (form) {
                    form.requestSubmit();
                }
            } else if (editModal && editModal.classList.contains("show")) {
                const form = editModal.querySelector("form");
                if (form) {
                    form.requestSubmit();
                }
            }
        },
        "Save Customer"
    );
});

export function exportCustomers(exportOption = 'csv') {
    const form = document.createElement("form");
    form.method = "POST";
    form.action = "/admin/customer/export";
    form.style.display = "none";

    const csrf = document.querySelector('meta[name="csrf-token"]');
    if (csrf) {
        const token = document.createElement("input");
        token.type = "hidden";
        token.name = "_token";
        token.value = csrf.getAttribute("content");
        form.appendChild(token);
    }

    const exportOptionInput = document.createElement("input");
    exportOptionInput.type = "hidden";
    exportOptionInput.name = "export_option";
    exportOptionInput.value = exportOption;
    form.appendChild(exportOptionInput);

    document.body.appendChild(form);
    form.submit();
    setTimeout(() => document.body.removeChild(form), 2000);
}

window.exportCustomers = exportCustomers;
