export function initListJs() {
    const invoiceTableContainer = document.getElementById("invoiceTableContainer");
    if (!invoiceTableContainer) return;

    // One List instance per generation of markup. This can be reached twice
    // before any swap (onPageLoad has no `id`, so it runs on module execution
    // and again on `turbo:load`), and List.js binds a fresh click handler per
    // instance. Two handlers on the same header run sort() back to back: the
    // first reads "no order" and sorts asc, the second reads "asc" and sorts
    // desc -- so the button always ends on `desc` and clicking it again is a
    // no-op. The race is timing-dependent, which is why it only bit some pages.
    if (invoiceTableContainer.dataset.listjsBound === "true") return;

    const tableBody = invoiceTableContainer.querySelector(".table-tbody");
    if (!tableBody || tableBody.children.length === 0) return;

    try {
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
                "sort-payment",
                "sort-status",
                // Columns that headers already reference via data-sort but that
                // were missing here: List.js indexes only the valueNames listed
                // below, so an absent entry silently made that column unsortable.
                "sort-customer",
                "sort-warehouse",
                "sort-tax",
                "sort-ppn",
                "sort-pph",
                {
                    name: "sort-quotationdate",
                    attr: "data-date",
                },
                {
                    name: "sort-validuntil",
                    attr: "data-date",
                },
                // Second sweep: the remaining data-sort values headers declare
                // but that were still unregistered (HR, Users, Customer,
                // Supplier, Tax). Each has a matching sort-* class on its <td>.
                "sort-closed",
                "sort-dept",
                "sort-email",
                "sort-is-main",
                "sort-paid-date",
                "sort-period",
                "sort-permissions",
                "sort-position",
                "sort-rate",
                "sort-roles",
                "sort-run-date",
                "sort-type",
                // Money reads the raw number off data-amount, never the
                // formatted "Rp 1.500.000" text: natural-sorting that string
                // ranks 1.5M below 999K because it compares the digit run "1"
                // against "999".
                {
                    name: "sort-credits",
                    attr: "data-amount",
                },
                // Payroll runs render their period as "October 2026", which
                // alphabetises by month name. Kept separate from sort-period
                // because attendance renders the same column as "2026-10",
                // which already sorts correctly as plain text.
                {
                    name: "sort-runperiod",
                    attr: "data-date",
                },
            ],
        });
        invoiceTableContainer.dataset.listjsBound = "true";
    } catch (e) {
        // Ignore List.js initialization errors
    }
}
