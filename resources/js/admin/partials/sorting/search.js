export function initSearch() {
    const searchInput = document.getElementById("searchInput");
    if (!searchInput) return;
    
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
