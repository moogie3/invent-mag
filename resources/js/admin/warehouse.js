document.addEventListener("DOMContentLoaded", function () {
    const editWarehouseModal = document.getElementById("editWarehouseModal");
    const createWarehouseModal = document.getElementById("createWarehouseModal");

    // Function to handle form submission via AJAX
    function handleFormSubmission(event, modalElement, isCreate = false) {
        event.preventDefault();

        const form = event.target;
        const formData = new FormData(form);
        const actionUrl = form.action;
        const method = form.method;

        fetch(actionUrl, {
            method: method === 'GET' ? 'GET' : 'POST', // Ensure POST for PUT/DELETE via _method
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            },
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast('Success', data.message, 'success');
                const bsModal = bootstrap.Modal.getInstance(modalElement);
                if (bsModal) {
                    bsModal._element.addEventListener('hidden.bs.modal', function handler() {
                        bsModal._element.removeEventListener('hidden.bs.modal', handler);
                        form.reset(); // Clear form fields
                        // Explicitly remove any remaining modal backdrops
                        const backdrops = document.querySelectorAll('.modal-backdrop');
                        backdrops.forEach(backdrop => backdrop.remove());
                        location.reload();
                    });
                    bsModal.hide();
                } else {
                    form.reset();
                    location.reload();
                }
            } else {
                showToast('Error', data.message || 'Operation failed.', 'error');
                console.error('Form submission error:', data.errors);
            }
        })
        .catch(error => {
            console.error('Error during fetch:', error);
            showToast('Error', 'An error occurred. Please check the console.', 'error');
        });
    }

    // Event listener for edit modal show
    if (editWarehouseModal) {
        editWarehouseModal.addEventListener("show.bs.modal", function (event) {
            const button = event.relatedTarget;

            const warehouseId = button.getAttribute("data-id");
            const warehouseName = button.getAttribute("data-name");
            const warehouseAddress = button.getAttribute("data-address");
            const warehouseDescription = button.getAttribute("data-description");

            document.getElementById("warehouseId").value = warehouseId;
            document.getElementById("warehouseNameEdit").value = warehouseName;
            document.getElementById("warehouseAddressEdit").value = warehouseAddress;
            document.getElementById("warehouseDescriptionEdit").value = warehouseDescription;

            document.getElementById("editWarehouseForm").action = "{{ route('admin.warehouse.update', '') }}/" + warehouseId;
        });

        // Add submit listener for edit form
        const editWarehouseForm = document.getElementById("editWarehouseForm");
        if (editWarehouseForm) {
            editWarehouseForm.addEventListener("submit", (event) => handleFormSubmission(event, editWarehouseModal));
        }
    }

    // Add submit listener for create form
    const createWarehouseForm = document.getElementById("createWarehouseForm");
    if (createWarehouseForm) {
        createWarehouseForm.addEventListener("submit", (event) => handleFormSubmission(event, createWarehouseModal, true));
    }

    // --- Start Search Functionality (Adapted from product.js) ---
    let searchTimeout;
    let currentRequest = null;
    let isSearchActive = false;
    let originalTableContent = null;
    let originalWarehouseData = new Map(); // Changed from originalProductData

    function initializeSearch() {
        const searchInput = document.getElementById("searchInput");
        if (!searchInput) return;

        storeOriginalTable();

        searchInput.addEventListener("input", function () {
            clearTimeout(searchTimeout);
            if (currentRequest) {
                currentRequest.abort();
                currentRequest = null;
            }

            const query = this.value.trim();

            searchTimeout = setTimeout(() => {
                if (query.length === 0) {
                    if (isSearchActive) {
                        restoreOriginalTable();
                    }
                    isSearchActive = false;
                } else {
                    performSearch(query);
                    isSearchActive = true;
                }
            }, 500);
        });
    }

    function storeOriginalTable() {
        if (!originalTableContent) {
            const tableBody = document.querySelector("table tbody");
            if (tableBody) {
                originalTableContent = tableBody.innerHTML;

                const rows = tableBody.querySelectorAll("tr[data-id]");
                rows.forEach((row) => {
                    const warehouseId = row.dataset.id;
                    const warehouseData = extractWarehouseDataFromRow(row); // Changed function name
                    if (warehouseData) {
                        originalWarehouseData.set(warehouseId, warehouseData); // Changed map name
                    }
                });
            }
        }
    }

    function extractWarehouseDataFromRow(row) { // Changed function name
        try {
            const nameElement = row.querySelector(".sort-name");
            const addressElement = row.querySelector(".sort-address");
            const descriptionElement = row.querySelector(".sort-description");
            const isMainElement = row.querySelector(".sort-is-main");

            if (!nameElement) return null;

            return {
                id: parseInt(row.dataset.id),
                name: nameElement.textContent.trim(),
                address: addressElement?.textContent?.trim() || "N/A",
                description: descriptionElement?.textContent?.trim() || "N/A",
                is_main: isMainElement?.textContent?.trim() === "Main",
            };
        } catch (error) {
            console.error("Error extracting warehouse data:", error);
            return null;
        }
    }

    function restoreOriginalTable() {
        if (originalTableContent) {
            const tableBody = document.querySelector("table tbody");
            if (tableBody) {
                tableBody.innerHTML = originalTableContent;
                // Reinitialize bulk selection and restore states if applicable
                // For now, warehouse doesn't have bulk selection, but keep this pattern
                // setTimeout(() => {
                //     initBulkSelection(); // If warehouse ever gets bulk selection
                //     restoreCheckboxStates();
                //     updateBulkActionsBarVisibility();
                // }, 100);
            }
        }
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
        currentRequest = controller;

        // !!! IMPORTANT: This URL needs to be implemented on the backend !!!
        fetch(`/admin/warehouse/search?q=${encodeURIComponent(query)}`, {
            signal: controller.signal,
            headers: {
                Accept: "application/json",
                "X-Requested-With": "XMLHttpRequest",
            },
        })
            .then((response) => response.json())
            .then((data) => {
                currentRequest = null;
                if (data.success) {
                    renderSearchResults(data.warehouses); // Changed from data.products
                } else {
                    showNoResults(data.message);
                }
            })
            .catch((error) => {
                currentRequest = null;
                if (error.name !== "AbortError") {
                    showSearchError(error.message);
                }
            });
    }

    function renderSearchResults(warehouses) { // Changed parameter name
        const tableBody = document.querySelector("table tbody");
        if (!warehouses.length) {
            showNoResults();
            return;
        }

        // Store search results in originalWarehouseData for future use (e.g., bulk operations)
        warehouses.forEach((warehouse) => {
            originalWarehouseData.set(warehouse.id.toString(), warehouse);
        });

        const html = warehouses
            .map((warehouse, index) => {
                // Assuming warehouse object has id, name, address, description, is_main
                return `
                <tr data-id="${warehouse.id}">
                    <td class="sort-no no-print">${index + 1}</td>
                    <td class="sort-name">${warehouse.name}</td>
                    <td class="sort-address">${warehouse.address}</td>
                    <td class="sort-description">${warehouse.description}</td>
                    <td class="sort-is-main">
                        ${warehouse.is_main ? '<span class="badge bg-green-lt">Main</span>' : '<span class="badge bg-secondary-lt">Sub</span>'}
                    </td>
                    <td class="no-print" style="text-align:center">
                        <div class="dropdown">
                            <button class="btn dropdown-toggle align-text-top"
                                data-bs-toggle="dropdown" data-bs-boundary="viewport">
                                Actions
                            </button>
                            <div class="dropdown-menu">
                                <a href="#" class="dropdown-item"
                                    data-bs-toggle="modal"
                                    data-bs-target="#editWarehouseModal"
                                    data-id="${warehouse.id}"
                                    data-name="${warehouse.name}"
                                    data-address="${warehouse.address}"
                                    data-description="${warehouse.description}"
                                    data-is_main="${warehouse.is_main}">
                                    <i class="ti ti-edit me-2"></i> Edit
                                </a>
                                <button type="button" class="dropdown-item text-danger"
                                    data-bs-toggle="modal" data-bs-target="#deleteModal"
                                    onclick="setDeleteFormAction('/admin/warehouse/destroy/${warehouse.id}')" ${warehouse.is_main ? 'disabled' : ''}>
                                    <i class="ti ti-trash me-2"></i> Delete
                                </button>
                            </div>
                        </div>
                    </td>
                </tr>
            `;
            })
            .join("");

        tableBody.innerHTML = html;

        // Reinitialize bulk selection if applicable (warehouse doesn't have it yet)
        // setTimeout(() => {
        //     initBulkSelection();
        //     restoreCheckboxStates();
        //     updateBulkActionsBarVisibility();
        // }, 100);
    }

    function showNoResults(message = "No warehouses found matching your search.") {
        document.querySelector("table tbody").innerHTML = `
            <tr><td colspan="100%" class="text-center py-5">
                <i class="ti ti-search-off fs-1 text-muted"></i>
                <p class="mt-3 text-muted">${message}</p>
            </td></tr>
        `;
    }

    function showSearchError(errorMessage = "Search error occurred.") {
        document.querySelector("table tbody").innerHTML = `
            <tr><td colspan="100%" class="text-center py-5">
                <i class="ti ti-alert-circle fs-1 text-danger"></i>
                <p class="mt-3 text-danger">${errorMessage}</p>
                <button class="btn btn-outline-primary mt-2" onclick="window.location.reload()">
                    <i class="ti ti-refresh me-2"></i> Refresh
                </button>
            </td></tr>
        `;
    }
    // --- End Search Functionality ---

    // Call initializeSearch when the DOM is ready
    initializeSearch();
});