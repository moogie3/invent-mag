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

    // Confirmation Modal Helper
    const confirmationModal = new bootstrap.Modal(document.getElementById('confirmationModal'));
    const confirmationModalConfirmBtn = document.getElementById('confirmationModalConfirm');
    const confirmationModalTitle = document.getElementById('confirmationModalTitle');
    const confirmationModalBody = document.getElementById('confirmationModalBody');

    function showConfirmationModal(title, body) {
        return new Promise((resolve) => {
            confirmationModalTitle.textContent = title;
            confirmationModalBody.innerHTML = body;

            const confirmHandler = () => {
                resolve(true);
                confirmationModal.hide();
                confirmationModalConfirmBtn.removeEventListener('click', confirmHandler);
            };

            const cancelHandler = () => {
                resolve(false);
                confirmationModal.hide();
                confirmationModalConfirmBtn.removeEventListener('click', confirmHandler);
            };

            confirmationModalConfirmBtn.addEventListener('click', confirmHandler);
            document.getElementById('confirmationModal').addEventListener('hidden.bs.modal', cancelHandler, { once: true });

            confirmationModal.show();
        });
    }

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

    // Parse initial data with error handling
    let allPipelines = [];
    let allCustomers = [];
    let allProducts = []; // New array for products

    try {
        const pipelinesData = pageBody.dataset.initialPipelines;
        const customersData = pageBody.dataset.initialCustomers;

        if (pipelinesData && pipelinesData.trim()) {
            allPipelines = JSON.parse(pipelinesData);
        }

        if (customersData && customersData.trim()) {
            allCustomers = JSON.parse(customersData);
        }

        console.log("Parsed pipelines:", allPipelines);
        console.log("Parsed customers:", allCustomers);
    } catch (error) {
        console.error("Error parsing initial data:", error);
        showToast('Error', 'Error loading initial data. Please refresh the page.', 'error');
        return;
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
            showToast('Error', 'Failed to load product data. Please refresh the page.', 'error');
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
            renderPipelinesSelect();
            renderPipelinesList();

            console.log("Data refreshed successfully");
        } catch (error) {
            console.error("Error re-fetching data:", error);
            showToast('Error', 'Failed to re-fetch data. Please try again.', 'error');
        }
    }

    function renderPipelinesSelect() {
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
            loadPipelineBoard(defaultPipeline.id);
        }
    }

    async function loadPipelineBoard(pipelineId) {
        if (!pipelineBoard) return;

        pipelineBoard.innerHTML = "";
        const selectedPipeline = allPipelines.find((p) => p.id == pipelineId);
        if (!selectedPipeline) return;

        try {
            const opportunitiesResponse = await fetch(
                `${SALES_PIPELINE_ROUTES.opportunitiesIndex}?pipeline_id=${pipelineId}`
            );
            if (!opportunitiesResponse.ok) {
                throw new Error("Failed to fetch opportunities");
            }
            const opportunities = await opportunitiesResponse.json();

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
                        <h3 class="card-title">${stage.name} (${stageOpportunities.length})</h3>
                    </div>
                    <div class="card-body p-2 stage-column" data-stage-id="${stage.id}" style="min-height: 150px;">
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
                                const newStageId = evt.to.dataset.stageId;
                                moveOpportunity(opportunityId, newStageId);
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
        const currencySymbol = pageBody.dataset.currencySymbol || "$"; // Get currency symbol from data attribute
        const amount = opportunity.amount
            ? new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(opportunity.amount)
            : "No amount";
        const expectedCloseDate = opportunity.expected_close_date
            ? new Date(opportunity.expected_close_date).toLocaleDateString()
            : "No date";

        card.innerHTML = `
            <div class="card-body">
                <h4 class="card-title">${opportunity.name}</h4>
                <p class="card-text">
                    <strong>Customer:</strong> ${customerName}<br>
                    <strong>Amount:</strong> ${amount}<br>
                    <strong>Expected Close:</strong> ${expectedCloseDate}<br>
                    <strong>Status:</strong> <span class="badge badge-${getStatusColor(
                        opportunity.status
                    )}-lt">${opportunity.status.charAt(0).toUpperCase() + opportunity.status.slice(1)}</span>
                </p>
                <div class="btn-group btn-group-sm" role="group">
                    <button type="button" class="btn btn-outline-primary btn-sm edit-opportunity-btn"
                            data-opportunity-id="${opportunity.id}">
                        <i class="ti ti-edit"></i> Edit
                    </button>
                    <button type="button" class="btn btn-outline-danger btn-sm delete-opportunity-btn"
                            data-opportunity-id="${opportunity.id}">
                        <i class="ti ti-trash"></i> Delete
                    </button>
                    ${
                        opportunity.status === "won"
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
                return "primary";
            case "won":
                return "success";
            case "lost":
                return "danger";
            default:
                return "secondary";
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
        const price = item.price || "";

        let productOptions = '<option value="">Select Product</option>';
        allProducts.forEach((product) => {
            const selected = product.id == productId ? "selected" : "";
            productOptions += `<option value="${product.id}" data-price="${
                product.price || 0
            }" ${selected}>${product.name}</option>`;
        });

        itemDiv.innerHTML = `
            <div class="col-md-5">
                <label for="${containerId}-product-${index}" class="form-label">Product</label>
                <select class="form-select product-select" id="${containerId}-product-${index}" name="items[${index}][product_id]" required>
                    ${productOptions}
                </select>
            </div>
            <div class="col-md-3">
                <label for="${containerId}-quantity-${index}" class="form-label">Quantity</label>
                <input type="number" class="form-control quantity-input" id="${containerId}-quantity-${index}" name="items[${index}][quantity]" value="${quantity}" min="1" required>
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
            priceInput.value = productPrice.toFixed(2);
            calculateTotalAmount(containerId);
        });

        quantityInput.addEventListener("input", () =>
            calculateTotalAmount(containerId)
        );
        priceInput.addEventListener("input", () =>
            calculateTotalAmount(containerId)
        );
        itemDiv
            .querySelector(".remove-item-btn")
            .addEventListener("click", function () {
                itemDiv.remove();
                calculateTotalAmount(containerId);
            });

        return itemDiv;
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
            newOpportunityTotalAmountInput.value = total.toFixed(2);
        } else if (containerId === "editOpportunityItemsContainer") {
            editOpportunityTotalAmountInput.value = total.toFixed(2);
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
            showToast('Error', 'Please select a pipeline first.', 'error');
            return;
        }

        // Additional debugging for stages
        console.log("Pipeline stages:", selectedPipeline.stages);
        console.log(
            "Stages length:",
            selectedPipeline.stages ? selectedPipeline.stages.length : 0
        );

        if (!selectedPipeline.stages || selectedPipeline.stages.length === 0) {
            showToast('Error', 'The selected pipeline has no stages. Please add stages to the pipeline first.', 'error');
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
            newOpportunityTotalAmountInput.value = "0.00"; // Reset total
            loadPipelineBoard(selectedPipelineId);

            showToast('Success', 'Opportunity created successfully!', 'success');
        } catch (error) {
            console.error("Error creating opportunity:", error);
            showToast('Error', 'Failed to create opportunity. Please try again.', 'error');
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

            showToast('Success', 'Pipeline created successfully!', 'success');
        } catch (error) {
            console.error("Error creating pipeline:", error);
            showToast('Error', 'Failed to create pipeline. Please try again.', 'error');
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

            showToast('Success', 'Pipeline updated successfully!', 'success');
        } catch (error) {
            console.error("Error updating pipeline:", error);
            showToast('Error', 'Failed to update pipeline. Please try again.', 'error');
        }
    });

    newStageForm?.addEventListener("submit", async function (e) {
        e.preventDefault();

        const pipelineId = newStagePipelineId.value;

        if (!pipelineId) {
            showToast('Error', 'Pipeline ID is missing. Please close and reopen the modal.', 'error');
            return;
        }

        const formData = new FormData(this);
        const data = Object.fromEntries(formData);
        data.is_closed = formData.has("is_closed");

        if (!data.name) {
            showToast('Error', 'Please fill in all required fields.', 'error');
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
                throw new Error(`Failed to create stage: ${errorData.message || response.status}`);
            }

            // THE FIX: Force a complete refresh of the pipeline data from the server
            await fetchData();

            // Re-render the main pipeline board with the new, correct stage order
            loadPipelineBoard(pipelineId);

            // Also re-render the stages list inside the modal for consistency
            const updatedPipeline = allPipelines.find(p => p.id == pipelineId);
            if (updatedPipeline) {
                renderPipelineStages(updatedPipeline);
            }

            this.reset();

            showToast('Success', 'Stage created successfully!', 'success');

        } catch (error) {
            console.error("Error creating stage:", error);
            showToast('Error', `Failed to create stage. Please try again.`, 'error');
        }
    });

    // Edit Opportunity Form
    editOpportunityForm?.addEventListener("submit", async function (e) {
        e.preventDefault();

        const opportunityId =
            document.getElementById("editOpportunityId").value;
        const formData = new FormData(this);
        const data = Object.fromEntries(formData);

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
                    method: "PUT",
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
            bootstrap.Modal.getInstance(
                document.getElementById("editOpportunityModal")
            ).hide();
            loadPipelineBoard(pipelineSelect.value);

            alert("Opportunity updated successfully!");
        } catch (error) {
            console.error("Error updating opportunity:", error);
            alert("Failed to update opportunity. Please try again.");
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
            const confirmed = await showConfirmationModal('Delete Pipeline', 'Are you sure you want to delete this pipeline?');
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
                    showToast('Success', 'Pipeline deleted successfully!', 'success');
                } catch (error) {
                    console.error("Error deleting pipeline:", error);
                    alert("Failed to delete pipeline. Please try again.");
                }
            }
        }

        // Delete Stage Button
        if (e.target.closest(".delete-stage-btn")) {
            const stageId =
                e.target.closest(".delete-stage-btn").dataset.stageId;
            const confirmed = await showConfirmationModal('Delete Stage', 'Are you sure you want to delete this stage?');
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

                    showToast('Success', 'Stage deleted successfully!', 'success');
                } catch (error) {
                    console.error("Error deleting stage:", error);
                    alert("Failed to delete stage. Please try again.");
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
                    throw new Error("Failed to fetch opportunity");
                }
                const opportunity = await response.json();

                document.getElementById("editOpportunityId").value =
                    opportunity.id;
                document.getElementById("editOpportunityName").value =
                    opportunity.name;
                document.getElementById("editOpportunityCustomer").value =
                    opportunity.customer_id;
                // document.getElementById("editOpportunityAmount").value = // Removed direct amount input
                //     opportunity.amount || "";
                document.getElementById(
                    "editOpportunityExpectedCloseDate"
                ).value = opportunity.expected_close_date || "";
                document.getElementById("editOpportunityDescription").value =
                    opportunity.description || "";
                document.getElementById("editOpportunityStatus").value =
                    opportunity.status;
                document.getElementById("editOpportunityPipelineId").value =
                    opportunity.sales_pipeline_id;
                document.getElementById("editOpportunityStageId").value =
                    opportunity.pipeline_stage_id;

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
                console.error("Error fetching opportunity:", error);
                alert("Failed to fetch opportunity details. Please try again.");
            }
        }

        // Delete Opportunity Button
        if (e.target.closest(".delete-opportunity-btn")) {
            const opportunityId = e.target.closest(".delete-opportunity-btn")
                .dataset.opportunityId;
            const confirmed = await showConfirmationModal('Delete Opportunity', 'Are you sure you want to delete this opportunity?');
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
                    showToast('Success', 'Opportunity deleted successfully!', 'success');
                } catch (error) {
                    console.error("Error deleting opportunity:", error);
                    alert("Failed to delete opportunity. Please try again.");
                }
            }
        }

        // Convert Opportunity Button
        if (e.target.closest(".convert-opportunity-btn")) {
            const opportunityId = e.target.closest(".convert-opportunity-btn")
                .dataset.opportunityId;
            const confirmed = await showConfirmationModal('Convert Opportunity', 'Are you sure you want to convert this opportunity to a Sales Order?');
            if (confirmed) {
                try {
                    const response = await fetch(
                        `${SALES_PIPELINE_ROUTES.opportunitiesBaseUrl}/${opportunityId}/convert`,
                        {
                            method: "POST",
                            headers: {
                                "X-CSRF-TOKEN": CSRF_TOKEN,
                            },
                        }
                    );

                    if (!response.ok) {
                        const errorData = await response
                            .json()
                            .catch(() => ({}));
                        throw new Error(
                            errorData.message ||
                                "Failed to convert opportunity."
                        );
                    }

                    const result = await response.json();
                    loadPipelineBoard(pipelineSelect.value);
                    showToast('Success', result.message, 'success');
                } catch (error) {
                    console.error("Error converting opportunity:", error);
                    alert("Failed to convert opportunity: " + error.message);
                }
            }
        }
    });

    // Move opportunity between stages
    async function moveOpportunity(opportunityId, newStageId) {
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
        } catch (error) {
            console.error("Error moving opportunity:", error);
            alert("Failed to move opportunity. Please try again.");
            // Reload the board to revert the visual change
            loadPipelineBoard(pipelineSelect.value);
        }
    }

    // Initialize the application
    initializeData();
});
