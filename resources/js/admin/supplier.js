document.addEventListener("DOMContentLoaded", function () {
    const editSupplierModal = document.getElementById("editSupplierModal");
    const createSupplierModal = document.getElementById("createSupplierModal");

    // Store selected checkbox states globally for suppliers
    let selectedSupplierIds = new Set();

    

    // Event listener for edit modal show
    if (editSupplierModal) {
        editSupplierModal.addEventListener("show.bs.modal", function (event) {
            const button = event.relatedTarget;

            if (!button) return; // Prevent errors if button is null

            const supplierId = button.getAttribute("data-id") || "";
            const supplierCode = button.getAttribute("data-code") || "";
            const supplierName = button.getAttribute("data-name") || "";
            const supplierAddress = button.getAttribute("data-address") || "";
            const supplierPhone =
                button.getAttribute("data-phone_number") || "";
            const supplierLocation = button.getAttribute("data-location") || "";
            const supplierPayment =
                button.getAttribute("data-payment_terms") || "";
            const supplierEmail = button.getAttribute("data-email") || "";
            const supplierImage = button.getAttribute("data-image") || "";

            document.getElementById("supplierId").value = supplierId;
            if (document.getElementById("supplierCodeEdit")) {
                document.getElementById("supplierCodeEdit").value =
                    supplierCode;
            }
            document.getElementById("supplierNameEdit").value = supplierName;
            document.getElementById("supplierAddressEdit").value =
                supplierAddress;
            document.getElementById("supplierPhoneEdit").value = supplierPhone;
            document.getElementById("supplierLocationEdit").value =
                supplierLocation;
            document.getElementById("supplierPaymentTermsEdit").value =
                supplierPayment;
            document.getElementById("supplierEmailEdit").value = supplierEmail;

            // Handle supplier image display
            const currentSupplierImageContainer = document.getElementById(
                "currentSupplierImageContainer"
            );
            const defaultPlaceholderUrl =
                window.defaultPlaceholderUrl || "/img/default_placeholder.png";

            if (currentSupplierImageContainer) {
                // If supplier has image data attribute, use it
                if (
                    supplierImage &&
                    supplierImage !== "" &&
                    supplierImage !== defaultPlaceholderUrl
                ) {
                    currentSupplierImageContainer.innerHTML = `
                        <img src="${supplierImage}" alt="${supplierName || 'Supplier Image'}"
                             class="img-thumbnail"
                             style="max-width: 80px; max-height: 80px; object-fit: cover;">
                    `;
                } else {
                    // Show default placeholder icon
                    currentSupplierImageContainer.innerHTML = `
                        <div class="img-thumbnail d-flex align-items-center justify-content-center"
                             style="width: 80px; height: 80px; margin: 0 auto;">
                            <i class="ti ti-photo fs-1 text-muted"></i>
                        </div>
                    `;
                }
            }

            const routeBase = document.getElementById("updateRouteBase").value;
            document.getElementById("editSupplierForm").action =
                routeBase + "/" + supplierId;

            // Fallback: Try to fetch supplier details if image data attribute is not available
            if (!supplierImage || supplierImage === "") {
                fetch(`/admin/suppliers/${supplierId}/srm-details`)
                    .then((response) => {
                        if (!response.ok) {
                            throw new Error(
                                `HTTP error! status: ${response.status}`
                            );
                        }
                        return response.json();
                    })
                    .then((data) => {
                        if (
                            data &&
                            data.supplier &&
                            currentSupplierImageContainer
                        ) {
                            if (
                                data.supplier.image &&
                                data.supplier.image !== defaultPlaceholderUrl
                            ) {
                                currentSupplierImageContainer.innerHTML = `
                                    <img src="${data.supplier.image}" alt="${data.supplier.name || 'Supplier Image'}"
                                         class="img-thumbnail"
                                         style="max-width: 80px; max-height: 80px; object-fit: cover;">
                                `;
                            } else {
                                currentSupplierImageContainer.innerHTML = `
                                    <div class="img-thumbnail d-flex align-items-center justify-content-center"
                                         style="width: 80px; height: 80px; margin: 0 auto;">
                                    <i class="ti ti-photo fs-1 text-muted"></i>
                                </div>
                            `;
                            }
                        }
                    })
                    .catch((error) => {
                        console.error(
                            "Error fetching supplier details for edit modal:",
                            error
                        );
                        if (currentSupplierImageContainer) {
                            currentSupplierImageContainer.innerHTML = `
                                <div class="img-thumbnail d-flex align-items-center justify-content-center"
                                     style="width: 80px; height: 80px; margin: 0 auto;">
                                    <i class="ti ti-photo fs-1 text-muted"></i>
                                </div>
                            `;
                        }
                    });
            }
        });
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

    function extractSupplierDataFromRow(row) {
        // Changed function name
        try {
            const codeElement = row.querySelector(".sort-code");
            const nameElement = row.querySelector(".sort-name");
            const addressElement = row.querySelector(".sort-address");
            const locationElement = row.querySelector(".sort-location");
            const paymentTermsElement = row.querySelector(".sort-paymentterms");
            const emailElement = row.querySelector(".sort-email");
            const imageElement = row.querySelector(".sort-image img");

            if (!nameElement) return null;

            return {
                id: parseInt(row.dataset.id),
                code: codeElement?.textContent?.trim() || "N/A",
                name: nameElement.textContent.trim(),
                address: addressElement?.textContent?.trim() || "N/A",
                location: locationElement?.textContent?.trim() || "N/A",
                payment_terms:
                    paymentTermsElement?.textContent?.trim() || "N/A",
                email: emailElement?.textContent?.trim() || "N/A",
                image: imageElement?.src || "",
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

    function renderSearchResults(suppliers) {
        // Changed parameter name
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
                    <td class="sort-image">
                        <img src="${supplier.image || '/img/default_placeholder.png'}" alt="Supplier Image" class="avatar avatar-sm">
                    </td>
                    <td class="sort-code">${supplier.code}</td>
                    <td class="sort-name">${supplier.name}</td>
                    <td class="sort-address">${supplier.address}</td>
                    <td class="sort-location">${supplier.location}</td>
                    <td class="sort-paymentterms">${supplier.payment_terms}</td>
                    <td class="sort-email">${supplier.email || "N/A"}</td>
                    <td class="no-print" style="text-align:center">
                        <div class="dropdown">
                            <button class="btn dropdown-toggle align-text-top"
                                data-bs-toggle="dropdown" data-bs-boundary="viewport">
                                Actions
                            </button>
                            <div class="dropdown-menu">
                                <a href="#" class="dropdown-item srm-supplier-btn"
                                                                    data-id="${supplier.id}" data-bs-toggle="modal"
                                                                    data-bs-target="#srmSupplierModal">
                                                                    <i class="ti ti-user-search me-2"></i> View SRM
                                                                </a>
                                <a href="#" class="dropdown-item"
                                    data-bs-toggle="modal"
                                    data-bs-target="#editSupplierModal"
                                    data-id="${supplier.id}"
                                    data-code="${supplier.code}"
                                    data-name="${supplier.name}"
                                    data-address="${supplier.address}"
                                    data-phone_number="${supplier.phone_number || ''}"
                                    data-location="${supplier.location}"
                                    data-payment_terms="${supplier.payment_terms}"
                                    data-email="${supplier.email || ''}"
                                    data-image="${supplier.image || ''}">
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

    function showNoResults(
        message = "No suppliers found matching your search."
    ) {
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

    const srmSupplierModal = document.getElementById("srmSupplierModal");

    // SRM Modal Logic
    if (srmSupplierModal) {
        let currentPage = 1;
        let supplierId = null;
        let lastPage = 1;

        // Function to safely update element text content
        function safeUpdateElement(elementId, content) {
            const element = document.getElementById(elementId);
            if (element) {
                element.textContent = content;
            }
        }

        // Function to safely update element innerHTML
        function safeUpdateElementHTML(elementId, content) {
            const element = document.getElementById(elementId);
            if (element) {
                element.innerHTML = content;
            }
        }

        // Function to safely show/hide element
        function safeToggleElement(elementId, display) {
            const element = document.getElementById(elementId);
            if (element) {
                element.style.display = display;
            }
        }

        // Function to clear all data and show loading
        function showLoadingState() {
            // Update supplier info elements
            safeUpdateElement("srmSupplierName", "Loading...");
            safeUpdateElement("srmSupplierEmail", "Loading...");
            safeUpdateElement("srmSupplierPhone", "Loading...");
            safeUpdateElement("srmSupplierAddress", "Loading...");
            safeUpdateElement("srmSupplierPaymentTerms", "Loading...");

            // Update metrics elements
            safeUpdateElement("srmLifetimeValue", "Loading...");
            safeUpdateElement("srmTotalPurchasesCount", "Loading...");
            safeUpdateElement("srmAverageOrderValue", "Loading...");
            safeUpdateElement("srmLastInteractionDate", "Loading...");
            safeUpdateElement("srmMostPurchasedProduct", "Loading...");
            safeUpdateElement("srmTotalProductsPurchased", "Loading...");
            safeUpdateElement("srmMemberSince", "Loading...");
            safeUpdateElement("srmLastPurchase", "Loading...");

            // Show loading spinners
            safeUpdateElementHTML(
                "srmInteractionTimeline",
                '<div class="text-center py-3"><div class="spinner-border text-primary" role="status"></div></div>'
            );
            safeUpdateElementHTML(
                "srmTransactionHistory",
                '<div class="text-center py-3"><div class="spinner-border text-primary" role="status"></div></div>'
            );
            safeUpdateElementHTML(
                "srmHistoricalPurchaseContent",
                '<div class="text-center py-3"><div class="spinner-border text-primary" role="status"></div></div>'
            );

            // Hide buttons and messages
            safeToggleElement("srmLoadMoreTransactions", "none");
            safeToggleElement("srmNoInteractionsMessage", "none");
            safeToggleElement("srmNoTransactionsMessage", "none");
        }

        // Function to show error state
        function showErrorState(message = "Error") {
            safeUpdateElement("srmSupplierName", message);
            safeUpdateElement("srmSupplierEmail", message);
            safeUpdateElement("srmSupplierPhone", message);
            safeUpdateElement("srmSupplierAddress", message);
            safeUpdateElement("srmSupplierPaymentTerms", message);
            safeUpdateElement("srmLifetimeValue", message);
            safeUpdateElement("srmTotalPurchasesCount", message);
            safeUpdateElement("srmAverageOrderValue", message);
            safeUpdateElement("srmLastInteractionDate", message);
            safeUpdateElement("srmMostPurchasedProduct", message);
            safeUpdateElement("srmTotalProductsPurchased", message);
            safeUpdateElement("srmMemberSince", message);
            safeUpdateElement("srmLastPurchase", message);

            safeUpdateElementHTML(
                "srmInteractionTimeline",
                `<p class="text-danger text-center py-3">Failed to load interactions.</p>`
            );
            safeUpdateElementHTML(
                "srmTransactionHistory",
                `<p class="text-danger text-center py-3">Failed to load transactions.</p>`
            );
            safeUpdateElementHTML(
                "srmHistoricalPurchaseContent",
                `<p class="text-danger text-center py-3">Failed to load purchase history.</p>`
            );

            safeToggleElement("srmLoadMoreTransactions", "none");
        }

        srmSupplierModal.addEventListener("show.bs.modal", function (event) {
            const button = event.relatedTarget;
            if (!button) return;

            supplierId = button.getAttribute("data-id");
            if (!supplierId) {
                console.error("Supplier ID not found");
                showErrorState("Supplier ID not found");
                return;
            }

            currentPage = 1;
            showLoadingState();
            loadSrmData(supplierId, currentPage);
        });

        // Load more transactions button
        const loadMoreBtn = document.getElementById("srmLoadMoreTransactions");
        if (loadMoreBtn) {
            loadMoreBtn.addEventListener("click", function () {
                if (currentPage < lastPage) {
                    currentPage++;
                    loadSrmData(supplierId, currentPage, true);
                }
            });
        }

        // Interaction form submission
        const interactionForm = document.getElementById("srmInteractionForm");
        if (interactionForm) {
            interactionForm.addEventListener("submit", function (event) {
                event.preventDefault();
                const form = event.target;
                const formData = new FormData(form);

                if (!supplierId) {
                    showToast("Error", "Supplier ID not found.", "error");
                    return;
                }

                fetch(`/admin/suppliers/${supplierId}/interactions`, {
                    method: "POST",
                    body: formData,
                    headers: {
                        "X-Requested-With": "XMLHttpRequest",
                        "X-CSRF-TOKEN": document
                            .querySelector('meta[name="csrf-token"]')
                            .getAttribute("content"),
                    },
                })
                    .then((response) => response.json())
                    .then((data) => {
                        if (data.id) {
                            showToast(
                                "Success",
                                "Interaction added.",
                                "success"
                            );
                            const timeline = document.getElementById(
                                "srmInteractionTimeline"
                            );
                            if (timeline) {
                                const newInteraction =
                                    document.createElement("div");
                                newInteraction.classList.add(
                                    "list-group-item",
                                    "list-group-item-action"
                                );
                                newInteraction.innerHTML = `
                                <div class="d-flex w-100 justify-content-between">
                                    <h5 class="mb-1">${data.type.charAt(0).toUpperCase() + data.type.slice(1)} on ${new Date(data.interaction_date).toLocaleDateString("id-ID")}</h5>
                                    <small class="text-muted">by ${data.user.name}</small>
                                </div>
                                <p class="mb-1">${data.notes}</p>
                            `;
                                timeline.prepend(newInteraction);
                                safeToggleElement(
                                    "srmNoInteractionsMessage",
                                    "none"
                                );
                            }
                            form.reset();
                            form.querySelector(
                                'input[name="interaction_date"]'
                            ).value = new Date().toISOString().slice(0, 10);
                        } else {
                            showToast(
                                "Error",
                                "Failed to add interaction.",
                                "error"
                            );
                        }
                    })
                    .catch((error) => {
                        console.error("Error:", error);
                        showToast("Error", "An error occurred.", "error");
                    });
            });
        }

        // Currency formatter
        function formatCurrency(value) {
            return new Intl.NumberFormat("id-ID", {
                style: "currency",
                currency: "IDR",
                maximumFractionDigits: 0,
            }).format(parseFloat(value) || 0);
        }

        // Date formatter for "3 June 2025" format
        function formatDateToCustomString(dateString) {
            if (!dateString) return "N/A";
            const options = { day: "numeric", month: "long", year: "numeric" };
            return new Date(dateString).toLocaleDateString("en-US", options);
        }

        // Status badge generator
        function getStatusBadgeHtml(status, dueDate) {
            let badgeClass = "";
            let statusText = status;

            const today = new Date();
            const due = new Date(dueDate);
            today.setHours(0, 0, 0, 0);
            due.setHours(0, 0, 0, 0);

            if (status === "Paid") {
                badgeClass = "bg-success-lt";
            } else if (status === "Unpaid" && due < today) {
                badgeClass = "bg-danger-lt";
                statusText = "Overdue";
            } else if (status === "Unpaid") {
                badgeClass = "bg-warning-lt";
            } else {
                badgeClass = "bg-secondary-lt";
            }
            return `<span class="badge ${badgeClass}">${statusText}</span>`;
        }

        // Main function to load SRM data
        function loadSrmData(id, page, append = false) {
            if (!id) {
                console.error("Supplier ID is required");
                showErrorState("Supplier ID is required");
                return;
            }

            console.log(
                `Loading SRM data for supplier ${id}, page ${page}, append: ${append}`
            );

            fetch(`/admin/suppliers/${id}/srm-details?page=${page}`)
                .then((response) => {
                    console.log("Response status:", response.status);
                    if (!response.ok) {
                        throw new Error(
                            `HTTP error! status: ${response.status}`
                        );
                    }
                    return response.json();
                })
                .then((data) => {
                    console.log("SRM Data received:", data);

                    if (!data || !data.supplier) {
                        throw new Error("Supplier data not found in response");
                    }

                    // Clear loading states only if not appending
                    if (!append) {
                        safeUpdateElementHTML("srmInteractionTimeline", "");
                        safeUpdateElementHTML("srmTransactionHistory", "");
                        safeToggleElement("srmNoInteractionsMessage", "none");
                        safeToggleElement("srmNoTransactionsMessage", "none");
                    }

                    // Populate supplier information
                    safeUpdateElement(
                        "srmSupplierNameInHeader",
                        `[${data.supplier.name || "N/A"}]`
                    );
                    safeUpdateElement(
                        "srmSupplierName",
                        data.supplier.name || "N/A"
                    );
                    safeUpdateElement(
                        "srmSupplierEmail",
                        data.supplier.email || "N/A"
                    );
                    safeUpdateElement(
                        "srmSupplierPhone",
                        data.supplier.phone_number || "N/A"
                    );
                    safeUpdateElement(
                        "srmSupplierAddress",
                        data.supplier.address || "N/A"
                    );
                    safeUpdateElement(
                        "srmSupplierPaymentTerms",
                        data.supplier.payment_terms || "N/A"
                    );

                    // Update supplier image if element exists
                    const srmSupplierImageContainer = document.getElementById(
                        "srmSupplierImageContainer"
                    );
                    const defaultPlaceholderUrl =
                        window.defaultPlaceholderUrl ||
                        "/img/default_placeholder.png";

                    if (srmSupplierImageContainer) {
                        if (
                            data.supplier.image &&
                            data.supplier.image !== defaultPlaceholderUrl
                        ) {
                            srmSupplierImageContainer.innerHTML = `
                                <img src="${data.supplier.image}" alt="${
                                    data.supplier.name || "Supplier Image"
                                }"
                                     class="img-thumbnail"
                                     style="max-width: 120px; max-height: 120px; object-fit: cover;">
                            `;
                        } else {
                            srmSupplierImageContainer.innerHTML = `
                                <div class="img-thumbnail d-flex align-items-center justify-content-center"
                                     style="width: 120px; height: 120px; margin: 0 auto;">
                                    <i class="ti ti-photo fs-1 text-muted"></i>
                                </div>
                            `;
                        }
                    }

                    // Populate metrics
                    safeUpdateElement(
                        "srmLifetimeValue",
                        formatCurrency(data.lifetimeValue || 0)
                    );
                    safeUpdateElement(
                        "srmTotalPurchasesCount",
                        data.totalPurchasesCount || 0
                    );
                    safeUpdateElement(
                        "srmAverageOrderValue",
                        formatCurrency(data.averageOrderValue || 0)
                    );
                    safeUpdateElement(
                        "srmLastInteractionDate",
                        data.lastInteractionDate
                            ? new Date(
                                  data.lastInteractionDate
                              ).toLocaleDateString("id-ID")
                            : "N/A"
                    );
                    safeUpdateElement(
                        "srmMostPurchasedProduct",
                        data.mostPurchasedProduct || "N/A"
                    );
                    safeUpdateElement(
                        "srmTotalProductsPurchased",
                        data.totalProductsPurchased || 0
                    );
                    safeUpdateElement(
                        "srmMemberSince",
                        data.supplier.created_at
                            ? new Date(
                                  data.supplier.created_at
                              ).toLocaleDateString("id-ID")
                            : "N/A"
                    );
                    safeUpdateElement(
                        "srmLastPurchase",
                        data.lastPurchaseDate
                            ? new Date(
                                  data.lastPurchaseDate
                              ).toLocaleDateString("id-ID")
                            : "N/A"
                    );

                    // Populate interactions
                    const interactionTimeline = document.getElementById(
                        "srmInteractionTimeline"
                    );
                    if (interactionTimeline) {
                        if (!append) {
                            interactionTimeline.innerHTML = "";
                        }

                        if (
                            data.supplier.interactions &&
                            data.supplier.interactions.length > 0
                        ) {
                            safeToggleElement(
                                "srmNoInteractionsMessage",
                                "none"
                            );
                            data.supplier.interactions.forEach(
                                (interaction) => {
                                    const interactionElement =
                                        document.createElement("div");
                                    interactionElement.classList.add(
                                        "list-group-item",
                                        "list-group-item-action"
                                    );
                                    interactionElement.innerHTML = `
                                    <div class="d-flex w-100 justify-content-between">
                                        <h5 class="mb-1">${interaction.type.charAt(0).toUpperCase() + interaction.type.slice(1)} on ${new Date(interaction.interaction_date).toLocaleDateString("id-ID")}</h5>
                                        <small class="text-muted">by ${interaction.user ? interaction.user.name : "Unknown"}</small>
                                </div>
                                <p class="mb-1">${interaction.notes}</p>
                            `;
                                    interactionTimeline.appendChild(
                                        interactionElement
                                    );
                                }
                            );
                        } else if (!append) {
                            safeToggleElement(
                                "srmNoInteractionsMessage",
                                "block"
                            );
                        }
                    }

                    // Populate transaction history
                    const transactionHistory = document.getElementById(
                        "srmTransactionHistory"
                    );
                    if (transactionHistory) {
                        if (!append) {
                            transactionHistory.innerHTML = "";
                        }

                        if (
                            data.purchases &&
                            data.purchases.data &&
                            data.purchases.data.length > 0
                        ) {
                            lastPage = data.purchases.last_page || 1;
                            safeToggleElement(
                                "srmNoTransactionsMessage",
                                "none"
                            );

                            data.purchases.data.forEach((purchase) => {
                                const purchaseElement =
                                    document.createElement("div");
                                purchaseElement.classList.add("accordion-item");
                                purchaseElement.innerHTML = `
                                    <h2 class="accordion-header" id="heading-${purchase.id}">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-${purchase.id}" aria-expanded="false" aria-controls="collapse-${purchase.id}">
                                            <div class="d-flex justify-content-between w-100 pe-3">
                                                <div>
                                                    Invoice #${purchase.invoice} - ${formatDateToCustomString(purchase.created_at)}
                                                    ${getStatusBadgeHtml(purchase.status, purchase.due_date)}
                                                </div>
                                                <div class="fw-bold">${formatCurrency(purchase.total_amount)}</div>
                                            </div>
                                        </button>
                                    </h2>
                                    <div id="collapse-${purchase.id}" class="accordion-collapse collapse" aria-labelledby="heading-${purchase.id}" data-bs-parent="#srmTransactionHistory">
                                        <div class="accordion-body">
                                            <div class="row mb-3">
                                                <div class="col-md-6">
                                                    <p class="mb-1"><strong>Order Date:</strong> ${formatDateToCustomString(purchase.date)}</p>
                                                    <p class="mb-1"><strong>Due Date:</strong> ${formatDateToCustomString(purchase.due_date)}</p>
                                                </div>
                                                <div class="col-md-6 text-end">
                                                    <p class="mb-1"><strong>Payment Type:</strong> ${purchase.payment_method || "N/A"}</p>
                                                </div>
                                            </div>
                                            <h6 class="fs-4">Items:</h6>
                                            <div class="table-responsive">
                                                <table class="table table-sm table-bordered">
                                                    <thead>
                                                        <tr>
                                                            <th>Product</th>
                                                            <th class="text-center">Qty</th>
                                                            <th class="text-end">Unit Price</th>
                                                            <th class="text-end">Line Total</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        ${purchase.purchase_items && purchase.purchase_items.length > 0 ? purchase.purchase_items.map((item) => `
                                                            <tr>
                                                                <td>${item.product ? item.product.name : "N/A"}</td>
                                                                <td class="text-center">${item.quantity || 0}</td>
                                                                <td class="text-end">${formatCurrency(item.price || 0)}</td>
                                                                <td class="text-end">${formatCurrency(item.total || 0)}</td>
                                                            </tr>
                                                        `).join("") : '<tr><td colspan="4" class="text-center">No items found</td></tr>'}
                                                    </tbody>
                                                </table>
                                            </div>
                                            <div class="row mt-3">
                                                <div class="col-md-12 text-end">
                                                    <p class="mb-1"><strong>Subtotal:</strong> ${formatCurrency(
                                                        purchase.total_amount -
                                                            purchase.tax_amount
                                                    )}</p>
                                                    <p class="mb-1"><strong>Discount:</strong> ${formatCurrency(
                                                        purchase.discount_amount
                                                    )}</p>
                                                    <p class="mb-1"><strong>Tax:</strong> ${formatCurrency(
                                                        purchase.tax_amount
                                                    )}</p>
                                                    <p class="mb-1"><strong>Grand Total:</strong> <span class="text-primary fw-bold">${formatCurrency(
                                                        purchase.total_amount
                                                    )}</span></p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                `;
                                transactionHistory.appendChild(purchaseElement);
                            });
                        } else if (!append) {
                            safeToggleElement(
                                "srmNoTransactionsMessage",
                                "block"
                            );
                        }

                        // Manage Load More button
                        const currentPageNum = data.purchases
                            ? data.purchases.current_page
                            : 1;
                        const lastPageNum = data.purchases
                            ? data.purchases.last_page
                            : 1;

                        if (currentPageNum < lastPageNum) {
                            safeToggleElement(
                                "srmLoadMoreTransactions",
                                "block"
                            );
                        } else {
                            safeToggleElement(
                                "srmLoadMoreTransactions",
                                "none"
                            );
                        }
                    }

                    console.log("SRM data loaded successfully");
                })
                .catch((error) => {
                    console.error("Error loading SRM data:", error);
                    showToast(
                        "Error",
                        `Failed to load SRM data: ${error.message}`,
                        "error"
                    );
                    showErrorState("Failed to load");
                });
        }

        // Function to load historical purchases
        function loadHistoricalPurchases(id) {
            console.log("loadHistoricalPurchases called for ID:", id);
            const historicalPurchaseContent = document.getElementById(
                "srmHistoricalPurchaseContent"
            );

            if (historicalPurchaseContent) {
                // Show loading state
                historicalPurchaseContent.innerHTML =
                    '<div class="d-flex justify-content-center align-items-center py-5"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>';
            } else {
                console.error(
                    "Element with ID 'srmHistoricalPurchaseContent' not found!"
                );
                return;
            }

            fetch(`/admin/suppliers/${id}/historical-purchases`)
                .then((response) => {
                    console.log(
                        "Historical purchases response status:",
                        response.status
                    );
                    if (!response.ok) {
                        throw new Error(
                            `HTTP error! status: ${response.status}`
                        );
                    }
                    return response.json();
                })
                .then((data) => {
                    console.log("Historical purchases data received:", data);
                    if (historicalPurchaseContent) {
                        if (
                            data.historical_purchases &&
                            data.historical_purchases.length > 0
                        ) {
                            console.log(
                                "Data has historical_purchases and it's not empty."
                            );
                            // Create modern card-based layout
                            let contentHtml = `
                        <div class="accordion" id="srmHistoricalPurchasesAccordion">
                    `;

                            data.historical_purchases.forEach((purchase) => {
                                contentHtml += `
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="heading-${purchase.id}">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-${purchase.id}" aria-expanded="false" aria-controls="collapse-${purchase.id}">
                                        <div class="d-flex justify-content-between w-100 pe-3">
                                            <div>
                                                Invoice #${purchase.invoice} - ${formatDateToCustomString(purchase.order_date)}
                                                ${getStatusBadgeHtml(purchase.status, purchase.due_date)}
                                            </div>
                                            <div class="fw-bold">${formatCurrency(purchase.total_amount)}</div>
                                        </div>
                                    </button>
                                </h2>
                                <div id="collapse-${purchase.id}" class="accordion-collapse collapse" aria-labelledby="heading-${purchase.id}" data-bs-parent="#srmHistoricalPurchasesAccordion">
                                    <div class="accordion-body">
                                        <div class="row mb-3">
                                            <div class="col-md-6">
                                                <p class="mb-1"><strong>Order Date:</strong> ${formatDateToCustomString(purchase.order_date)}</p>
                                                <p class="mb-1"><strong>Due Date:</strong> ${formatDateToCustomString(purchase.due_date)}</p>
                                            </div>
                                            <div class="col-md-6 text-end">
                                                <p class="mb-1"><strong>Payment Type:</strong> ${purchase.payment_method || "N/A"}</p>
                                            </div>
                                        </div>
                                        <h6 class="fs-4">Items:</h6>
                                        <div class="table-responsive">
                                            <table class="table table-sm table-bordered">
                                                <thead>
                                                    <tr>
                                                        <th>Product</th>
                                                        <th class="text-center">Qty</th>
                                                        <th class="text-end">Unit Price</th>
                                                        <th class="text-end">Line Total</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    ${
                                                        purchase.purchase_items &&
                                                        purchase.purchase_items
                                                            .length > 0
                                                            ? purchase.purchase_items
                                                                  .map(
                                                                      (
                                                                          item
                                                                      ) => `
                                                        <tr>
                                                            <td>${
                                                                item.product
                                                                    ? item
                                                                          .product
                                                                          .name
                                                                    : "N/A"
                                                            }</td>
                                                            <td class="text-center">${
                                                                item.quantity ||
                                                                0
                                                            }</td>
                                                            <td class="text-end">${formatCurrency(
                                                                item.price || 0
                                                            )}</td>
                                                            <td class="text-end">${formatCurrency(
                                                                item.total || 0
                                                            )}</td>
                                                        </tr>
                                                    `
                                                                  )
                                                                  .join("")
                                                            : '<tr><td colspan="4" class="text-center">No items found</td></tr>'
                                                    }
                                                </tbody>
                                            </table>
                                        </div>
                                        <div class="row mt-3">
                                            <div class="col-md-12 text-end">
                                                <p class="mb-1"><strong>Subtotal:</strong> ${formatCurrency(
                                                    purchase.total_amount
                                                )}</p>
                                                <p class="mb-1"><strong>Discount:</strong> ${formatCurrency(
                                                    purchase.discount_amount
                                                )}</p>
                                                <p class="mb-1"><strong>Grand Total:</strong> <span class="text-primary fw-bold">${formatCurrency(
                                                    purchase.total_amount -
                                                        purchase.discount_amount
                                                )}</span></p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        `;
                            });

                            contentHtml += `
                        </div>
                    `;
                            console.log("Generated contentHtml:", contentHtml);
                            historicalPurchaseContent.innerHTML = contentHtml;
                            console.log("innerHTML updated with contentHtml.");
                        } else {
                            console.log(
                                "No historical_purchases found or array is empty."
                            );
                            historicalPurchaseContent.innerHTML = `
                        <div class="text-center py-5">
                            <div class="mb-3">
                                <i class="ti ti-shopping-cart-off fs-1 text-muted"></i>
                            </div>
                            <h5 class="text-muted">No Purchase History</h5>
                            <p class="text-muted mb-0">This supplier hasn't made any purchases yet.</p>
                        </div>
                    `;
                            console.log(
                                "innerHTML updated with 'No purchase history' message."
                            );
                        }
                    } else {
                        console.error(
                            "Element with ID 'srmHistoricalPurchaseContent' was null after fetch!"
                        );
                    }
                })
                .catch((error) => {
                    console.error(
                        "Fetch error in loadHistoricalPurchases:",
                        error
                    );
                    if (historicalPurchaseContent) {
                        historicalPurchaseContent.innerHTML = `
                    <div class="text-center py-5">
                        <div class="mb-3">
                            <i class="ti ti-alert-circle fs-1 text-danger"></i>
                        </div>
                        <h5 class="text-danger">Error Loading Data</h5>
                        <p class="text-muted mb-0">Failed to load purchase history: ${error.message}</p>
                    </div>
                `;
                    }
                });
        }

        // Event listener for Historical Purchase tab
        const historicalPurchaseTab = document.getElementById(
            "srm-historical-purchases-tab"
        );
        if (historicalPurchaseTab) {
            historicalPurchaseTab.addEventListener(
                "shown.bs.tab",
                function (event) {
                    if (supplierId) {
                        loadHistoricalPurchases(supplierId);
                    }
                }
            );
        }

        // Function to load product history
        function loadProductHistory(id) {
            console.log("loadProductHistory called for ID:", id);
            const productHistoryContent = document.getElementById(
                "srmProductHistoryContent"
            );

            if (productHistoryContent) {
                // Show loading state
                productHistoryContent.innerHTML =
                    '<div class="d-flex justify-content-center align-items-center py-5"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>';
            } else {
                console.error(
                    "Element with ID 'srmProductHistoryContent' not found!"
                );
                return;
            }

            fetch(`/admin/suppliers/${id}/product-history`)
                .then((response) => {
                    console.log(
                        "Product history response status:",
                        response.status
                    );
                    if (!response.ok) {
                        throw new Error(
                            `HTTP error! status: ${response.status}`
                        );
                    }
                    return response.json();
                })
                .then((data) => {
                    console.log("Product history data received:", data);
                    if (productHistoryContent) {
                        if (
                            data.product_history &&
                            data.product_history.length > 0
                        ) {
                            console.log(
                                "Data has product_history and it's not empty."
                            );
                            // Create modern card-based layout
                            let contentHtml = `
                        <div class="accordion" id="srmProductHistoryAccordion">
                    `;

                            data.product_history.forEach((product) => {
                                contentHtml += `
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="product-heading-${product.product_name.replace(
                                    /\s+/g,
                                    "-"
                                )}">
                                    <button class="accordion-button collapsed fs-3" type="button" data-bs-toggle="collapse" data-bs-target="#product-collapse-${product.product_name.replace(
                                        /\s+/g,
                                        "-"
                                    )}" aria-expanded="false" aria-controls="product-collapse-${product.product_name.replace(
                                    /\s+/g,
                                    "-"
                                )}">
                                        <div class="d-flex justify-content-between w-100 pe-3">
                                            <span>${product.product_name}</span>
                                            <span class="text-muted fs-4">Last Price: ${formatCurrency(
                                                product.last_price
                                            )}</span>
                                        </div>
                                    </button>
                                </h2>
                                <div id="product-collapse-${product.product_name.replace(
                                    /\s+/g,
                                    "-"
                                )}" class="accordion-collapse collapse" aria-labelledby="product-heading-${product.product_name.replace(
                                    /\s+/g,
                                    "-"
                                )}" data-bs-parent="#srmProductHistoryAccordion">
                                    <div class="accordion-body">
                                        <div class="list-group list-group-flush">
                                            ${product.history
                                                .map(
                                                    (item) => `
                                                <div class="list-group-item px-0">
                                                    <div class="row align-items-center">
                                                        <div class="col-md-5 mb-2 mb-md-0">
                                                            <div class="d-flex align-items-center">
                                                                <div class="me-3">
                                                                    <i class="ti ti-file-invoice text-primary fs-2"></i>
                                                                </div>
                                                                <div>
                                                                    <h6 class="mb-0 fs-4">Invoice #${
                                                                        item.invoice
                                                                    }</h6>
                                                                    <small class="text-muted">${formatDateToCustomString(
                                                                        item.order_date
                                                                    )}</small>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="col-6 col-md-3 text-center">
                                                            <div class="text-muted small">Quantity</div>
                                                            <div class="fw-bold">${
                                                                item.quantity
                                                            }</div>
                                                        </div>
                                                        <div class="col-6 col-md-4 text-end">
                                                            <div class="text-muted small">Price</div>
                                                            <div class="fw-bold text-success">${formatCurrency(
                                                                item.price_at_purchase
                                                            )}</div>
                                                        </div>
                                                    </div>
                                                </div>
                                            `
                                                )
                                                .join("")}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        `;
                            });

                            contentHtml += `
                        </div>
                    `;
                            console.log("Generated contentHtml:", contentHtml);
                            productHistoryContent.innerHTML = contentHtml;
                            console.log("innerHTML updated with contentHtml.");
                        } else {
                            console.log(
                                "No product_history found or array is empty."
                            );
                            productHistoryContent.innerHTML = `
                        <div class="text-center py-5">
                            <div class="mb-3">
                                <i class="ti ti-shopping-cart-off fs-1 text-muted"></i>
                            </div>
                            <h5 class="text-muted">No Product History</h5>
                            <p class="text-muted mb-0">This supplier hasn't purchased any products yet.</p>
                        </div>
                    `;
                            console.log(
                                "innerHTML updated with 'No product history' message."
                            );
                        }
                    } else {
                        console.error(
                            "Element with ID 'srmProductHistoryContent' was null after fetch!"
                        );
                    }
                })
                .catch((error) => {
                    console.error("Fetch error in loadProductHistory:", error);
                    if (productHistoryContent) {
                        productHistoryContent.innerHTML = `
                    <div class="text-center py-5">
                        <div class="mb-3">
                            <i class="ti ti-alert-circle fs-1 text-danger"></i>
                        </div>
                        <h5 class="text-danger">Error Loading Data</h5>
                        <p class="text-muted mb-0">Failed to load product history: ${error.message}</p>
                    </div>
                `;
                    }
                });
        }

        // Event listener for Product History tab
        const productHistoryTab = document.getElementById(
            "srm-product-history-tab"
        );
        if (productHistoryTab) {
            productHistoryTab.addEventListener(
                "shown.bs.tab",
                function (event) {
                    if (supplierId) {
                        loadProductHistory(supplierId);
                    }
                }
            );
        }
    }
});
