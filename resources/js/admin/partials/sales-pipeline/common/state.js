export let allPipelines = [];
export let allCustomers = [];
export let allProducts = [];

export let currencySettings = {
    symbol: "$",
    decimalPlaces: 2,
    decimalSeparator: ".",
    thousandSeparator: ",",
};

export function setAllPipelines(pipelines) {
    allPipelines = pipelines;
}

export function setAllCustomers(customers) {
    allCustomers = customers;
}

export function setAllProducts(products) {
    allProducts = products;
}

export function setCurrencySettings(settings) {
    currencySettings = { ...currencySettings, ...settings };
}
