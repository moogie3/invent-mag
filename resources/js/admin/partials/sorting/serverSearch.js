import { initListJs } from "./listjs.js";

/**
 * Shared server-side search for every paginated admin list.
 *
 * Why this exists
 * ---------------
 * The old helper filtered the rows already present in the DOM. On a
 * server-paginated table that is structurally incapable of a whole-catalog
 * search: the browser only ever holds one page of rows, so a term that matches
 * 56 products displays however many of those 56 happened to be on page 1 —
 * silently, with no error, and with a plausible-looking result. This module
 * moves the predicate to the database, where the whole catalog lives.
 *
 * How a page opts in
 * ------------------
 * Put `data-server-search` on `#searchInput`, and mark the pagination footer
 * with `data-pagination-footer`. `partials/sorting/search.js` sees the
 * attribute and returns early, so the client-side row filter and this module
 * can never both drive the same table. That mutual exclusion is the whole
 * safety story — it is not a convention, it is enforced by the guard.
 *
 * What it does
 * ------------
 * - 300 ms debounce plus an AbortController, so a fast typist produces exactly
 *   one in-flight request and an out-of-order response can never land.
 * - Re-fetches the SAME index URL with `?search=...`. No second endpoint, so
 *   the search query and the list query cannot drift apart, and every existing
 *   filter, tenant scope and eager load applies automatically.
 * - Swaps only `#invoiceTableContainer .table-responsive` and the pagination
 *   footer — no full reload, no layout repaint, no lost scroll position.
 * - `history.replaceState` keeps the URL in sync, so refresh / bookmark /
 *   back / "send this view to a colleague" all keep the search.
 * - Re-runs `initListJs()` afterwards. List.js binds sort handlers to the
 *   `<button class="table-sort">` elements inside `<thead>`, which live INSIDE
 *   the swapped region — without this, sorting silently dies after the first
 *   search.
 * - Dispatches a `listsearch:updated` CustomEvent so a page can re-initialize
 *   whatever per-row state it owns (bulk selection, keyboard shortcuts).
 */
export function initServerSearch() {
    const input = document.getElementById("searchInput");
    if (!input || !input.hasAttribute("data-server-search")) return;
    if (input.dataset.serverSearchAttached) return;
    input.dataset.serverSearchAttached = "true";

    const tableContainer = document.getElementById("invoiceTableContainer");
    if (!tableContainer) return;

    const paginationFooter = document.querySelector("[data-pagination-footer]");

    let timer = null;
    let controller = null;

    // After a paginated reload (e.g. clicking page 2 of a search) the results
    // are already filtered but the input renders empty. Restore it from the
    // URL so the box always agrees with the table.
    const initial = new URL(window.location.href).searchParams.get("search");
    if (initial && !input.value) input.value = initial;

    // The dimming wash is the page surface at 55%, not a literal white.
    // This node is built in JS, so no stylesheet override can reach it — a
    // hardcoded rgba(255,255,255,.55) flashed a white panel over the table in
    // dark mode on every one of the pages that opt in. Tabler publishes the
    // surface as an RGB triplet (--tblr-body-bg-rgb) per theme, which is what
    // makes rgba() accept a var() here; 246,248,251 light, 4,10,17 dark.
    const overlay = document.createElement("div");
    overlay.style.cssText =
        "position:absolute;inset:0;display:none;align-items:center;justify-content:center;background:rgba(var(--tblr-body-bg-rgb),.55);z-index:2;";
    overlay.innerHTML =
        '<span class="spinner-border spinner-border-sm text-primary" role="status"></span>';
    tableContainer.style.position = "relative";
    tableContainer.appendChild(overlay);

    function buildUrl(term) {
        const url = new URL(window.location.href);
        if (term) {
            url.searchParams.set("search", term);
        } else {
            url.searchParams.delete("search");
        }
        url.searchParams.delete("page"); // a new term always starts at page 1
        return url;
    }

    function showOverlay(show) {
        overlay.style.display = show ? "flex" : "none";
        // A container-level swap would detach the overlay — re-attach it.
        if (show && overlay.parentElement !== tableContainer) {
            tableContainer.appendChild(overlay);
        }
    }

    /**
     * Replace the table region and pagination footer from a parsed document.
     * Only the inner HTML of `.table-responsive` is touched so the overlay
     * node (and any other container-level state) survives.
     */
    function swapFrom(doc) {
        const nextContainer = doc.getElementById("invoiceTableContainer");
        if (!nextContainer) throw new Error("Unexpected response");

        const nextInner = nextContainer.querySelector(".table-responsive");
        const currentInner = tableContainer.querySelector(".table-responsive");
        if (nextInner && currentInner) {
            currentInner.innerHTML = nextInner.innerHTML;
        } else {
            tableContainer.innerHTML = nextContainer.innerHTML;
        }

        if (paginationFooter) {
            const nextFooter = doc.querySelector("[data-pagination-footer]");
            if (nextFooter) {
                paginationFooter.innerHTML = nextFooter.innerHTML;
            }
        }
    }

    /** Re-bind everything that was captured by the old markup. */
    function afterSwap(term) {
        // swapFrom() replaced .table-responsive's inner HTML, so the old
        // header buttons (and List.js's click handlers on them) are gone.
        // The guard flag lives on the container, which survives the swap --
        // clear it or the re-init below would be skipped and sorting would
        // stop working after the first search.
        delete tableContainer.dataset.listjsBound;
        initListJs();
        // Dispatch on the table container, not `document`. The container lives
        // inside the page body, so a listener attached to it is scoped to the
        // current page and dies with it on navigation — whereas a document
        // listener survives Turbo navigations and would keep firing a
        // previous page's re-init against the new page's DOM.
        tableContainer.dispatchEvent(
            new CustomEvent("listsearch:updated", {
                detail: { search: term },
                bubbles: true,
            }),
        );
    }

    function run(term) {
        if (controller) controller.abort();
        controller = new AbortController();
        showOverlay(true);

        fetch(buildUrl(term).toString(), {
            headers: {
                "X-Requested-With": "XMLHttpRequest",
                Accept: "text/html",
            },
            signal: controller.signal,
        })
            .then((response) => {
                if (!response.ok) throw new Error(String(response.status));
                return response.text();
            })
            .then((html) => {
                const doc = new DOMParser().parseFromString(html, "text/html");
                swapFrom(doc);

                window.history.replaceState(null, "", buildUrl(term).toString());

                // List.js re-init is synchronous; per-page state may rely on
                // layout, so give it a frame first.
                setTimeout(() => afterSwap(term), 0);
            })
            .catch((error) => {
                // An aborted request is the user typing again — not an error.
                if (error && error.name === "AbortError") return;
                console.error("Server search failed:", error);
            })
            .finally(() => {
                showOverlay(false);
            });
    }

    input.addEventListener("input", () => {
        clearTimeout(timer);
        const term = input.value.trim();
        timer = setTimeout(() => run(term), 300);
    });
}
