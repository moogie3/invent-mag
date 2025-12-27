export let searchTimeout;
export let currentRequest = null;
export let isSearchActive = false;
export let originalTableContent = null;
export let originalWarehouseData = new Map();

export function setCurrentRequest(request) {
    currentRequest = request;
}

export function setIsSearchActive(isActive) {
    isSearchActive = isActive;
}

export function setOriginalTableContent(content) {
    originalTableContent = content;
}
