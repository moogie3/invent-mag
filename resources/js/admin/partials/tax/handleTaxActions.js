export function handleTaxActions() {
    window.setSetDefaultTaxFormAction = function (action, name) {
        var form = document.getElementById("setDefaultTaxFormModal");
        if (form) form.action = action;
        var nameEl = document.getElementById("newDefaultTaxName");
        if (nameEl) nameEl.textContent = name;
    };

    window.setDeleteTaxFormAction = function (action, name) {
        var form = document.getElementById("deleteTaxForm");
        if (form) form.action = action;
        var nameEl = document.getElementById("deleteTaxName");
        if (nameEl) nameEl.textContent = name;
    };
}
