export function searchTransactions() {
    const searchInput = document.getElementById("searchInput");
    if (!searchInput) return;
    const searchValue = searchInput.value;
    const url = new URL(window.location);
    if (searchValue) {
        url.searchParams.set("search", searchValue);
    } else {
        url.searchParams.delete("search");
    }
    window.location.href = url.toString();
}
