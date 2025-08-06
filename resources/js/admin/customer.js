document.addEventListener("DOMContentLoaded", function () {
    const editCustomerModal = document.getElementById("editCustomerModal");
    const createCustomerModal = document.getElementById("createCustomerModal");
    const crmCustomerModal = document.getElementById("crmCustomerModal");

    // Edit customer modal logic
    if (editCustomerModal) {
        editCustomerModal.addEventListener("show.bs.modal", function (event) {
            const button = event.relatedTarget;
            if (!button) return;

            const customerId = button.getAttribute("data-id") || "";
            const customerName = button.getAttribute("data-name") || "";
            const customerAddress = button.getAttribute("data-address") || "";
            const customerPhone =
                button.getAttribute("data-phone_number") || "";
            const customerPayment =
                button.getAttribute("data-payment_terms") || "";
            const customerEmail = button.getAttribute("data-email") || "";
            const customerImage = button.getAttribute("data-image") || "";

            // Populate form fields
            document.getElementById("customerId").value = customerId;
            document.getElementById("customerNameEdit").value = customerName;
            document.getElementById("customerAddressEdit").value =
                customerAddress;
            document.getElementById("customerPhoneEdit").value = customerPhone;
            document.getElementById("customerPaymentTermsEdit").value =
                customerPayment;
            document.getElementById("customerEmailEdit").value = customerEmail;

            // Set form action
            const routeBase = document.getElementById("updateRouteBase").value;
            document.getElementById("editCustomerForm").action =
                routeBase + "/" + customerId;

            // Handle customer image display
            const currentCustomerImageContainer = document.getElementById(
                "currentCustomerImageContainer"
            );
            const defaultPlaceholderUrl =
                window.defaultPlaceholderUrl || "/img/default_placeholder.png";

            if (currentCustomerImageContainer) {
                // If customer has image data attribute, use it
                if (
                    customerImage &&
                    customerImage !== "" &&
                    customerImage !== defaultPlaceholderUrl
                ) {
                    currentCustomerImageContainer.innerHTML = `
                        <img src="${customerImage}" alt="${
                        customerName || "Customer Image"
                    }"
                             class="img-thumbnail"
                             style="max-width: 80px; max-height: 80px; object-fit: cover;">
                    `;
                } else {
                    // Show default placeholder icon
                    currentCustomerImageContainer.innerHTML = `
                        <div class="img-thumbnail d-flex align-items-center justify-content-center"
                             style="width: 80px; height: 80px; margin: 0 auto;">
                            <i class="ti ti-photo fs-1 text-muted"></i>
                        </div>
                    `;
                }
            }

            // Fallback: Try to fetch customer details if image data attribute is not available
            if (!customerImage || customerImage === "") {
                fetch(`/admin/customers/${customerId}/details`)
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
                            data.customer &&
                            currentCustomerImageContainer
                        ) {
                            if (
                                data.customer.image &&
                                data.customer.image !== defaultPlaceholderUrl
                            ) {
                                currentCustomerImageContainer.innerHTML = `
                                    <img src="${data.customer.image}" alt="${
                                    data.customer.name || "Customer Image"
                                }"
                                         class="img-thumbnail"
                                         style="max-width: 80px; max-height: 80px; object-fit: cover;">
                                `;
                            } else {
                                currentCustomerImageContainer.innerHTML = `
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
                            "Error fetching customer details for edit modal:",
                            error
                        );
                        if (currentCustomerImageContainer) {
                            currentCustomerImageContainer.innerHTML = `
                                <div class="img-thumbnail d-flex align-items-center justify-content-center"
                                     style="width: 80px; height: 80px; margin: 0 auto;">
                                    <i class="ti ti-photo fs-1 text-muted"></i>
                                </div>
                            `;
                        }
                    });
            }
        });

        // CRM Modal Logic
        if (crmCustomerModal) {
            let currentPage = 1;
            let customerId = null;
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
                // Update customer info elements
                safeUpdateElement("crmCustomerName", "Loading...");
                safeUpdateElement("crmCustomerEmail", "Loading...");
                safeUpdateElement("crmCustomerPhone", "Loading...");
                safeUpdateElement("crmCustomerAddress", "Loading...");
                safeUpdateElement("crmCustomerPaymentTerms", "Loading...");

                // Update metrics elements
                safeUpdateElement("crmLifetimeValue", "Loading...");
                safeUpdateElement("crmTotalSalesCount", "Loading...");
                safeUpdateElement("crmAverageOrderValue", "Loading...");
                safeUpdateElement("crmLastInteractionDate", "Loading...");
                safeUpdateElement("crmMostPurchasedProduct", "Loading...");
                safeUpdateElement("crmTotalProductsPurchased", "Loading...");
                safeUpdateElement("crmMemberSince", "Loading...");
                safeUpdateElement("crmLastPurchase", "Loading...");

                // Show loading spinners
                safeUpdateElementHTML(
                    "interactionTimeline",
                    '<div class="text-center py-3"><div class="spinner-border text-primary" role="status"></div></div>'
                );
                safeUpdateElementHTML(
                    "transactionHistory",
                    '<div class="text-center py-3"><div class="spinner-border text-primary" role="status"></div></div>'
                );
                safeUpdateElementHTML(
                    "purchaseHistoryContent",
                    '<div class="text-center py-3"><div class="spinner-border text-primary" role="status"></div></div>'
                );

                // Hide buttons and messages
                safeToggleElement("loadMoreTransactions", "none");
                safeToggleElement("noInteractionsMessage", "none");
                safeToggleElement("noTransactionsMessage", "none");
            }

            // Function to show error state
            function showErrorState(message = "Error") {
                safeUpdateElement("crmCustomerName", message);
                safeUpdateElement("crmCustomerEmail", message);
                safeUpdateElement("crmCustomerPhone", message);
                safeUpdateElement("crmCustomerAddress", message);
                safeUpdateElement("crmCustomerPaymentTerms", message);
                safeUpdateElement("crmLifetimeValue", message);
                safeUpdateElement("crmTotalSalesCount", message);
                safeUpdateElement("crmAverageOrderValue", message);
                safeUpdateElement("crmLastInteractionDate", message);
                safeUpdateElement("crmMostPurchasedProduct", message);
                safeUpdateElement("crmTotalProductsPurchased", message);
                safeUpdateElement("crmMemberSince", message);
                safeUpdateElement("crmLastPurchase", message);

                safeUpdateElementHTML(
                    "interactionTimeline",
                    `<p class="text-danger text-center py-3">Failed to load interactions.</p>`
                );
                safeUpdateElementHTML(
                    "transactionHistory",
                    `<p class="text-danger text-center py-3">Failed to load transactions.</p>`
                );
                safeUpdateElementHTML(
                    "purchaseHistoryContent",
                    `<p class="text-danger text-center py-3">Failed to load purchase history.</p>`
                );

                safeToggleElement("loadMoreTransactions", "none");
            }

            crmCustomerModal.addEventListener(
                "show.bs.modal",
                function (event) {
                    const button = event.relatedTarget;
                    if (!button) return;

                    customerId = button.getAttribute("data-id");
                    if (!customerId) {
                        console.error("Customer ID not found");
                        showErrorState("Customer ID not found");
                        return;
                    }

                    currentPage = 1;
                    showLoadingState();
                    loadCrmData(customerId, currentPage);
                }
            );

            // Load more transactions button
            const loadMoreBtn = document.getElementById("loadMoreTransactions");
            if (loadMoreBtn) {
                loadMoreBtn.addEventListener("click", function () {
                    if (currentPage < lastPage) {
                        currentPage++;
                        loadCrmData(customerId, currentPage, true);
                    }
                });
            }

            // Interaction form submission
            const interactionForm = document.getElementById("interactionForm");
            if (interactionForm) {
                interactionForm.addEventListener("submit", function (event) {
                    event.preventDefault();
                    const form = event.target;
                    const formData = new FormData(form);

                    if (!customerId) {
                        showToast("Error", "Customer ID not found.", "error");
                        return;
                    }

                    fetch(`/admin/customers/${customerId}/interactions`, {
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
                                    "interactionTimeline"
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
                                    <h5 class="mb-1">${
                                        data.type.charAt(0).toUpperCase() +
                                        data.type.slice(1)
                                    } on ${new Date(
                                        data.interaction_date
                                    ).toLocaleDateString("id-ID")}</h5>
                                    <small class="text-muted">by ${
                                        data.user.name
                                    }</small>
                                </div>
                                <p class="mb-1">${data.notes}</p>
                            `;
                                    timeline.prepend(newInteraction);
                                    safeToggleElement(
                                        "noInteractionsMessage",
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
                return new Intl.NumberFormat(currencySettings.locale, {
                    style: "currency",
                    currency: currencySettings.currency_code,
                    maximumFractionDigits: currencySettings.decimal_places,
                }).format(parseFloat(value) || 0);
            }

            // Date formatter for "3 June 2025" format
            function formatDateToCustomString(dateString) {
                if (!dateString) return "N/A";
                const options = {
                    day: "numeric",
                    month: "long",
                    year: "numeric",
                };
                return new Date(dateString).toLocaleDateString(
                    "en-US",
                    options
                );
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

            // Main function to load CRM data
            function loadCrmData(id, page, append = false) {
                if (!id) {
                    console.error("Customer ID is required");
                    showErrorState("Customer ID is required");
                    return;
                }

                

                fetch(`/admin/customers/${id}/crm-details?page=${page}`)
                    .then((response) => {
                        
                        if (!response.ok) {
                            throw new Error(
                                `HTTP error! status: ${response.status}`
                            );
                        }
                        return response.json();
                    })
                    .then((data) => {
                        

                        if (!data || !data.customer) {
                            throw new Error(
                                "Customer data not found in response"
                            );
                        }

                        // Clear loading states only if not appending
                        if (!append) {
                            safeUpdateElementHTML("interactionTimeline", "");
                            safeUpdateElementHTML("transactionHistory", "");
                            safeToggleElement("noInteractionsMessage", "none");
                            safeToggleElement("noTransactionsMessage", "none");
                        }

                        // Populate customer information
                        safeUpdateElement(
                            "crmCustomerName",
                            data.customer.name || "N/A"
                        );
                        safeUpdateElement(
                            "crmCustomerEmail",
                            data.customer.email || "N/A"
                        );
                        safeUpdateElement(
                            "crmCustomerPhone",
                            data.customer.phone_number || "N/A"
                        );
                        safeUpdateElement(
                            "crmCustomerAddress",
                            data.customer.address || "N/A"
                        );
                        safeUpdateElement(
                            "crmCustomerPaymentTerms",
                            data.customer.payment_terms || "N/A"
                        );

                        // Update customer image if element exists
                        const crmCustomerImageContainer =
                            document.getElementById(
                                "crmCustomerImageContainer"
                            );
                        const defaultPlaceholderUrl =
                            window.defaultPlaceholderUrl ||
                            "/img/default_placeholder.png";

                        if (crmCustomerImageContainer) {
                            if (
                                data.customer.image &&
                                data.customer.image !== defaultPlaceholderUrl
                            ) {
                                crmCustomerImageContainer.innerHTML = `
                                <img src="${data.customer.image}" alt="${
                                    data.customer.name || "Customer Image"
                                }"
                                     class="img-thumbnail"
                                     style="max-width: 120px; max-height: 120px; object-fit: cover;">
                            `;
                            } else {
                                crmCustomerImageContainer.innerHTML = `
                                <div class="img-thumbnail d-flex align-items-center justify-content-center"
                                     style="width: 120px; height: 120px; margin: 0 auto;">
                                    <i class="ti ti-photo fs-1 text-muted"></i>
                                </div>
                            `;
                            }
                        }

                        // Populate metrics
                        safeUpdateElement(
                            "crmLifetimeValue",
                            formatCurrency(data.lifetimeValue || 0)
                        );
                        safeUpdateElement(
                            "crmTotalSalesCount",
                            data.totalSalesCount || 0
                        );
                        safeUpdateElement(
                            "crmAverageOrderValue",
                            formatCurrency(data.averageOrderValue || 0)
                        );
                        safeUpdateElement(
                            "crmLastInteractionDate",
                            data.lastInteractionDate
                                ? new Date(
                                      data.lastInteractionDate
                                  ).toLocaleDateString("id-ID")
                                : "N/A"
                        );
                        safeUpdateElement(
                            "crmMostPurchasedProduct",
                            data.mostPurchasedProduct || "N/A"
                        );
                        safeUpdateElement(
                            "crmTotalProductsPurchased",
                            data.totalProductsPurchased || 0
                        );
                        safeUpdateElement(
                            "crmMemberSince",
                            data.customer.created_at
                                ? new Date(
                                      data.customer.created_at
                                  ).toLocaleDateString("id-ID")
                                : "N/A"
                        );
                        safeUpdateElement(
                            "crmLastPurchase",
                            data.lastPurchaseDate
                                ? new Date(
                                      data.lastPurchaseDate
                                  ).toLocaleDateString("id-ID")
                                : "N/A"
                        );

                        // Populate interactions
                        const interactionTimeline = document.getElementById(
                            "interactionTimeline"
                        );
                        if (interactionTimeline) {
                            if (!append) {
                                interactionTimeline.innerHTML = "";
                            }

                            if (
                                data.customer.interactions &&
                                data.customer.interactions.length > 0
                            ) {
                                safeToggleElement(
                                    "noInteractionsMessage",
                                    "none"
                                );
                                data.customer.interactions.forEach(
                                    (interaction) => {
                                        const interactionElement =
                                            document.createElement("div");
                                        interactionElement.classList.add(
                                            "list-group-item",
                                            "list-group-item-action"
                                        );
                                        interactionElement.innerHTML = `
                                    <div class="d-flex w-100 justify-content-between">
                                        <h5 class="mb-1">${
                                            interaction.type
                                                .charAt(0)
                                                .toUpperCase() +
                                            interaction.type.slice(1)
                                        } on ${new Date(
                                            interaction.interaction_date
                                        ).toLocaleDateString("id-ID")}</h5>
                                        <small class="text-muted">by ${
                                            interaction.user
                                                ? interaction.user.name
                                                : "Unknown"
                                        }</small>
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
                                    "noInteractionsMessage",
                                    "block"
                                );
                            }
                        }

                        // Populate transaction history
                        const transactionHistory =
                            document.getElementById("transactionHistory");
                        if (transactionHistory) {
                            if (!append) {
                                transactionHistory.innerHTML = "";
                            }

                            if (
                                data.sales &&
                                data.sales.data &&
                                data.sales.data.length > 0
                            ) {
                                lastPage = data.sales.last_page || 1;
                                safeToggleElement(
                                    "noTransactionsMessage",
                                    "none"
                                );

                                data.sales.data.forEach((sale) => {
                                    const saleElement =
                                        document.createElement("div");
                                    saleElement.classList.add("accordion-item");
                                    saleElement.innerHTML = `
                                    <h2 class="accordion-header" id="heading-${
                                        sale.id
                                    }">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-${
                                            sale.id
                                        }" aria-expanded="false" aria-controls="collapse-${
                                        sale.id
                                    }">
                                            <div class="d-flex justify-content-between w-100 pe-3">
                                                <div>
                                                    Invoice #${
                                                        sale.invoice
                                                    } - ${formatDateToCustomString(
                                        sale.created_at
                                    )}
                                                    ${getStatusBadgeHtml(
                                                        sale.status,
                                                        sale.due_date
                                                    )}
                                                </div>
                                                <div class="fw-bold">${formatCurrency(
                                                    sale.total
                                                )}</div>
                                            </div>
                                        </button>
                                    </h2>
                                    <div id="collapse-${
                                        sale.id
                                    }" class="accordion-collapse collapse" aria-labelledby="heading-${
                                        sale.id
                                    }" data-bs-parent="#transactionHistory">
                                        <div class="accordion-body">
                                            <div class="row mb-3">
                                                <div class="col-md-6">
                                                    <p class="mb-1"><strong>Order Date:</strong> ${formatDateToCustomString(
                                                        sale.order_date
                                                    )}</p>
                                                    <p class="mb-1"><strong>Due Date:</strong> ${formatDateToCustomString(
                                                        sale.due_date
                                                    )}</p>
                                                </div>
                                                <div class="col-md-6 text-end">
                                                    <p class="mb-1"><strong>Payment Type:</strong> ${
                                                        sale.payment_type ||
                                                        "N/A"
                                                    }</p>
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
                                                            sale.sales_items &&
                                                            sale.sales_items
                                                                .length > 0
                                                                ? sale.sales_items
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
                                                                    item.customer_price ||
                                                                        0
                                                                )}</td>
                                                                <td class="text-end">${formatCurrency(
                                                                    item.total ||
                                                                        0
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
                                                        sale.total -
                                                            sale.total_tax
                                                    )}</p>
                                                    <p class="mb-1"><strong>Discount:</strong> ${formatCurrency(
                                                        sale.order_discount
                                                    )}</p>
                                                    <p class="mb-1"><strong>Tax:</strong> ${formatCurrency(
                                                        sale.total_tax
                                                    )}</p>
                                                    <p class="mb-1"><strong>Grand Total:</strong> <span class="text-primary fw-bold">${formatCurrency(
                                                        sale.total
                                                    )}</span></p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                `;
                                    transactionHistory.appendChild(saleElement);
                                });
                            } else if (!append) {
                                safeToggleElement(
                                    "noTransactionsMessage",
                                    "block"
                                );
                            }

                            // Manage Load More button
                            const currentPageNum = data.sales
                                ? data.sales.current_page
                                : 1;
                            const lastPageNum = data.sales
                                ? data.sales.last_page
                                : 1;

                            if (currentPageNum < lastPageNum) {
                                safeToggleElement(
                                    "loadMoreTransactions",
                                    "block"
                                );
                            } else {
                                safeToggleElement(
                                    "loadMoreTransactions",
                                    "none"
                                );
                            }
                        }

                        
                    })
                    .catch((error) => {
                        console.error("Error loading CRM data:", error);
                        showToast(
                            "Error",
                            `Failed to load CRM data: ${error.message}`,
                            "error"
                        );
                        showErrorState("Failed to load");
                    });
            }

            // Function to load historical purchases
            // Modern version of loadHistoricalPurchases function
            // Function to load historical purchases - Modern & Simplified Version
            function loadHistoricalPurchases(id) {
                

                const historicalPurchaseContent = document.getElementById(
                    "historicalPurchaseContent"
                );

                
                

                if (historicalPurchaseContent) {
                    // Show loading state
                    historicalPurchaseContent.innerHTML =
                        '<div class="d-flex justify-content-center align-items-center py-5"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>';
                } else {
                    console.error(
                        "Element with ID 'historicalPurchaseContent' not found!"
                    );
                    return;
                }

                fetch(`/admin/customers/${id}/historical-purchases`)
                    .then((response) => {
                        if (!response.ok) {
                            throw new Error(
                                `HTTP error! status: ${response.status}`
                            );
                        }
                        return response.json();
                    })
                    .then((data) => {
                        

                        if (historicalPurchaseContent) {
                            
                            if (
                                data.historical_purchases &&
                                data.historical_purchases.length > 0
                            ) {
                                

                                // Create modern card-based layout
                                let contentHtml = `
                        <div class="row g-3">
                    `;

                                data.historical_purchases.forEach(
                                    (purchase) => {
                                        contentHtml += `
                            <div class="col-12">
                                <div class="card border-0 shadow-sm">
                                    <div class="card-body p-4">
                                        <div class="row align-items-center">
                                            <div class="col-md-6">
                                                <div class="d-flex align-items-center mb-2">
                                                    <div class="badge bg-primary-lt text-primary me-2">
                                                        ${purchase.invoice}
                                                    </div>
                                                    <span class="text-muted small">
                                                        ${formatDateToCustomString(
                                                            purchase.order_date
                                                        )}
                                                    </span>
                                                </div>
                                                <h6 class="mb-1 fw-semibold fs-5">${
                                                    purchase.product_name
                                                }</h6>
                                                <div class="text-muted small">
                                                    Quantity: <span class="fw-medium">${
                                                        purchase.quantity
                                                    }</span>
                                                </div>
                                            </div>
                                            <div class="col-md-6 text-md-end mt-3 mt-md-0">
                                                <div class="mb-1">
                                                    <span class="text-success fw-bold fs-5">${formatCurrency(
                                                        purchase.line_total
                                                    )}</span>
                                                </div>
                                                <div class="small text-muted">
                                                Customer Price:
                                                    ${formatCurrency(
                                                        purchase.price_at_purchase
                                                    )} per unit
                                                </div>
                                                ${
                                                    purchase.customer_latest_price !==
                                                    purchase.price_at_purchase
                                                        ? `<div class="small text-info">
                                                        Our Price: ${formatCurrency(
                                                            purchase.customer_latest_price
                                                        )}
                                                    </div>`
                                                        : ""
                                                }
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        `;
                                    }
                                );

                                contentHtml += `
                        </div>
                    `;


                                historicalPurchaseContent.innerHTML =
                                    contentHtml;
                                
                            } else {
                                
                                historicalPurchaseContent.innerHTML = `
                        <div class="text-center py-5">
                            <div class="mb-3">
                                <i class="ti ti-shopping-cart-off fs-1 text-muted"></i>
                            </div>
                            <h5 class="text-muted">No Purchase History</h5>
                            <p class="text-muted mb-0">This customer hasn't made any purchases yet.</p>
                        </div>
                    `;
                                
                            }
                        } else {
                            console.error(
                                "Element with ID 'historicalPurchaseContent' was null after fetch!"
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
                "purchase-history-tab"
            );
            if (historicalPurchaseTab) {
                historicalPurchaseTab.addEventListener(
                    "shown.bs.tab",
                    function (event) {
                        
                        const checkElement = document.getElementById(
                            "purchaseHistoryContent"
                        );
                        
                        if (customerId) {
                            loadProductHistory(customerId);
                        }
                    }
                );
            }

            function loadProductHistory(id) {
                
                const productHistoryContent = document.getElementById(
                    "productHistoryContent"
                );

                if (productHistoryContent) {
                    // Show loading state
                    productHistoryContent.innerHTML =
                        '<div class="d-flex justify-content-center align-items-center py-5"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>';
                } else {
                    console.error(
                        "Element with ID 'productHistoryContent' not found!"
                    );
                    return;
                }

                fetch(`/admin/customers/${id}/product-history`)
                    .then((response) => {
                        
                        if (!response.ok) {
                            throw new Error(
                                `HTTP error! status: ${response.status}`
                            );
                        }
                        return response.json();
                    })
                    .then((data) => {
                        
                        if (productHistoryContent) {
                            if (
                                data.product_history &&
                                data.product_history.length > 0
                            ) {
                                
                                // Create modern card-based layout
                                let contentHtml = `
                        <div class="accordion" id="crmProductHistoryAccordion">
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
                                    )}" data-bs-parent="#crmProductHistoryAccordion">
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

                                productHistoryContent.innerHTML = contentHtml;
                                
                            } else {
                                
                                productHistoryContent.innerHTML = `
                        <div class="text-center py-5">
                            <div class="mb-3">
                                <i class="ti ti-shopping-cart-off fs-1 text-muted"></i>
                            </div>
                            <h5 class="text-muted">No Product History</h5>
                            <p class="text-muted mb-0">This customer has no product history yet.</p>
                        </div>
                    `;
                                
                            }
                        } else {
                            console.error(
                                "Element with ID 'crmProductHistoryContent' was null after fetch!"
                            );
                        }
                    })
                    .catch((error) => {
                        console.error(
                            "Fetch error in loadProductHistory:",
                            error
                        );
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
        }
    }
});
