export function handleTaxActions() {
    window.setDefaultTax = function (taxId) {
        var msgEl = document.getElementById("setDefaultTaxForm");
        var msg = msgEl ? msgEl.getAttribute("data-confirm-msg") : null;
        if (!confirm(msg || "Are you sure you want to set this tax as the default?")) return;
        var form = document.getElementById("setDefaultTaxForm");
        if (form) {
            var base = form.getAttribute("data-base");
            form.action = (base || "/admin/settings/tax") + "/" + taxId + "/default";
            form.submit();
        }
    };

    window.setDeleteTaxFormAction = function (action, name) {
        var form = document.getElementById("deleteTaxForm");
        if (form) form.action = action;
        var nameEl = document.getElementById("deleteTaxName");
        if (nameEl) nameEl.textContent = name;
    };
}
