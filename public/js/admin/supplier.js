document.addEventListener("DOMContentLoaded", function () {
    const editSupplierModal = document.getElementById("editSupplierModal");
    const createSupplierModal = document.getElementById("createSupplierModal");

    // Store selected checkbox states globally for suppliers
    let selectedSupplierIds = new Set();

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
    if (editSupplierModal) {
        editSupplierModal.addEventListener("show.bs.modal", function (event) {
            const button = event.relatedTarget;

            if (!button) return; // Prevent errors if button is null

            const supplierId = button.getAttribute("data-id") || "";
            const supplierCode = button.getAttribute("data-code") || "";
            const supplierName = button.getAttribute("data-name") || "";
            const supplierAddress = button.getAttribute("data-address") || "";
            const supplierPhone = button.getAttribute("data-phone_number") || "";
            const supplierLocation = button.getAttribute("data-location") || "";
            const supplierPayment = button.getAttribute("data-payment_terms") || "";

            document.getElementById("supplierId").value = supplierId;
            if (document.getElementById("supplierCodeEdit")) {
                document.getElementById("supplierCodeEdit").value = supplierCode;
            }
            document.getElementById("supplierNameEdit").value = supplierName;
            document.getElementById("supplierAddressEdit").value = supplierAddress;
            document.getElementById("supplierPhoneEdit").value = supplierPhone;
            document.getElementById("supplierLocationEdit").value = supplierLocation;
            document.getElementById("supplierPaymentTermsEdit").value = supplierPayment;

            const routeBase = document.getElementById("updateRouteBase").value;
            document.getElementById("editSupplierForm").action = routeBase + "/" + supplierId;
        });

        // Add submit listener for edit form
        const editSupplierForm = document.getElementById("editSupplierForm");
        if (editSupplierForm) {
            editSupplierForm.addEventListener("submit", (event) => handleFormSubmission(event, editSupplierModal));
        }
    }

    // Add submit listener for create form
    const createSupplierForm = document.getElementById("createSupplierForm");
    if (createSupplierForm) {
        createSupplierForm.addEventListener("submit", (event) => handleFormSubmission(event, createSupplierModal, true));
    }

    // Toast notification functions (copied from user.js)
    // Function to handle form submission via AJAX
    let searchTimeout;
    let currentRequest = null;
    let isSearchActive = false;
    let originalTableContent = null;
    let originalSupplierData = new Map(); // Changed from originalProductData

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
                    const supplierId = row.dataset.id;
                    const supplierData = extractSupplierDataFromRow(row); // Changed function name
                    if (supplierData) {
                        originalSupplierData.set(supplierId, supplierData); // Changed map name
                    }
                });
            }
        }
    }

    function extractSupplierDataFromRow(row) { // Changed function name
        try {
            const codeElement = row.querySelector(".sort-code");
            const nameElement = row.querySelector(".sort-name");
            const addressElement = row.querySelector(".sort-address");
            const locationElement = row.querySelector(".sort-location");
            const paymentTermsElement = row.querySelector(".sort-paymentterms");

            if (!nameElement) return null;

            return {
                id: parseInt(row.dataset.id),
                code: codeElement?.textContent?.trim() || "N/A",
                name: nameElement.textContent.trim(),
                address: addressElement?.textContent?.trim() || "N/A",
                location: locationElement?.textContent?.trim() || "N/A",
                payment_terms: paymentTermsElement?.textContent?.trim() || "N/A",
            };
        } catch (error) {
            console.error("Error extracting supplier data:", error);
            return null;
        }
    }

    function restoreOriginalTable() {
        if (originalTableContent) {
            const tableBody = document.querySelector("table tbody");
            if (tableBody) {
                tableBody.innerHTML = originalTableContent;
                // Reinitialize bulk selection and restore states if applicable
                // For now, supplier doesn't have bulk selection, but keep this pattern
                // setTimeout(() => {
                //     initBulkSelection(); // If supplier ever gets bulk selection
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
        fetch(`/admin/supplier/search?q=${encodeURIComponent(query)}`, {
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
                    renderSearchResults(data.suppliers); // Changed from data.products
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

    function renderSearchResults(suppliers) { // Changed parameter name
        const tableBody = document.querySelector("table tbody");
        if (!suppliers.length) {
            showNoResults();
            return;
        }

        // Store search results in originalSupplierData for future use (e.g., bulk operations)
        suppliers.forEach((supplier) => {
            originalSupplierData.set(supplier.id.toString(), supplier);
        });

        const html = suppliers
            .map((supplier, index) => {
                // Assuming supplier object has id, code, name, address, location, payment_terms
                return `
                <tr data-id="${supplier.id}">
                    <td class="sort-no no-print">${index + 1}</td>
                    <td class="sort-code">${supplier.code}</td>
                    <td class="sort-name">${supplier.name}</td>
                    <td class="sort-address">${supplier.address}</td>
                    <td class="sort-location">${supplier.location}</td>
                    <td class="sort-paymentterms">${supplier.payment_terms}</td>
                    <td class="no-print" style="text-align:center">
                        <div class="dropdown">
                            <button class="btn dropdown-toggle align-text-top"
                                data-bs-toggle="dropdown" data-bs-boundary="viewport">
                                Actions
                            </button>
                            <div class="dropdown-menu">
                                <a href="#" class="dropdown-item"
                                    data-bs-toggle="modal"
                                    data-bs-target="#editSupplierModal"
                                    data-id="${supplier.id}"
                                    data-code="${supplier.code}"
                                    data-name="${supplier.name}"
                                    data-address="${supplier.address}"
                                    data-phone_number="${supplier.phone_number || ''}"
                                    data-location="${supplier.location}"
                                    data-payment_terms="${supplier.payment_terms}">
                                    <i class="ti ti-edit me-2"></i> Edit
                                </a>
                                <button type="button" class="dropdown-item text-danger"
                                    data-bs-toggle="modal" data-bs-target="#deleteModal"
                                    onclick="setDeleteFormAction('/admin/supplier/destroy/${supplier.id}')">
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

        // Reinitialize bulk selection if applicable (supplier doesn't have it yet)
        // setTimeout(() => {
        //     initBulkSelection();
        //     restoreCheckboxStates();
        //     updateBulkActionsBarVisibility();
        // }, 100);
    }

    function showNoResults(message = "No suppliers found matching your search.") {
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