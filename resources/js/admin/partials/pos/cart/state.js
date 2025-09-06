export let products = [];

export function getProducts() {
    return products;
}

export function setProducts(newProducts) {
    products = newProducts;
}

export function saveProductsToCache() {
    localStorage.setItem("cachedProducts", JSON.stringify(products));
}

export function loadProductsFromCache() {
    const cachedProducts = localStorage.getItem("cachedProducts");
    if (cachedProducts) {
        products = JSON.parse(cachedProducts);
    }
}
