export let products = [];

export function getProducts() {
    // Return a shallow copy to prevent direct mutation of the state array
    return [...products];
}

export function setProducts(newProducts) {
    // Store a shallow copy to ensure the state is not the same instance as the input
    products = [...newProducts];
}

export function saveProductsToCache() {
    localStorage.setItem("cachedProducts", JSON.stringify(products));
}

export function loadProductsFromCache() {
    const cachedProducts = localStorage.getItem("cachedProducts");
    if (cachedProducts) {
        try {
            // Use the setProducts function to ensure a copy is stored
            setProducts(JSON.parse(cachedProducts));
        } catch (e) {
            console.error("Failed to parse cached products from localStorage:", e);
            // If parsing fails, reset to a clean state
            setProducts([]);
        }
    }
}
