export function initSearch() {
    const searchInput = document.getElementById("searchInput");
    const tableRows = document.querySelectorAll("#invoiceTableBody tr");

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
