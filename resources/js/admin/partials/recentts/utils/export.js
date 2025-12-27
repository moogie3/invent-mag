export function exportTransactions() {
    const params = new URLSearchParams(window.location.search);
    params.set("export", "excel");
    const transactionsRoute =
        document
            .querySelector('meta[name="transactions-route"]')
            ?.getAttribute("content") || "/admin/transactions";
    window.location.href = transactionsRoute + "?" + params.toString();
}

export function bulkExport() {
    const selected = Array.from(
        document.querySelectorAll(".row-checkbox:checked")
    ).map((cb) => cb.value);
    if (selected.length === 0) return;

    const params = new URLSearchParams(window.location.search);
    params.set("export", "excel");
    params.set("selected", selected.join(","));
    const transactionsRoute =
        document
            .querySelector('meta[name="transactions-route"]')
            ?.getAttribute("content") || "/admin/transactions";
    window.location.href = transactionsRoute + "?" + params.toString();
}
