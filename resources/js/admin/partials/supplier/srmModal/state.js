export let srmState = {
    currentPage: 1,
    supplierId: null,
    lastPage: 1,
};

export let currencySettings = {};

export function setSrmState(newState) {
    srmState = { ...srmState, ...newState };
}

export function setCurrencySettings(settings) {
    currencySettings = { ...currencySettings, ...settings };
}
