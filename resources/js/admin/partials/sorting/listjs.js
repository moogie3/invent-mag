export function initListJs() {
    const invoiceTableContainer = document.getElementById("invoiceTableContainer");
    const tableBody = invoiceTableContainer ? invoiceTableContainer.querySelector(".table-tbody") : null;

    if (tableBody && tableBody.children.length > 0) {
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
                "sort-expiry",
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
    }
}
