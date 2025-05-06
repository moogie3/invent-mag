document.addEventListener("DOMContentLoaded", function () {
    // Initialize List.js
    const list = new List("invoiceTableContainer", {
        sortClass: "table-sort",
        listClass: "table-tbody",
        valueNames: [
            "sort-no",
            "sort-invoice",
            "sort-date",
            "sort-total",
            "sort-supplier",
            "sort-orderdate",
            "sort-quantity",
            "sort-name",
            "sort-description",
            "sort-phonenumber",
            "sort-code",
            "sort-address",
            "sort-location",
            "sort-paymentterms",
            "sort-category",
            "sort-price",
            "sort-sellingprice",
            "sort-unit",
            {
                name: "sort-duedate",
                attr: "data-date",
            },
            {
                name: "sort-amount",
                attr: "data-amount",
            },
            "sort-amount",
            "sort-payment",
            "sort-status",
        ],
    });

    // Enhanced search for formatted and raw amounts
    const searchInput = document.getElementById("searchInput");
    const tableRows = document.querySelectorAll("#invoiceTableBody tr");

    searchInput.addEventListener("keyup", function () {
        const searchTerm = searchInput.value.toLowerCase();

        tableRows.forEach((row) => {
            const text = row.textContent.toLowerCase();
            const rawAmount =
                row.querySelector(".raw-amount")?.textContent.toLowerCase() ||
                "";

            // Match either formatted text OR raw amount
            row.style.display =
                text.includes(searchTerm) || rawAmount.includes(searchTerm)
                    ? ""
                    : "none";
        });
    });
});
