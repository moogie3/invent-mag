import { searchTimeout, currentRequest, isSearchActive, setCurrentRequest, setIsSearchActive } from './state.js';
import { storeOriginalTable, restoreOriginalTable } from './table.js';
import { renderSearchResults, showNoResults, showSearchError } from './ui.js';

export function initializeSearch() {
    const searchInput = document.getElementById("searchInput");
    if (!searchInput) return;

    storeOriginalTable();

    searchInput.addEventListener("input", function () {
        clearTimeout(searchTimeout);
        if (currentRequest) {
            currentRequest.abort();
            setCurrentRequest(null);
        }

        const query = this.value.trim();

        const newSearchTimeout = setTimeout(() => {
            if (query.length === 0) {
                if (isSearchActive) {
                    restoreOriginalTable();
                }
                setIsSearchActive(false);
            } else {
                performSearch(query);
                setIsSearchActive(true);
            }
        }, 500);
        setCurrentRequest(newSearchTimeout);
    });
}

function performSearch(query) {
    storeOriginalTable();

    const tableBody = document.querySelector("table tbody");

    if (!query) {
        restoreOriginalTable();
        return;
    }

    tableBody.innerHTML = `
        <tr><td colspan="100%" class="text-center py-5">
            <div class="spinner-border text-primary"></div>
            <p class="mt-3 text-muted">Searching...</p>
        </td></tr>
    `;

    const controller = new AbortController();
    setCurrentRequest(controller);

    fetch(`/admin/product/search?q=${encodeURIComponent(query)}`, {
        signal: controller.signal,
        headers: {
            Accept: "application/json",
            "X-Requested-With": "XMLHttpRequest",
        },
    })
        .then((response) => response.json())
        .then((data) => {
            setCurrentRequest(null);
            if (data.success) {
                renderSearchResults(data.products);
            } else {
                showNoResults(data.message);
            }
        })
        .catch((error) => {
            setCurrentRequest(null);
            if (error.name !== "AbortError") {
                showSearchError(error.message);
            }
        });
}
