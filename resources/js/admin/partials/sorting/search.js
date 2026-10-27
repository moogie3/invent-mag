export function initSearch() {
    const searchInput = document.getElementById("searchInput");
    if (!searchInput) return;

    // Inputs marked with data-server-search query the backend (see the page's
    // own serverSearch module) — client-side row filtering here would only see
    // the rows of the current page and silently hide matches on other pages.
    if (searchInput.hasAttribute("data-server-search")) return;

    const tableRows = document.querySelectorAll("#invoiceTableBody tr");
    if (!tableRows || tableRows.length === 0) return;

    searchInput.addEventListener("keyup", function () {
        const searchTerm = searchInput.value.toLowerCase();

        tableRows.forEach((row) => {
            const text = row.textContent.toLowerCase();
            const rawAmount =
                row.querySelector(".raw-amount")?.textContent.toLowerCase() ||
                "";

            row.style.display =
                text.includes(searchTerm) || rawAmount.includes(searchTerm)
                    ? ""
                    : "none";
        });
    });
}
