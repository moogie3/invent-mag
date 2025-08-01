document.addEventListener("DOMContentLoaded", function () {
    const pageBody = document.querySelector(".page-body");
    const pipelineSelect = document.getElementById("pipelineSelect");
    const pipelineBoard = document.getElementById("pipeline-board");
    const newOpportunityForm = document.getElementById("newOpportunityForm");
    const newOpportunityPipelineId = document.getElementById(
        "newOpportunityPipelineId"
    );
    const newOpportunityStageId = document.getElementById(
        "newOpportunityStageId"
    );
    const opportunityCustomerSelect = document.getElementById(
        "opportunityCustomer"
    );
    const newPipelineForm = document.getElementById("newPipelineForm");
    const pipelinesListContainer = document.getElementById(
        "pipelinesListContainer"
    );
    const editPipelineForm = document.getElementById("editPipelineForm");
    const pipelineStagesContainer = document.getElementById(
        "pipelineStagesContainer"
    );
    const newStageForm = document.getElementById("newStageForm");
    const newStagePipelineId = document.getElementById("newStagePipelineId");
    const editOpportunityForm = document.getElementById("editOpportunityForm");
    const editOpportunityCustomerSelect = document.getElementById(
        "editOpportunityCustomer"
    );

    

    const CSRF_TOKEN = document
        .querySelector('meta[name="csrf-token"]')
        .getAttribute("content");
    const SALES_PIPELINE_ROUTES = {
        pipelinesIndex: "/admin/sales-pipeline/pipelines",
        pipelinesStore: "/admin/sales-pipeline/pipelines",
        pipelinesBaseUrl: "/admin/sales-pipeline/pipelines",
        stagesBaseUrl: "/admin/sales-pipeline/stages",
        opportunitiesIndex: "/admin/sales-pipeline/opportunities",
        opportunitiesStore: "/admin/sales-pipeline/opportunities",
        opportunitiesBaseUrl: "/admin/sales-pipeline/opportunities",
        customerIndex: "/admin/customer",
        productIndex: "/admin/product/search?q=", // New route for products
    };

    const newOpportunityItemsContainer = document.getElementById(
        "newOpportunityItemsContainer"
    );
    const addNewOpportunityItemBtn = document.getElementById(
        "addNewOpportunityItem"
    );
    const newOpportunityTotalAmountInput = document.getElementById(
        "newOpportunityTotalAmount"
    );

    const editOpportunityItemsContainer = document.getElementById(
        "editOpportunityItemsContainer"
    );
    const editNewOpportunityItemBtn = document.getElementById(
        "editNewOpportunityItem"
    );
    const editOpportunityTotalAmountInput = document.getElementById(
        "editOpportunityTotalAmount"
    );

    // Initialize Flatpickr for date fields
    let newOpportunityExpectedCloseDateFlatpickr;
    const newOpportunityDateElement = document.getElementById("opportunityExpectedCloseDate");
    if (newOpportunityDateElement) {
        newOpportunityExpectedCloseDateFlatpickr = flatpickr(newOpportunityDateElement, {
            dateFormat: "Y-m-d",
            altInput: true,
            altFormat: "d F Y",
            allowInput: true,
        });
    }

    let editOpportunityExpectedCloseDateFlatpickr;
    let allPipelines = [];
    let allCustomers = [];

    

    let allProducts = []; // New array for products

    let currencySettings = {
        symbol: "$",
        decimalPlaces: 2,
        decimalSeparator: ".",
        thousandSeparator: ",",
    };

    try {
        const initialDataContainer = document.querySelector(
            ".card-body[data-initial-pipelines]"
        );
        const pipelinesData = initialDataContainer.dataset.initialPipelines;
        const customersData = initialDataContainer.dataset.initialCustomers;

        currencySettings.symbol =
            initialDataContainer.dataset.currencySymbol || "$";
        currencySettings.decimalPlaces = parseInt(
            initialDataContainer.dataset.decimalPlaces || 2
        );
        currencySettings.decimalSeparator =
            initialDataContainer.dataset.decimalSeparator || ".";
        currencySettings.thousandSeparator =
            initialDataContainer.dataset.thousandSeparator || ",";

        if (pipelinesData && pipelinesData.trim()) {
            allPipelines = JSON.parse(pipelinesData);
        }

        if (customersData && customersData.trim()) {
            allCustomers = JSON.parse(customersData);
        }

        console.log("Parsed pipelines:", allPipelines);
        console.log("Parsed customers:", allCustomers);
        console.log("Currency Settings:", currencySettings);
    } catch (error) {
        console.error("Error parsing initial data:", error);
        window.showToast(
            "Error",
            "Error loading initial data. Please refresh the page.",
            "error"
        );
        return;
    }

    // Helper function to format currency in JavaScript
    function formatCurrencyJs(amount) {
        let number = parseFloat(amount);
        if (isNaN(number)) {
            return amount; // Return original if not a valid number
        }

        const parts = number
            .toFixed(currencySettings.decimalPlaces)
            .split(".");
        let integerPart = parts[0];
        let decimalPart = parts.length > 1 ? parts[1] : "";

        integerPart = integerPart.replace(
            /\B(?=(\d{3})+(?!\d))/g,
            currencySettings.thousandSeparator
        );

        let formattedAmount = integerPart;
        if (currencySettings.decimalPlaces > 0) {
            formattedAmount += currencySettings.decimalSeparator + decimalPart;
        }

        return currencySettings.symbol + " " + formattedAmount;
    }

    // Initial data rendering
    async function initializeData() {
        // Made async to fetch products
        if (allPipelines.length > 0) {
            renderPipelinesSelect();
        } else {
            console.warn("No pipelines found");
        }

        renderPipelinesList();

        if (allCustomers.length > 0) {
            populateCustomerSelect(opportunityCustomerSelect);
            populateCustomerSelect(editOpportunityCustomerSelect);
        } else {
            console.warn("No customers found");
        }

        // Fetch products
        try {
            const productsResponse = await fetch(
                SALES_PIPELINE_ROUTES.productIndex
            );
            if (!productsResponse.ok) {
                throw new Error("Failed to fetch products");
            }
            allProducts = await productsResponse.json();
            console.log("Fetched products:", allProducts);
        } catch (error) {
            console.error("Error fetching products:", error);
            window.showToast(
                "Error",
                "Failed to load product data. Please refresh the page.",
                "error"
            );
        }
    }

    // Re-fetch Pipelines and Customers (for updates after initial load)
    async function fetchData() {
        try {
            const pipelinesResponse = await fetch(
                SALES_PIPELINE_ROUTES.pipelinesIndex
            );
            if (!pipelinesResponse.ok) {
                throw new Error("Failed to fetch pipelines");
            }
            allPipelines = await pipelinesResponse.json();

            // Re-render relevant parts after fetching updated data
            renderPipelinesSelect(false); // Don't automatically reload the board
            renderPipelinesList();

            console.log("Data refreshed successfully");
        } catch (error) {
            console.error("Error re-fetching data:", error);
            window.showToast(
                "Error",
                "Failed to re-fetch data. Please try again.",
                "error"
            );
        }
    }

    function renderPipelinesSelect(shouldLoadBoard = true) {
        if (!pipelineSelect) return;

        pipelineSelect.innerHTML = '<option value="">Select Pipeline</option>';

        if (allPipelines.length === 0) {
            const option = document.createElement("option");
            option.value = "";
            option.textContent = "No pipelines available";
            option.disabled = true;
            pipelineSelect.appendChild(option);
            return;
        }

        allPipelines.forEach((pipeline) => {
            const option = document.createElement("option");
            option.value = pipeline.id;
            option.textContent = pipeline.name;
            pipelineSelect.appendChild(option);
        });

        // Select default pipeline
        const defaultPipeline =
            allPipelines.find((p) => p.is_default) || allPipelines[0];
        if (defaultPipeline) {
            pipelineSelect.value = defaultPipeline.id;
            if (shouldLoadBoard) {
                loadPipelineBoard(defaultPipeline.id);
            }
        }
    }

    async function loadPipelineBoard(pipelineId) {
        if (!pipelineBoard) return;

        pipelineBoard.innerHTML = "";
        const selectedPipeline = allPipelines.find((p) => p.id == pipelineId);
        if (!selectedPipeline) return;

        try {
            const cacheBuster = new Date().getTime();
            const opportunitiesResponse = await fetch(
                `${SALES_PIPELINE_ROUTES.opportunitiesIndex}?pipeline_id=${pipelineId}&_=${cacheBuster}`
            );
            if (!opportunitiesResponse.ok) {
                throw new Error("Failed to fetch opportunities");
            }
            const responseData = await opportunitiesResponse.json();
            const opportunities = responseData.opportunities;
            const totalPipelineValue = responseData.total_pipeline_value;

            // Update the total pipeline value display
            const pipelineValueElement =
                document.getElementById("pipelineValue");
            if (pipelineValueElement) {
                pipelineValueElement.textContent =
                    formatCurrencyJs(totalPipelineValue);
            }

            selectedPipeline.stages
                .sort((a, b) => a.position - b.position)
                .forEach((stage) => {
                    const stageOpportunities = opportunities.filter(
                        (opp) => opp.pipeline_stage_id === stage.id
                    );
                    const stageColumn = document.createElement("div");
                    stageColumn.className = "col-md-3";
                    stageColumn.innerHTML = `
                <div class="card card-stacked">
                    <div class="card-header">
                        <h3 class="card-title">${stage.name} (${
                stageOpportunities.length
            })</h3>
                    </div>
                    <div class="card-body p-2 stage-column" data-stage-id="${
                stage.id
            }" style="min-height: 150px;">
                        <!-- Opportunities will be dragged here -->
                    </div>
                </div>
            `;
                    const opportunitiesContainer =
                        stageColumn.querySelector(".stage-column");
                    stageOpportunities.forEach((opportunity) => {
                        opportunitiesContainer.appendChild(
                            createOpportunityCard(opportunity)
                        );
                    });
                    pipelineBoard.appendChild(stageColumn);

                    // Initialize Sortable if available
                    if (typeof Sortable !== "undefined") {
                        new Sortable(opportunitiesContainer, {
                            group: "opportunities",
                            animation: 150,
                            onEnd: function (evt) {
                                const opportunityId =
                                    evt.item.dataset.opportunityId;
                                const oldStageId = evt.from.dataset.stageId;
                                const newStageId = evt.to.dataset.stageId;
                                if (oldStageId !== newStageId) {
                                    moveOpportunity(
                                        opportunityId,
                                        newStageId,
                                        oldStageId
                                    );
                                }
                            },
                        });
                    }
                });
        } catch (error) {
            console.error("Error loading pipeline board:", error);
            pipelineBoard.innerHTML =
                '<div class="alert alert-danger">Error loading pipeline board</div>';
        }
    }

    function createOpportunityCard(opportunity) {
        const card = document.createElement("div");
        card.className = "card card-sm mb-2 opportunity-card";
        card.dataset.opportunityId = opportunity.id;

        const customerName = opportunity.customer
            ? opportunity.customer.name
            : "Unknown Customer";
        const amount = opportunity.amount
            ? formatCurrencyJs(opportunity.amount)
            : "No amount";
        const expectedCloseDate = opportunity.expected_close_date
            ? new Date(opportunity.expected_close_date).toLocaleDateString('en-GB', { day: 'numeric', month: 'long', year: 'numeric' })
            : "No date";

        card.innerHTML = `
            <div class="card-body">
                <h4 class="card-title">${opportunity.name}</h4>
                <p class="card-text">
                    <strong>Customer:</strong> ${customerName}<br>
                    <strong>Amount:</strong> ${amount}<br>
                    <strong>Expected Close:</strong> ${expectedCloseDate}<br>
                    <strong>Status:</strong> <span class="badge ${getStatusColor(
                opportunity.status
            )}">${
                opportunity.status.charAt(0).toUpperCase() +
                opportunity.status.slice(1)
            }</span>
                </p>
                <div class="btn-group btn-group-sm" role="group">
                    <button type="button" class="btn btn-outline-primary btn-sm edit-opportunity-btn" 
                            data-opportunity-id="${opportunity.id}" ${opportunity.status === 'converted' ? 'disabled' : ''}>
                        <i class="ti ti-edit"></i> Edit
                    </button>
                    <button type="button" class="btn btn-outline-danger btn-sm delete-opportunity-btn"
                            data-opportunity-id="${opportunity.id}">
                        <i class="ti ti-trash"></i> Delete
                    </button>
                    ${
                        opportunity.status === "won" && !opportunity.sales_id
                            ? `<button type="button" class="btn btn-success btn-sm convert-opportunity-btn" data-opportunity-id="${opportunity.id}"><i class="ti ti-check"></i> Convert to Sales Order</button>`
                            : ""
                    }
                </div>
            </div>
        `;

        return card;
    }

    function getStatusColor(status) {
        switch (status) {
            case "open":
                return "badge-outline text-blue";
            case "won":
                return "badge-outline text-green";
            case "lost":
                return "badge-outline text-red";
            case "converted":
                return "badge-outline text-teal";
            default:
                return "badge-outline text-gray";
        }
    }

    function populateCustomerSelect(selectElement) {
        if (!selectElement) return;

        selectElement.innerHTML = '<option value="">Select Customer</option>';
        allCustomers.forEach((customer) => {
            const option = document.createElement("option");
            option.value = customer.id;
            option.textContent = customer.name;
            selectElement.appendChild(option);
        });
    }

    function renderPipelinesList() {
        if (!pipelinesListContainer) return;

        pipelinesListContainer.innerHTML = "";

        if (allPipelines.length === 0) {
            pipelinesListContainer.innerHTML =
                '<p class="text-muted">No pipelines available</p>';
            return;
        }

        allPipelines.forEach((pipeline) => {
            const pipelineCard = document.createElement("div");
            pipelineCard.className = "card mb-3";
            pipelineCard.innerHTML = `
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col">
                            <h5 class="card-title">${pipeline.name} ${
                pipeline.is_default
                    ? '<span class="badge bg-primary">Default</span>'
                    : ""
            }</h5>
                            <p class="card-text">${
                pipeline.description || "No description"
            }</p>
                            <small class="text-muted">Stages: ${
                pipeline.stages ? pipeline.stages.length : 0
            }</small>
                        </div>
                        <div class="col-auto">
                            <div class="btn-group" role="group">
                                <button type="button" class="btn btn-outline-primary btn-sm edit-pipeline-btn"
                                        data-pipeline-id="${pipeline.id}">
                                    <i class="ti ti-edit"></i> Edit
                                </button>
                                <button type="button" class="btn btn-outline-danger btn-sm delete-pipeline-btn"
                                        data-pipeline-id="${pipeline.id}">
                                    <i class="ti ti-trash"></i> Delete
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            pipelinesListContainer.appendChild(pipelineCard);
        });
    }

    function renderPipelineStages(pipelineId) {
        if (!pipelineStagesContainer) return;

        const pipeline = allPipelines.find((p) => p.id == pipelineId);
        if (!pipeline || !pipeline.stages) {
            pipelineStagesContainer.innerHTML =
                '<p class="text-muted">No stages available</p>';
            return;
        }

        pipelineStagesContainer.innerHTML = "";

        pipeline.stages
            .sort((a, b) => a.position - b.position)
            .forEach((stage) => {
                const stageCard = document.createElement("div");
                stageCard.className = "card mb-2";
                stageCard.innerHTML = `
                <div class="card-body py-2">
                    <div class="row align-items-center">
                        <div class="col">
                            <strong>${stage.name}</strong>
                            <small class="text-muted">Position: ${
                stage.position
            }</small>
                            ${
                                stage.is_closed
                                    ? '<span class="badge bg-secondary ms-2">Closed Stage</span>'
                                    : ""
                            }
                        </div>
                        <div class="col-auto">
                            <button type="button" class="btn btn-outline-danger btn-sm delete-stage-btn"
                                    data-stage-id="${stage.id}">
                                <i class="ti ti-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            `;
                pipelineStagesContainer.appendChild(stageCard);
            });
    }

    // Helper function to create a product item row
    function createProductItemRow(item = {}, index, containerId) {
        const itemDiv = document.createElement("div");
        itemDiv.className = "row g-2 align-items-end mb-2 product-item-row";
        itemDiv.dataset.index = index;

        const productId = item.product_id || "";
        const quantity = item.quantity || "";
        const price = parseFloat(item.price || 0).toFixed(currencySettings.decimalPlaces);

        let productOptions = '<option value="">Select Product</option>';
        allProducts.forEach((product) => {
            const selected = product.id == productId ? "selected" : "";
            productOptions += `<option value="${product.id}" data-price="${
                product.selling_price || 0
            }" data-stock="${product.stock_quantity || 0}" ${selected}>${product.name} (Stock: ${product.stock_quantity || 0})</option>`;
        });

        itemDiv.innerHTML = `
            <div class="col-md-5">
                <label for="${containerId}-product-${index}" class="form-label">Product ${
            index + 1
        }</label>
                <select class="form-select product-select" id="${containerId}-product-${index}" name="items[${index}][product_id]" required>
                    ${productOptions}
                </select>
            </div>
            <div class="col-md-3">
                <label for="${containerId}-quantity-${index}" class="form-label">Quantity</label>
                <input type="number" class="form-control quantity-input" id="${containerId}-quantity-${index}" name="items[${index}][quantity]" value="${quantity}" min="1" max="${item.product ? item.product.stock_quantity : ''}" required>
            </div>
            <div class="col-md-3">
                <label for="${containerId}-price-${index}" class="form-label">Price</label>
                <input type="number" class="form-control price-input" id="${containerId}-price-${index}" name="items[${index}][price]" value="${price}" step="0.01" min="0" required>
            </div>
            <div class="col-md-1">
                <button type="button" class="btn btn-danger btn-icon remove-item-btn">
                    <i class="ti ti-trash"></i>
                </button>
            </div>
        `;

        // Add event listeners for quantity and price changes
        const quantityInput = itemDiv.querySelector(".quantity-input");
        const priceInput = itemDiv.querySelector(".price-input");
        const productSelect = itemDiv.querySelector(".product-select");

        productSelect.addEventListener("change", function () {
            const selectedOption = this.options[this.selectedIndex];
            const productPrice = parseFloat(selectedOption.dataset.price || 0);
            const productStock = parseFloat(selectedOption.dataset.stock || 0);
            priceInput.value = productPrice.toFixed(
                currencySettings.decimalPlaces
            );
            quantityInput.max = productStock; // Set max quantity to stock
            if (parseFloat(quantityInput.value) > productStock) {
                quantityInput.value = productStock; // Adjust quantity if it exceeds stock
            }
            calculateTotalAmount(containerId);
        });

        quantityInput.addEventListener("input", () => {
            const maxStock = parseFloat(quantityInput.max);
            if (parseFloat(quantityInput.value) > maxStock) {
                quantityInput.value = maxStock;
                window.showToast("Warning", `Quantity cannot exceed available stock (${maxStock}).`, "warning");
            }
            calculateTotalAmount(containerId);
        });
        priceInput.addEventListener("input", () =>
            calculateTotalAmount(containerId)
        );
        itemDiv
            .querySelector(".remove-item-btn")
            .addEventListener("click", function () {
                itemDiv.remove();
                calculateTotalAmount(containerId);
                updateProductItemLabels(containerId);
            });

        return itemDiv;
    }

    function updateProductItemLabels(containerId) {
        const container = document.getElementById(containerId);
        if (!container) return;

        const itemRows = container.querySelectorAll(".product-item-row");
        itemRows.forEach((row, index) => {
            const newIndex = index;
            row.dataset.index = newIndex;

            const productLabel = row.querySelector(
                `label[for^='${containerId}-product-']`
            );
            if (productLabel) {
                productLabel.textContent = `Product ${newIndex + 1}`;
                productLabel.setAttribute(
                    "for",
                    `${containerId}-product-${newIndex}`
                );
            }

            const productSelect = row.querySelector(".product-select");
            if (productSelect) {
                productSelect.id = `${containerId}-product-${newIndex}`;
                productSelect.name = `items[${newIndex}][product_id]`;
            }

            const quantityLabel = row.querySelector(
                `label[for^='${containerId}-quantity-']`
            );
            const quantityInput = row.querySelector(".quantity-input");
            if (quantityInput) {
                quantityInput.id = `${containerId}-quantity-${newIndex}`;
                quantityInput.name = `items[${newIndex}][quantity]`;
                if (quantityLabel)
                    quantityLabel.setAttribute(
                        "for",
                        `${containerId}-quantity-${newIndex}`
                    );
            }

            const priceLabel = row.querySelector(
                `label[for^='${containerId}-price-']`
            );
            const priceInput = row.querySelector(".price-input");
            if (priceInput) {
                priceInput.id = `${containerId}-price-${newIndex}`;
                priceInput.name = `items[${newIndex}][price]`;
                if (priceLabel)
                    priceLabel.setAttribute(
                        "for",
                        `${containerId}-price-${newIndex}`
                    );
            }
        });
    }

    // Helper function to calculate total amount
    function calculateTotalAmount(containerId) {
        let total = 0;
        const container = document.getElementById(containerId);
        const itemRows = container.querySelectorAll(".product-item-row");
        itemRows.forEach((row) => {
            const quantity =
                parseFloat(row.querySelector(".quantity-input").value) || 0;
            const price =
                parseFloat(row.querySelector(".price-input").value) || 0;
            total += quantity * price;
        });

        if (containerId === "newOpportunityItemsContainer") {
            newOpportunityTotalAmountInput.value = formatCurrencyJs(total);
        } else if (containerId === "editOpportunityItemsContainer") {
            editOpportunityTotalAmountInput.value = formatCurrencyJs(total);
        }
    }

    // Event Listeners
    pipelineSelect?.addEventListener("change", function () {
        const selectedPipelineId = this.value;
        if (selectedPipelineId) {
            loadPipelineBoard(selectedPipelineId);
        }
    });

    // Add New Opportunity Item Button
    addNewOpportunityItemBtn?.addEventListener("click", function () {
        const index = newOpportunityItemsContainer.children.length;
        newOpportunityItemsContainer.appendChild(
            createProductItemRow({}, index, "newOpportunityItemsContainer")
        );
        calculateTotalAmount("newOpportunityItemsContainer");
    });

    // Add Edit Opportunity Item Button
    editNewOpportunityItemBtn?.addEventListener("click", function () {
        const index = editOpportunityItemsContainer.children.length;
        editOpportunityItemsContainer.appendChild(
            createProductItemRow({}, index, "editOpportunityItemsContainer")
        );
        calculateTotalAmount("editOpportunityItemsContainer");
    });

    // Fixed New Opportunity Form Handler
    newOpportunityForm?.addEventListener("submit", async function (e) {
        e.preventDefault();

        const selectedPipelineId = pipelineSelect.value;

        // Debug logging
        console.log("Selected Pipeline ID:", selectedPipelineId);
        console.log("Selected Pipeline ID type:", typeof selectedPipelineId);
        console.log("All Pipelines:", allPipelines);

        // Enhanced pipeline finding with better type handling
        const selectedPipeline = allPipelines.find((p) => {
            // Convert both to strings for comparison to handle type mismatches
            return String(p.id) === String(selectedPipelineId);
        });

        console.log("Found Pipeline:", selectedPipeline);

        if (!selectedPipeline) {
            window.showToast("Error", "Please select a pipeline first.", "error");
            return;
        }

        // Additional debugging for stages
        console.log("Pipeline stages:", selectedPipeline.stages);
        console.log(
            "Stages length:",
            selectedPipeline.stages ? selectedPipeline.stages.length : 0
        );

        if (!selectedPipeline.stages || selectedPipeline.stages.length === 0) {
            window.showToast(
                "Error",
                "The selected pipeline has no stages. Please add stages to the pipeline first.",
                "error"
            );
            return;
        }

        // Set pipeline and first stage
        newOpportunityPipelineId.value = selectedPipelineId;
        const firstStage = selectedPipeline.stages.sort(
            (a, b) => a.position - b.position
        )[0];
        newOpportunityStageId.value = firstStage.id;

        const formData = new FormData(this);
        const data = Object.fromEntries(formData);

        // Collect items data
        const items = [];
        newOpportunityItemsContainer
            .querySelectorAll(".product-item-row")
            .forEach((row, index) => {
                items.push({
                    product_id: row.querySelector(".product-select").value,
                    quantity: row.querySelector(".quantity-input").value,
                    price: row.querySelector(".price-input").value,
                });
            });
        data.items = items;

        // Debug logging
        console.log("Form data being sent:", data);

        try {
            const response = await fetch(
                SALES_PIPELINE_ROUTES.opportunitiesStore,
                {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": CSRF_TOKEN,
                    },
                    body: JSON.stringify(data),
                }
            );

            if (!response.ok) {
                const errorData = await response.json().catch(() => ({}));
                console.error("Server response:", errorData);
                throw new Error(
                    `Failed to create opportunity: ${response.status}`
                );
            }

            const opportunity = await response.json();

            // Close modal and refresh board
            bootstrap.Modal.getInstance(
                document.getElementById("newOpportunityModal")
            ).hide();
            this.reset();
            newOpportunityItemsContainer.innerHTML = ""; // Clear items
            newOpportunityTotalAmountInput.value = formatCurrencyJs(0); // Reset total
            loadPipelineBoard(selectedPipelineId);

            window.showToast("Success", "Opportunity created successfully!", "success");
        } catch (error) {
            console.error("Error creating opportunity:", error);
            window.showToast(
                "Error",
                "Failed to create opportunity. Please try again.",
                "error"
            );
        }
    });

    // New Pipeline Form
    newPipelineForm?.addEventListener("submit", async function (e) {
        e.preventDefault();

        const formData = new FormData(this);
        const data = Object.fromEntries(formData);
        data.is_default = formData.has("is_default");

        try {
            const response = await fetch(SALES_PIPELINE_ROUTES.pipelinesStore, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": CSRF_TOKEN,
                },
                body: JSON.stringify(data),
            });

            if (!response.ok) {
                throw new Error("Failed to create pipeline");
            }

            const pipeline = await response.json();

            // Close modal and refresh data
            bootstrap.Modal.getInstance(
                document.getElementById("managePipelinesModal")
            ).hide();
            await fetchData();
            this.reset();

            window.showToast("Success", "Pipeline created successfully!", "success");
        } catch (error) {
            console.error("Error creating pipeline:", error);
            window.showToast(
                "Error",
                "Failed to create pipeline. Please try again.",
                "error"
            );
        }
    });

    // Edit Pipeline Form
    editPipelineForm?.addEventListener("submit", async function (e) {
        e.preventDefault();

        const pipelineId = document.getElementById("editPipelineId").value;
        const formData = new FormData(this);
        const data = Object.fromEntries(formData);
        data.is_default = formData.has("is_default");

        try {
            const response = await fetch(
                `${SALES_PIPELINE_ROUTES.pipelinesBaseUrl}/${pipelineId}`,
                {
                    method: "PUT",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": CSRF_TOKEN,
                    },
                    body: JSON.stringify(data),
                }
            );

            if (!response.ok) {
                throw new Error("Failed to update pipeline");
            }

            // Close modal and refresh data
            bootstrap.Modal.getInstance(
                document.getElementById("editPipelineModal")
            ).hide();
            await fetchData();

            window.showToast("Success", "Pipeline updated successfully!", "success");
        } catch (error) {
            console.error("Error updating pipeline:", error);
            window.showToast(
                "Error",
                "Failed to update pipeline. Please try again.",
                "error"
            );
        }
    });

    newStageForm?.addEventListener("submit", async function (e) {
        e.preventDefault();

        const pipelineId = newStagePipelineId.value;

        if (!pipelineId) {
            window.showToast(
                "Error",
                "Pipeline ID is missing. Please close and reopen the modal.",
                "error"
            );
            return;
        }

        const formData = new FormData(this);
        const data = Object.fromEntries(formData);
        data.is_closed = formData.has("is_closed");

        if (!data.name) {
            window.showToast("Error", "Please fill in all required fields.", "error");
            return;
        }

        try {
            const response = await fetch(
                `${SALES_PIPELINE_ROUTES.pipelinesBaseUrl}/${pipelineId}/stages`,
                {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": CSRF_TOKEN,
                    },
                    body: JSON.stringify(data),
                }
            );

            if (!response.ok) {
                const errorData = await response.json().catch(() => ({}));
                throw new Error(
                    `Failed to create stage: ${
                        errorData.message || response.status
                    }`
                );
            }

            // THE FIX: Force a complete refresh of the pipeline data from the server
            await fetchData();

            // Re-render the main pipeline board with the new, correct stage order
            loadPipelineBoard(pipelineId);

            // Also re-render the stages list inside the modal for consistency
            const updatedPipeline = allPipelines.find((p) => p.id == pipelineId);
            if (updatedPipeline) {
                renderPipelineStages(updatedPipeline);
            }

            this.reset();

            window.showToast("Success", "Stage created successfully!", "success");
        } catch (error) {
            console.error("Error creating stage:", error);
            window.showToast("Error", `Failed to create stage. Please try again.`, "error");
        }
    });

    // Edit Opportunity Form
    editOpportunityForm?.addEventListener("submit", async function (e) {
        e.preventDefault();

        const opportunityId =
            document.getElementById("editOpportunityId").value;
        const formData = new FormData(this);
        const data = Object.fromEntries(formData.entries());

        // Manually add pipeline and stage IDs from the hidden fields
        data.sales_pipeline_id = document.getElementById('editOpportunityPipelineId').value;
        data.pipeline_stage_id = document.getElementById('editOpportunityStageId').value;

        // Collect items data
        const items = [];
        editOpportunityItemsContainer
            .querySelectorAll(".product-item-row")
            .forEach((row, index) => {
                items.push({
                    product_id: row.querySelector(".product-select").value,
                    quantity: row.querySelector(".quantity-input").value,
                    price: row.querySelector(".price-input").value,
                });
            });
        data.items = items;

        try {
            const response = await fetch(
                `${SALES_PIPELINE_ROUTES.opportunitiesBaseUrl}/${opportunityId}`,
                {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": CSRF_TOKEN,
                    },
                    body: JSON.stringify(data),
                }
            );

            if (!response.ok) {
                throw new Error("Failed to update opportunity");
            }

            // Close modal and refresh board
            const currentPipelineId = pipelineSelect.value;
            bootstrap.Modal.getInstance(
                document.getElementById("editOpportunityModal")
            ).hide();
            await fetchData(); // Refresh pipelines and stages
            pipelineSelect.value = currentPipelineId; // Restore the selected pipeline
            loadPipelineBoard(currentPipelineId); // Refresh the board with the correct pipeline

            window.showToast("Success", "Opportunity updated successfully!", "success");

            
        } catch (error) {
            console.error("Error updating opportunity:", error);
            window.showToast(
                "Error",
                "Failed to update opportunity. Please try again.",
                "error"
            );
        }
    });

    // Event delegation for dynamic buttons
    document.addEventListener("click", async function (e) {
        // Edit Pipeline Button
        if (e.target.closest(".edit-pipeline-btn")) {
            const pipelineId =
                e.target.closest(".edit-pipeline-btn").dataset.pipelineId;
            const pipeline = allPipelines.find((p) => p.id == pipelineId);
            if (pipeline) {
                // Populate form fields
                document.getElementById("editPipelineId").value = pipeline.id;
                document.getElementById("editPipelineName").value =
                    pipeline.name;
                document.getElementById("editPipelineDescription").value =
                    pipeline.description || "";
                document.getElementById("editPipelineIsDefault").checked =
                    pipeline.is_default || false;

                // Set pipeline ID for new stages
                newStagePipelineId.value = pipeline.id;

                // Render existing stages
                renderPipelineStages(pipelineId);

                // Show modal
                new bootstrap.Modal(
                    document.getElementById("editPipelineModal")
                ).show();
            }
        }

        // Delete Pipeline Button
        if (e.target.closest(".delete-pipeline-btn")) {
            const pipelineId = e.target.closest(".delete-pipeline-btn").dataset
                .pipelineId;
            const confirmed = await showConfirmationModal(
                "Delete Pipeline",
                "Are you sure you want to delete this pipeline?"
            );
            if (confirmed) {
                try {
                    const response = await fetch(
                        `${SALES_PIPELINE_ROUTES.pipelinesBaseUrl}/${pipelineId}`,
                        {
                            method: "DELETE",
                            headers: {
                                "X-CSRF-TOKEN": CSRF_TOKEN,
                            },
                        }
                    );

                    if (!response.ok) {
                        throw new Error("Failed to delete pipeline");
                    }

                    await fetchData();
                    window.showToast("Success", "Pipeline deleted successfully!", "success");
                } catch (error) {
                    console.error("Error deleting pipeline:", error);
                    window.showToast(
                        "Error",
                        "Failed to delete pipeline. Please try again.",
                        "error"
                    );
                }
            }
        }

        // Delete Stage Button
        if (e.target.closest(".delete-stage-btn")) {
            const stageId =
                e.target.closest(".delete-stage-btn").dataset.stageId;
            const confirmed = await showConfirmationModal(
                "Delete Stage",
                "Are you sure you want to delete this stage?"
            );
            if (confirmed) {
                try {
                    const response = await fetch(
                        `${SALES_PIPELINE_ROUTES.stagesBaseUrl}/${stageId}`,
                        {
                            method: "DELETE",
                            headers: {
                                "X-CSRF-TOKEN": CSRF_TOKEN,
                            },
                        }
                    );

                    if (!response.ok) {
                        throw new Error("Failed to delete stage");
                    }

                    await fetchData();
                    const pipelineId = newStagePipelineId.value;
                    if (pipelineId) {
                        renderPipelineStages(pipelineId);
                    }

                    window.showToast("Success", "Stage deleted successfully!", "success");
                } catch (error) {
                    console.error("Error deleting stage:", error);
                    window.showToast(
                        "Error",
                        "Failed to delete stage. Please try again.",
                        "error"
                    );
                }
            }
        }

        // Edit Opportunity Button
        if (e.target.closest(".edit-opportunity-btn")) {
            const opportunityId = e.target.closest(".edit-opportunity-btn")
                .dataset.opportunityId;

            try {
                const response = await fetch(
                    `${SALES_PIPELINE_ROUTES.opportunitiesBaseUrl}/${opportunityId}`
                );
                if (!response.ok) {
                    const errorData = await response.json().catch(() => ({}));
                    console.error(
                        `Server error fetching opportunity: Status ${response.status} - ${response.statusText}`,
                        errorData
                    );
                    throw new Error("Failed to fetch opportunity");
                }
                const opportunity = await response.json();

                document.getElementById("editOpportunityId").value =
                    opportunity.id;
                document.getElementById("editOpportunityName").value =
                    opportunity.name;
                document.getElementById("editOpportunityPipelineId").value =
                    opportunity.sales_pipeline_id;
                document.getElementById("editOpportunityStageId").value =
                    opportunity.pipeline_stage_id;
                document.getElementById("editOpportunityPipelineId").value =
                    opportunity.sales_pipeline_id;
                document.getElementById("editOpportunityStageId").value =
                    opportunity.pipeline_stage_id;
                document.getElementById("editOpportunityCustomer").value =
                    opportunity.customer_id;
                document.getElementById("editOpportunityStatus").value = opportunity.status;
                // document.getElementById("editOpportunityAmount").value = // Removed direct amount input
                //     opportunity.amount || "";
                // Initialize Flatpickr for the edit form if not already initialized
                if (!editOpportunityExpectedCloseDateFlatpickr) {
                    editOpportunityExpectedCloseDateFlatpickr = flatpickr(document.getElementById("editOpportunityExpectedCloseDate"), {
                        dateFormat: "Y-m-d",
                        altInput: true,
                        altFormat: "d F Y",
                        allowInput: true,
                    });
                }

                // Set the date for the Flatpickr instance
                if (opportunity.expected_close_date) {
                    editOpportunityExpectedCloseDateFlatpickr.setDate(opportunity.expected_close_date);
                } else {
                    editOpportunityExpectedCloseDateFlatpickr.clear();
                }

                // Populate items
                editOpportunityItemsContainer.innerHTML = ""; // Clear existing items
                if (opportunity.items && Array.isArray(opportunity.items)) {
                    opportunity.items.forEach((item, index) => {
                        editOpportunityItemsContainer.appendChild(
                            createProductItemRow(
                                item,
                                index,
                                "editOpportunityItemsContainer"
                            )
                        );
                    });
                }
                calculateTotalAmount("editOpportunityItemsContainer");

                new bootstrap.Modal(
                    document.getElementById("editOpportunityModal")
                ).show();
            } catch (error) {
                console.error("Error in edit opportunity fetch:", error);
                window.showToast(
                    "Error",
                    "Failed to fetch opportunity details. Please try again.",
                    "error"
                );
            }
        }

        // Delete Opportunity Button
        if (e.target.closest(".delete-opportunity-btn")) {
            const opportunityId = e.target.closest(".delete-opportunity-btn")
                .dataset.opportunityId;
            const confirmed = await showConfirmationModal(
                "Delete Opportunity",
                "Are you sure you want to delete this opportunity?"
            );
            if (confirmed) {
                try {
                    const response = await fetch(
                        `${SALES_PIPELINE_ROUTES.opportunitiesBaseUrl}/${opportunityId}`,
                        {
                            method: "DELETE",
                            headers: {
                                "X-CSRF-TOKEN": CSRF_TOKEN,
                            },
                        }
                    );

                    if (!response.ok) {
                        throw new Error("Failed to delete opportunity");
                    }

                    loadPipelineBoard(pipelineSelect.value);
                    window.showToast(
                        "Success",
                        "Opportunity deleted successfully!",
                        "success"
                    );
                } catch (error) {
                    console.error("Error deleting opportunity:", error);
                    window.showToast(
                        "Error",
                        "Failed to delete opportunity. Please try again.",
                        "error"
                    );
                }
            }
        }

        // Convert Opportunity Button
        if (e.target.closest(".convert-opportunity-btn")) {
            const opportunityId = e.target.closest(".convert-opportunity-btn").dataset.opportunityId;
            const convertModal = new bootstrap.Modal(document.getElementById('convertOpportunityModal'));
            document.getElementById('convertOpportunityId').value = opportunityId;
            document.getElementById('convertOpportunityForm').action = `${SALES_PIPELINE_ROUTES.opportunitiesBaseUrl}/${opportunityId}/convert`;
            convertModal.show();
        }
    });

    // Move opportunity between stages
    async function moveOpportunity(opportunityId, newStageId, oldStageId) {
        try {
            const response = await fetch(
                `${SALES_PIPELINE_ROUTES.opportunitiesBaseUrl}/${opportunityId}/move`,
                {
                    method: "PUT",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": CSRF_TOKEN,
                    },
                    body: JSON.stringify({
                        pipeline_stage_id: newStageId,
                    }),
                }
            );

            if (!response.ok) {
                throw new Error("Failed to move opportunity");
            }

            console.log("Opportunity moved successfully");

            // Update stage counts without reloading the board
            updateStageCount(oldStageId, -1);
            updateStageCount(newStageId, 1);
        } catch (error) {
            console.error("Error moving opportunity:", error);
            window.showToast(
                "Error",
                "Failed to move opportunity. Please try again.",
                "error"
            );
            // Reload the board to revert the visual change
            loadPipelineBoard(pipelineSelect.value);
        }
    }

    function updateStageCount(stageId, change) {
        const stageColumn = pipelineBoard.querySelector(`[data-stage-id="${stageId}"]`);
        if (stageColumn) {
            const cardHeader = stageColumn.closest(".card-stacked").querySelector(".card-header");
            if (cardHeader) {
                const title = cardHeader.querySelector(".card-title");
                if (title) {
                    const currentCount = parseInt(title.textContent.match(/\((\d+)\)/)[1], 10);
                    const newCount = currentCount + change;
                    const stageName = title.textContent.split("(")[0].trim();
                    title.textContent = `${stageName} (${newCount})`;
                }
            }
        }
    }

    // Show confirmation modal
    function showConfirmationModal(title, body) {
        return new Promise((resolve) => {
            const modal = new bootstrap.Modal(document.getElementById('confirmationModal'));
            document.getElementById('confirmationModalTitle').textContent = title;
            document.getElementById('confirmationModalBody').textContent = body;
            document.getElementById('confirmationModalConfirm').onclick = () => {
                modal.hide();
                resolve(true);
            };
            document.getElementById('confirmationModal').addEventListener('hidden.bs.modal', () => {
                resolve(false);
            }, { once: true });
            modal.show();
        });
    }

    // Initialize the application
    initializeData();

    // Initialize Flatpickr for date fields
    flatpickr("#opportunityExpectedCloseDate", {
        dateFormat: "Y-m-d",
        altInput: true,
        altFormat: "d F Y",
        allowInput: true,
    });
    flatpickr("#editOpportunityExpectedCloseDate", {
        dateFormat: "Y-m-d",
        altInput: true,
        altFormat: "d F Y",
        allowInput: true,
    });
});

document.addEventListener('DOMContentLoaded', function () {
    const convertOpportunityForm = document.getElementById('convertOpportunityForm');

    if (convertOpportunityForm) {
        convertOpportunityForm.addEventListener('submit', function (e) {
            e.preventDefault();
            const form = e.target;
            const url = form.action;
            const formData = new FormData(form);
            const opportunityId = document.getElementById('convertOpportunityId').value;

            fetch(url, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json',
                },
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                const convertModal = bootstrap.Modal.getInstance(document.getElementById('convertOpportunityModal'));
                convertModal.hide();

                if (data.type === 'success') {
                    window.showToast('Success', data.message, 'success');
                    
                    // Find the opportunity card and update it
                    const opportunityCard = document.querySelector(`.opportunity-card[data-opportunity-id='${opportunityId}']`);
                    if (opportunityCard) {
                        // Update status badge
                        const statusBadge = opportunityCard.querySelector('.badge');
                        if (statusBadge) {
                            statusBadge.textContent = 'Converted';
                            statusBadge.classList.remove('badge-success-lt');
                            statusBadge.classList.add('badge-info-lt');
                        }

                        // Remove the convert button
                        const convertButton = opportunityCard.querySelector('.convert-opportunity-btn');
                        if (convertButton) {
                            convertButton.remove();
                        }
                    }
                } else {
                    window.showToast('Error', data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                const convertModal = bootstrap.Modal.getInstance(document.getElementById('convertOpportunityModal'));
                convertModal.hide();
                window.showToast('Error', 'An unexpected error occurred.', 'error');
            });
        });
    }
});