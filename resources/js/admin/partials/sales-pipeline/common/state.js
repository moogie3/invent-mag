export let allPipelines = [];
export let allCustomers = [];
export let allProducts = [];

// Currency settings are now globally available via window.currencySettings

export function setAllPipelines(pipelines) {
    allPipelines = pipelines;
}

export function setAllCustomers(customers) {
    allCustomers = customers;
}

export function setAllProducts(products) {
    allProducts = products;
}


