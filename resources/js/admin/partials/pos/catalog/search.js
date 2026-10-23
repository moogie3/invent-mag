import { formatCurrency } from "@/js/utils/currencyFormatter.js";
import { getStockBadgeClass } from "../utils/ui.js";

const DEBOUNCE_MS = 250;
const NEAR_BOTTOM_PX = 200;

function buildEmptyState(grid) {
    const empty = document.createElement("div");
    empty.className = "empty";

    const img = document.createElement("div");
    img.className = "empty-img";
    const icon = document.createElement("i");
    icon.className = "ti ti-mood-sad";
    icon.style.fontSize = "5rem";
    icon.style.color = "#ccc";
    img.appendChild(icon);

    const title = document.createElement("p");
    title.className = "empty-title";
    title.textContent = grid.dataset.emptyTitle || "";

    const subtitle = document.createElement("p");
    subtitle.className = "empty-subtitle text-muted";
    subtitle.textContent = grid.dataset.emptySubtitle || "";

    empty.append(img, title, subtitle);

    return empty;
}

function buildProductCol(product, grid) {
    const col = document.createElement("div");
    col.className = "col-md-4 mb-2";

    const card = document.createElement("div");
    card.className = "card product-card border-0 shadow-sm rounded-3 hover-shadow";
    card.style.cursor = "pointer";
    card.setAttribute("data-product-id", product.id);
    card.setAttribute("data-product-name", product.name);
    card.setAttribute("data-product-price", product.selling_price);
    card.setAttribute("data-product-unit", product.unit_symbol || "pcs");
    card.setAttribute("data-product-stock", product.main_warehouse_stock ?? 0);

    const imageContainer = document.createElement("div");
    imageContainer.className =
        "card-img-top position-relative product-image-container d-flex align-items-center justify-content-center";
    imageContainer.style.height = "150px";

    if (product.is_placeholder) {
        const icon = document.createElement("i");
        icon.className = "ti ti-photo fs-1";
        icon.style.cssText =
            "width:100%;height:100%;display:flex;align-items:center;justify-content:center;border:1px solid #ccc;border-radius:5px;margin:0 auto;";
        imageContainer.appendChild(icon);
    } else {
        const img = document.createElement("img");
        img.src = product.image;
        img.alt = product.name;
        img.className = "img-fluid";
        img.setAttribute("loading", "lazy");
        img.style.cssText =
            "width:100%;height:100%;object-fit:cover;border-radius:4px 4px 0 0;";
        imageContainer.appendChild(img);
    }

    const body = document.createElement("div");
    body.className = "card-body p-2 text-center";

    const title = document.createElement("h5");
    title.className = "card-title fs-4 mb-1 product-card-title";
    title.textContent = product.name;

    const price = document.createElement("p");
    price.className = "card-text fs-4 mb-1";
    price.textContent = formatCurrency(product.selling_price);

    const stock = product.main_warehouse_stock ?? 0;
    const stockLine = document.createElement("p");
    stockLine.className = "card-text fs-5 text-muted";
    stockLine.append(`${grid.dataset.inStockLabel || "In Stock"}: `);
    const stockBadge = document.createElement("span");
    stockBadge.className = `product-stock-display badge text-light ${getStockBadgeClass(stock)}`;
    stockBadge.textContent = stock;
    stockLine.appendChild(stockBadge);

    body.append(title, price, stockLine);
    card.append(imageContainer, body);
    col.appendChild(card);

    return col;
}

function renderProducts(grid, products, { append }) {
    if (!append) {
        grid.innerHTML = "";
        if (products.length === 0) {
            grid.appendChild(buildEmptyState(grid));

            return;
        }
    }

    const fragment = document.createDocumentFragment();
    products.forEach((product) => fragment.appendChild(buildProductCol(product, grid)));
    grid.appendChild(fragment);
}

/**
 * Server-side catalog search + infinite scroll for the POS product grid. The
 * page ships only the first page of cards (PosService::CATALOG_PAGE_SIZE);
 * typing queries admin.pos.products instead of filtering the DOM, and
 * scrolling the bounded wrapper auto-loads the next page, so 1000+ item
 * catalogs never render (or scroll) as one giant page.
 */
export function initCatalogSearch() {
    const searchInput = document.getElementById("searchProduct");
    const grid = document.getElementById("productGrid");

    if (!searchInput || !grid) return;
    if (searchInput.dataset.handlerAttached) return;
    searchInput.dataset.handlerAttached = "true";

    const searchUrl = grid.dataset.searchUrl;
    const scroller = grid.closest(".pos-catalog-scroll");
    if (!searchUrl || !scroller) return;

    let requestId = 0;
    let nextOffset = grid.querySelectorAll(".product-card").length;
    let debounceTimer = null;
    let hasMore = grid.dataset.hasMore === "1";
    let isLoading = false;

    const loadingRow = document.createElement("div");
    loadingRow.className = "col-12 text-center py-2";
    loadingRow.innerHTML =
        '<div class="spinner-border spinner-border-sm" role="status"></div>';

    const retryButton = document.createElement("button");
    retryButton.type = "button";
    retryButton.className = "col-12 btn btn-outline-primary w-100 mt-2";
    retryButton.textContent = grid.dataset.loadMoreLabel || "Load more";
    retryButton.addEventListener("click", () => fetchPage({ append: true }));

    function setHasMore(value) {
        hasMore = value;
        grid.dataset.hasMore = value ? "1" : "0";
    }

    function clearFooter() {
        loadingRow.remove();
        retryButton.remove();
    }

    // If the wrapper still under-fills its viewport (e.g. a search that
    // returns a handful of cards), top up without waiting for a scroll event.
    function maybeAutoLoad() {
        if (!hasMore || isLoading) return;
        if (scroller.clientHeight > 0 && scroller.scrollHeight <= scroller.clientHeight) {
            fetchPage({ append: true });
        }
    }

    async function fetchPage({ append = false } = {}) {
        if (append && (isLoading || !hasMore)) return;
        const id = ++requestId;
        const query = searchInput.value.trim();
        const offset = append ? nextOffset : 0;

        if (append) {
            isLoading = true;
            retryButton.remove();
            grid.appendChild(loadingRow);
        }

        try {
            const response = await fetch(
                `${searchUrl}?q=${encodeURIComponent(query)}&offset=${offset}`,
                { headers: { Accept: "application/json" } },
            );
            if (!response.ok) throw new Error(`HTTP ${response.status}`);
            const data = await response.json();
            if (id !== requestId) return; // superseded by a newer search

            const products = data.products || [];
            if (append) {
                nextOffset += products.length;
            } else {
                nextOffset = products.length;
            }
            setHasMore(Boolean(data.has_more));

            clearFooter();
            if (!append) scroller.scrollTop = 0;
            renderProducts(grid, products, { append });
            maybeAutoLoad();
        } catch (error) {
            // Keep whatever is currently rendered; offer a retry on append.
            console.error("POS product search failed:", error);
            if (append && id === requestId) {
                loadingRow.remove();
                grid.appendChild(retryButton);
            }
        } finally {
            if (append) isLoading = false;
        }
    }

    searchInput.addEventListener("input", function () {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => fetchPage({ append: false }), DEBOUNCE_MS);
    });

    if (!scroller.dataset.handlerAttached) {
        scroller.dataset.handlerAttached = "true";
        scroller.addEventListener(
            "scroll",
            () => {
                if (
                    scroller.scrollTop + scroller.clientHeight >=
                    scroller.scrollHeight - NEAR_BOTTOM_PX
                ) {
                    fetchPage({ append: true });
                }
            },
            { passive: true },
        );
    }
}
