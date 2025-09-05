document.addEventListener("DOMContentLoaded", function () {
    // Retrieve currency settings from meta tags
    const currencySettings = {
        locale: document
            .querySelector('meta[name="currency-locale"]')
            .getAttribute("content"),
        currency_code: document
            .querySelector('meta[name="currency-code"]')
            .getAttribute("content"),
        decimal_places: parseInt(
            document
                .querySelector('meta[name="currency-decimal-places"]')
                .getAttribute("content")
        ),
    };

    // DOM Elements
    const productList = document.getElementById("productList");
    const subtotalElement = document.getElementById("subtotal");
    const finalTotalElement = document.getElementById("finalTotal");
    const productGrid = document.getElementById("productGrid");
    const productsField = document.getElementById("productsField");
    const orderDiscountInput = document.getElementById("orderDiscountInput");
    const orderDiscountTypeInput = document.getElementById(
        "orderDiscountTypeInput"
    );
    const taxInput = document.getElementById("taxInput");
    const clearCartBtn = document.getElementById("clearCart");
    const cartCountElement = document.getElementById("cartCount");
    const grandTotalInput = document.getElementById("grandTotalInput");
    const processPaymentBtn = document.getElementById("processPaymentBtn");
    const invoiceForm = document.getElementById("invoiceForm");

    // Direct input elements
    const orderDiscountElement = document.getElementById("orderDiscount");
    const discountTypeElement = document.getElementById("discountType");
    const taxRateElement = document.getElementById("taxRate");
    taxRateElement.value = "0";

    // Payment Modal Elements
    const paymentModal = new bootstrap.Modal(
        document.getElementById("paymentModal")
    );
    const modalProductList = document.getElementById("modalProductList");
    const modalSubtotal = document.getElementById("modalSubtotal");
    const modalDiscount = document.getElementById("modalDiscount");
    const modalTax = document.getElementById("modalTax");
    const modalGrandTotal = document.getElementById("modalGrandTotal");
    const paymentMethod = document.getElementById("paymentMethod");
    const amountReceived = document.getElementById("amountReceived");
    const changeAmount = document.getElementById("changeAmount");
    const cashPaymentDiv = document.getElementById("cashPaymentDiv");
    const changeRow = document.getElementById("changeRow");
    const completePaymentBtn = document.getElementById("completePaymentBtn");

    // State variables
    let products = [];
    let subtotal = 0;
    let orderDiscount = 0;
    let taxAmount = 0;
    let grandTotal = 0;

    // Check if we're returning from a successful transaction
    const urlParams = new URLSearchParams(window.location.search);
    const successParam = urlParams.get("success");

    flatpickr("#transaction_date", {
        enableTime: true,
        dateFormat: "d F Y H:i", // What user sees
        altInput: true,
        altFormat: "d F Y H:i", // Fancy alternate format
        time_24hr: true, // 24-hour format
        defaultDate: new Date(), // Auto-fill with now
        allowInput: true, // Allow typing manually
        minuteIncrement: 1, // More precise time picking
    });

    // If returning with success parameter or no cached products, start fresh
    // Otherwise load cached products
    if (successParam === "true") {
        // Clear cached products when returning from successful transaction
        localStorage.removeItem("cachedProducts");
        // Could also show a success message here if desired
    } else {
        // Only load cached products if not returning from successful transaction
        products = JSON.parse(localStorage.getItem("cachedProducts")) || [];
    }

    // Helper functions
    function formatCurrency(amount) {
        return new Intl.NumberFormat(currencySettings.locale, {
            style: "currency",
            currency: currencySettings.currency_code,
            minimumFractionDigits: currencySettings.decimal_places,
            maximumFractionDigits: currencySettings.decimal_places,
        }).format(amount);
    }

    function saveProductsToCache() {
        localStorage.setItem("cachedProducts", JSON.stringify(products));
    }

    // Calculate totals
    function calculateTotals() {
        // Calculate subtotal
        subtotal = products.reduce((sum, product) => sum + product.total, 0);

        // Get current values from inputs
        const discountValue = parseFloat(orderDiscountElement.value || 0);
        const discountType = discountTypeElement.value;
        const taxRate = parseFloat(taxRateElement.value || 0);

        // Calculate order discount
        if (discountType === "percent") {
            orderDiscount = (subtotal * discountValue) / 100;
        } else {
            orderDiscount = discountValue;
        }

        // Calculate taxable amount (after discount is applied)
        const taxableAmount = subtotal - orderDiscount;

        // Calculate tax amount
        taxAmount = taxableAmount * (taxRate / 100);

        // Calculate grand total
        grandTotal = taxableAmount + taxAmount;

        // Update display
        subtotalElement.innerText = formatCurrency(subtotal);
        finalTotalElement.innerText = formatCurrency(grandTotal);
        cartCountElement.innerText = products.length;

        // Update hidden inputs
        orderDiscountInput.value = orderDiscount;
        orderDiscountTypeInput.value = discountType;
        taxInput.value = taxAmount;
        grandTotalInput.value = grandTotal;

        // Update button state
        processPaymentBtn.disabled = products.length === 0;
    }

    // Render shopping cart list
    function renderList() {
        productList.innerHTML = "";

        if (products.length === 0) {
            const emptyMessage = document.createElement("div");
            emptyMessage.classList.add("text-center", "py-4", "text-muted");
            emptyMessage.innerHTML =
                '<i class="ti ti-shopping-cart text-muted" style="font-size: 3rem;"></i>' +
                '<p class="mt-2">Your cart is empty</p>';
            productList.appendChild(emptyMessage);
        } else {
            products.forEach((product, index) => {
                const item = document.createElement("div");
                item.classList.add(
                    "list-group-item",
                    "d-flex",
                    "justify-content-between",
                    "align-items-center",
                    "border-0",
                    "border-bottom"
                );

                // Create the product info section with editable price
                const productInfo = document.createElement("div");
                productInfo.classList.add("flex-grow-1");
                productInfo.innerHTML = `
                <strong class="d-block">${product.name}</strong>
                <div class="d-flex align-items-center mt-1">
                    <span class="text-muted me-2">${product.quantity} x</span>
                    <div class="input-group input-group-sm" style="width: 120px;">
                        <span class="input-group-text">Rp</span>
                        <input type="number" class="form-control price-input" value="${product.price}" min="0" data-index="${index}">
                    </div>
                    <span class="text-muted ms-2">/ ${product.unit}</span>
                </div>
            `;

                // Create the actions section
                const actionsSection = document.createElement("div");
                actionsSection.classList.add(
                    "d-flex",
                    "justify-content-end",
                    "align-items-center",
                    "gap-2",
                    "ms-2"
                );
                actionsSection.innerHTML = `
                <strong>${formatCurrency(product.total)}</strong>
                <div class="btn-group">
                    <button class="btn btn-sm btn-outline-success increase-product" data-index="${index}" title="Increase">
                        <i class="ti ti-plus"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-warning decrease-product" data-index="${index}" title="Decrease">
                        <i class="ti ti-minus"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-danger remove-product" data-index="${index}" title="Remove">
                        <i class="ti ti-trash"></i>
                    </button>
                </div>
            `;

                // Append both sections to the item
                item.appendChild(productInfo);
                item.appendChild(actionsSection);

                productList.appendChild(item);
            });
        }

        calculateTotals();
        productsField.value = JSON.stringify(products);
        saveProductsToCache();
    }

    // Update product price
    function updateProductPrice(index, newPrice) {
        if (isNaN(index) || index < 0 || index >= products.length) return;

        // Update the price
        products[index].price = parseFloat(newPrice);

        // Recalculate the total for this product
        products[index].total =
            products[index].quantity * products[index].price;

        // Update display
        renderList();
    }

    // Price input event listener
    productList.addEventListener("change", function (event) {
        const priceInput = event.target.closest(".price-input");

        if (priceInput) {
            const index = parseInt(priceInput.dataset.index);
            const newPrice = parseFloat(priceInput.value);

            if (!isNaN(index) && !isNaN(newPrice) && newPrice >= 0) {
                updateProductPrice(index, newPrice);
            }
        }
    });

    // Prevent form submission on Enter key in price inputs
    productList.addEventListener("keypress", function (event) {
        if (event.target.closest(".price-input") && event.key === "Enter") {
            event.preventDefault();
            event.target.blur();
        }
    });

    // Add product to cart
    function addToProductList(
        productId,
        productName,
        productPrice,
        productUnit
    ) {
        // Find if product already exists in cart
        let existingProduct = products.find((p) => p.id === productId);

        if (existingProduct) {
            existingProduct.quantity += 1;
            existingProduct.total =
                existingProduct.quantity * existingProduct.price;
        } else {
            products.push({
                id: productId,
                name: productName,
                price: parseFloat(productPrice),
                quantity: 1,
                total: parseFloat(productPrice),
                unit: productUnit,
            });
        }

        // Show quick feedback animation
        showAddToCartFeedback();

        // Play success sound
        playSuccessSound();

        // Update display
        renderList();
    }

    // Function to play a success sound
    function playSuccessSound() {
        const audio = new Audio("/audio/success.mp3");
        audio.play().catch((e) => console.error("Error playing sound:", e));
    }

    function playDeleteSound() {
        const audio = new Audio("/audio/delete.mp3");
        audio.play().catch((e) => console.error("Error playing sound:", e));
    }

    function playDecreaseSound() {
        const audio = new Audio("/audio/decrease.mp3");
        audio.play().catch((e) => console.error("Error playing sound:", e));
    }

    function playCashSound() {
        const audio = new Audio("/audio/cash.mp3");
        audio.play().catch((e) => console.error("Error playing sound:", e));
    }

    // Show feedback animation when adding to cart
    function showAddToCartFeedback() {
        const feedback = document.createElement("div");
        feedback.classList.add(
            "position-fixed",
            "top-50",
            "start-50",
            "translate-middle",
            "bg-success",
            "text-white",
            "rounded-circle",
            "p-3",
            "d-flex",
            "justify-content-center",
            "align-items-center"
        );
        feedback.style.zIndex = "1050";
        feedback.style.width = "60px";
        feedback.style.height = "60px";
        feedback.style.opacity = "0";
        feedback.style.transition = "opacity 0.3s ease";
        feedback.innerHTML =
            '<i class="ti ti-check" style="font-size: 24px;"></i>';

        document.body.appendChild(feedback);

        // Animate
        setTimeout(() => {
            feedback.style.opacity = "0.9";
            setTimeout(() => {
                feedback.style.opacity = "0";
                setTimeout(() => {
                    document.body.removeChild(feedback);
                }, 300);
            }, 500);
        }, 10);
    }

    // Clear shopping cart
    function clearCart() {
        products = [];
        renderList();
    }

    // Populate payment modal with product details
    function populatePaymentModal() {
        // Clear previous data
        modalProductList.innerHTML = "";

        // Add each product to the modal list
        products.forEach((product) => {
            const row = document.createElement("tr");
            row.innerHTML = `
            <td>${product.name}</td>
            <td class="text-center">${product.quantity} ${product.unit}</td>
            <td class="text-end">${formatCurrency(product.price)}</td>
            <td class="text-end">${formatCurrency(product.total)}</td>
        `;
            modalProductList.appendChild(row);
        });

        // Get discount type and value
        const discountValue = parseFloat(orderDiscountElement.value || 0);
        const discountType = discountTypeElement.value;
        const taxRate = parseFloat(taxRateElement.value || 0);

        // Format the discount text based on the discount type
        let discountText = "";
        if (discountType === "percent") {
            discountText = `(${discountValue}%)`;
        } else {
            discountText = "(Fixed)";
        }

        // Format the tax text
        let taxText = `(${taxRate}%)`;

        // Update the summary values with additional information
        modalSubtotal.textContent = formatCurrency(subtotal);
        modalDiscount.textContent = `${formatCurrency(
            orderDiscount
        )} ${discountText}`;
        modalTax.textContent = `${formatCurrency(taxAmount)} ${taxText}`;
        modalGrandTotal.textContent = formatCurrency(grandTotal);

        // Reset payment inputs
        amountReceived.value = "";
        changeAmount.textContent = formatCurrency(0);

        // Show the modal
        paymentModal.show();
    }

    // Calculate change amount
    function calculateChange() {
        const received = parseFloat(amountReceived.value || 0);
        const change = received - grandTotal;

        changeAmount.textContent = formatCurrency(Math.max(0, change));

        // Update complete payment button state
        completePaymentBtn.disabled =
            received < grandTotal && paymentMethod.value === "cash";
    }

    // Set exact amount
    function setExactAmount() {
        amountReceived.value = grandTotal;
        calculateChange();
    }

    // Add this to your DOM Elements section
    const exactAmountBtn = document.getElementById("exactAmountBtn");

    // Add this to your Event Listeners section
    if (exactAmountBtn) {
        exactAmountBtn.addEventListener("click", setExactAmount);
    }

    // Handle payment method change
    function handlePaymentMethodChange() {
        const isCash = paymentMethod.value === "cash";

        cashPaymentDiv.style.display = isCash ? "block" : "none";
        changeRow.style.display = isCash ? "block" : "none";
        exactAmountBtn.style.display = isCash ? "inline-block" : "none";

        // Update button state
        completePaymentBtn.disabled = isCash
            ? parseFloat(amountReceived.value || 0) < grandTotal
            : false;
    }

    // Complete the payment and submit the form
    function completePayment() {
        playCashSound();
        // Map lowercase payment method values to the format expected by the backend
        const paymentMethodMap = {
            cash: "Cash",
            card: "Card",
            transfer: "Transfer",
            ewallet: "eWallet",
        };

        // Add payment info to form
        const paymentInfoInput = document.createElement("input");
        paymentInfoInput.type = "hidden";
        paymentInfoInput.name = "payment_method";
        paymentInfoInput.value =
            paymentMethodMap[paymentMethod.value] || paymentMethod.value;
        invoiceForm.appendChild(paymentInfoInput);

        // Ensure we're using the user-inputted tax rate
        const taxRateInput = document.getElementById("taxRateInput");
        const taxRateValue = document.getElementById("taxRate").value;
        taxRateInput.value = taxRateValue;

        if (paymentMethod.value === "cash") {
            const amountReceivedInput = document.createElement("input");
            amountReceivedInput.type = "hidden";
            amountReceivedInput.name = "amount_received";
            amountReceivedInput.value = parseFloat(amountReceived.value || 0);
            invoiceForm.appendChild(amountReceivedInput);

            const changeInput = document.createElement("input");
            changeInput.type = "hidden";
            changeInput.name = "change_amount";
            changeInput.value = Math.max(
                0,
                parseFloat(amountReceived.value || 0) - grandTotal
            );
            invoiceForm.appendChild(changeInput);
        } else {
            // For non-cash payments, still provide amount_received to satisfy the validation
            const amountReceivedInput = document.createElement("input");
            amountReceivedInput.type = "hidden";
            amountReceivedInput.name = "amount_received";
            amountReceivedInput.value = grandTotal; // The total amount is considered received
            invoiceForm.appendChild(amountReceivedInput);
        }

        // Clear localStorage before submitting to prevent cached items from reappearing
        localStorage.removeItem("cachedProducts");

        // Submit the form via AJAX
        fetch(invoiceForm.action, {
            method: invoiceForm.method,
            body: new FormData(invoiceForm),
            headers: {
                "X-Requested-With": "XMLHttpRequest",
                "X-CSRF-TOKEN": document
                    .querySelector('meta[name="csrf-token"]')
                    .getAttribute("content"),
            },
        })
            .then((response) => response.json())
            .then((data) => {
                if (data.success) {
                    showToast("Success", data.message, "success");
                    paymentModal.hide();
                    clearCart(); // Clear cart after successful payment
                    // Redirect to the receipt page
                    window.location.href = `/admin/pos/receipt/${data.sale_id}`;
                } else {
                    showToast(
                        "Error",
                        data.message || "Failed to process payment.",
                        "error"
                    );
                    console.error("Payment error:", data.errors);
                }
            })
            .catch((error) => {
                console.error("Error processing payment:", error);
                showToast(
                    "Error",
                    "An error occurred while processing payment. Please check the console for details.",
                    "error"
                );
            });
    }

    // Event Listeners

    // Product grid click handler
    productGrid.addEventListener("click", function (event) {
        const productCard = event.target.closest(".product-card");
        if (!productCard) return;

        const productId = productCard.dataset.productId;
        const productName = productCard.dataset.productName;
        const productPrice = productCard.dataset.productPrice;
        const productUnit = productCard.dataset.productUnit;

        addToProductList(productId, productName, productPrice, productUnit);
    });

    // Clear cart button
    clearCartBtn.addEventListener("click", function () {
        if (products.length === 0) return;
        playDeleteSound();
        clearCart();
    });

    // Product list action handlers
    productList.addEventListener("click", function (event) {
        const removeBtn = event.target.closest(".remove-product");
        const increaseBtn = event.target.closest(".increase-product");
        const decreaseBtn = event.target.closest(".decrease-product");

        if (removeBtn) {
            const index = parseInt(removeBtn.dataset.index);
            if (!isNaN(index)) {
                playDeleteSound();
                products.splice(index, 1);
                renderList();
            }
        } else if (decreaseBtn) {
            const index = parseInt(decreaseBtn.dataset.index);
            if (!isNaN(index)) {
                if (products[index].quantity > 1) {
                    playDecreaseSound();
                    products[index].quantity -= 1;
                    products[index].total =
                        products[index].quantity * products[index].price;
                } else {
                    playDeleteSound();
                    products.splice(index, 1); // Remove if quantity reaches 0
                }
                renderList();
            }
        } else if (increaseBtn) {
            const index = parseInt(increaseBtn.dataset.index);
            if (!isNaN(index)) {
                playSuccessSound();
                products[index].quantity += 1;
                products[index].total =
                    products[index].quantity * products[index].price;
                renderList();
            }
        }
    });

    // Add event listeners for discount and tax input changes
    orderDiscountElement.addEventListener("input", calculateTotals);
    discountTypeElement.addEventListener("change", calculateTotals);
    taxRateElement.addEventListener("input", calculateTotals);

    // Search functionality
    const searchInput = document.getElementById("searchProduct");
    const productCards = document.querySelectorAll("#productGrid .col-md-4");

    if (searchInput) {
        searchInput.addEventListener("input", function () {
            const searchText = this.value.toLowerCase().trim();

            productCards.forEach((card) => {
                const productNameElement = card.querySelector(".card-title");
                if (productNameElement) {
                    const productName = productNameElement.textContent
                        .toLowerCase()
                        .trim();
                    card.style.display = productName.includes(searchText)
                        ? ""
                        : "none";
                }
            });
        });
    }

    // Process Payment button handler
    processPaymentBtn.addEventListener("click", function () {
        if (products.length === 0) {
            alert("Please add at least one product to the cart.");
            return;
        }

        populatePaymentModal();
    });

    // Payment method change handler
    paymentMethod.addEventListener("change", handlePaymentMethodChange);

    // Amount received input handler
    amountReceived.addEventListener("input", calculateChange);

    // Complete payment button handler
    completePaymentBtn.addEventListener("click", completePayment);

    // Initialize
    renderList();
    handlePaymentMethodChange();
});

/**
 * Enhanced Quick Create functionality for POS interface
 * Fixes issues with form submission and AJAX handling
 */

// Wait for document to be ready
document.addEventListener("DOMContentLoaded", function () {
    // Setup quick-create buttons
    setupQuickCreateCustomerButton();
    setupQuickCreateProductButton();

    // Setup modal handlers for quick-create forms
    setupQuickCreateCustomerForm();
    setupQuickCreateProductForm();
});

// Add quick-create button for customers
function setupQuickCreateCustomerButton() {
    const customerSelectContainer =
        document.getElementById("customer_id")?.parentElement;
    if (!customerSelectContainer) return;

    // Create button element
    const addButton = document.createElement("button");
    addButton.type = "button";
    addButton.className = "btn btn-sm btn-primary ms-2";
    addButton.innerHTML = '<i class="ti ti-plus fs-3"></i>';
    addButton.title = "Create New Customer";
    addButton.setAttribute("data-bs-toggle", "modal");
    addButton.setAttribute("data-bs-target", "#quickCreateCustomerModal");

    // Create wrapper div for select + button
    const inputGroup = document.createElement("div");
    inputGroup.className = "d-flex align-items-center";

    // Get the select element
    const selectElement = customerSelectContainer.querySelector("select");
    if (!selectElement) return;

    // Remove select from its parent
    selectElement.parentNode.removeChild(selectElement);

    // Add select and button to wrapper, then wrapper to container
    inputGroup.appendChild(selectElement);
    inputGroup.appendChild(addButton);
    customerSelectContainer.appendChild(inputGroup);
}

// Add quick-create button for products
function setupQuickCreateProductButton() {
    const searchProductContainer =
        document.getElementById("searchProduct")?.parentElement;
    if (!searchProductContainer) return;

    // Create button element
    const addButton = document.createElement("button");
    addButton.type = "button";
    addButton.className = "btn btn-primary";
    addButton.innerHTML = '<i class="ti ti-plus"></i>';
    addButton.title = "Create New Product";
    addButton.setAttribute("data-bs-toggle", "modal");
    addButton.setAttribute("data-bs-target", "#quickCreateProductModal");

    // Add button to search input group
    searchProductContainer.appendChild(addButton);
}

// Handle quick-create customer form submission
function setupQuickCreateCustomerForm() {
    const customerForm = document.getElementById("quickCreateCustomerForm");
    if (!customerForm) return;

    customerForm.addEventListener("submit", function (e) {
        e.preventDefault();

        const form = this;
        const formData = new FormData(form);

        // Get the correct URL from the form's action attribute
        const url = form.getAttribute("action");
        if (!url) {
            console.error("Form action URL is missing");
            showToast("Error", "Form configuration error", "error");
            return;
        }

        // Get CSRF token
        const csrfToken = document.querySelector(
            'meta[name="csrf-token"]'
        )?.content;
        if (!csrfToken) {
            console.error("CSRF token not found");
            showToast("Error", "Security token missing", "error");
            return;
        }

        // Submit form via AJAX
        fetch(url, {
            method: "POST",
            body: formData,
            headers: {
                "X-Requested-With": "XMLHttpRequest",
                "X-CSRF-TOKEN": csrfToken,
            },
        })
            .then((response) => {
                if (!response.ok) {
                    throw new Error(`HTTP error! Status: ${response.status}`);
                }
                return response.json();
            })
            .then((data) => {
                if (data.success) {
                    // Add new customer to dropdown
                    const customerSelect =
                        document.getElementById("customer_id");
                    if (customerSelect) {
                        const newOption = new Option(
                            data.customer.name,
                            data.customer.id
                        );
                        newOption.setAttribute(
                            "data-payment-terms",
                            data.customer.payment_terms
                        );
                        customerSelect.add(newOption);
                        customerSelect.value = data.customer.id;
                    }

                    // Close modal
                    const modal = document.getElementById(
                        "quickCreateCustomerModal"
                    );
                    if (modal) {
                        const bsModal = bootstrap.Modal.getInstance(modal);
                        if (bsModal) bsModal.hide();
                    }

                    // Reset form
                    form.reset();

                    // Show success toast
                    showToast(
                        "Success",
                        "Customer created successfully",
                        "success"
                    );
                } else {
                    // Show errors
                    showToast(
                        "Error",
                        data.message || "Failed to create customer",
                        "error"
                    );
                    console.error("Error response:", data);
                }
            })
            .catch((error) => {
                console.error("Error:", error);
                showToast(
                    "Error",
                    "An error occurred while creating the customer",
                    "error"
                );
            });
    });
}

// Handle quick-create product form submission
function setupQuickCreateProductForm() {
    const productForm = document.getElementById("quickCreateProductForm");
    if (!productForm) return;

    productForm.addEventListener("submit", function (e) {
        e.preventDefault();

        const form = this;
        const formData = new FormData(form);

        // Get the correct URL from the form's action attribute
        const url = form.getAttribute("action");
        if (!url) {
            console.error("Form action URL is missing");
            showToast("Error", "Form configuration error", "error");
            return;
        }

        // Get CSRF token
        const csrfToken = document.querySelector(
            'meta[name="csrf-token"]'
        )?.content;
        if (!csrfToken) {
            console.error("CSRF token not found");
            showToast("Error", "Security token missing", "error");
            return;
        }

        // Submit form via AJAX
        fetch(url, {
            method: "POST",
            body: formData,
            headers: {
                "X-Requested-With": "XMLHttpRequest",
                "X-CSRF-TOKEN": csrfToken,
            },
        })
            .then((response) => {
                if (!response.ok) {
                    throw new Error(`HTTP error! Status: ${response.status}`);
                }
                return response.json();
            })
            .then((data) => {
                if (data.success) {
                    // Add new product to the product grid
                    addProductToGrid(data.product);

                    // Close modal
                    const modal = document.getElementById(
                        "quickCreateProductModal"
                    );
                    if (modal) {
                        const bsModal = bootstrap.Modal.getInstance(modal);
                        if (bsModal) bsModal.hide();
                    }

                    // Reset form
                    form.reset();

                    // Show success toast
                    showToast(
                        "Success",
                        "Product created successfully",
                        "success"
                    );
                } else {
                    // Show errors
                    showToast(
                        "Error",
                        data.message || "Failed to create product",
                        "error"
                    );
                    console.error("Error response:", data);
                }
            })
            .catch((error) => {
                console.error("Error:", error);
                showToast(
                    "Error",
                    "An error occurred while creating the product",
                    "error"
                );
            });
    });
}

function addProductToGrid(product) {
    const productGrid = document.getElementById("productGrid");
    if (!productGrid) return;

    // Create new product column element
    const productCol = document.createElement("div");
    productCol.className = "col-md-4 mb-2";

    const productCard = document.createElement("div");
    productCard.className = "card product-card h-100 border hover-shadow";

    // Determine image source
    const imageSrc = product.image_url || "/img/default_placeholder.png";

    // Image container
    const imageContainer = document.createElement("div");
    imageContainer.className =
        "card-img-top position-relative product-image-container";

    const img = document.createElement("img");
    img.src = imageSrc;
    img.alt = product.name;
    img.className = "img-fluid product-image";
    img.setAttribute("data-product-id", product.id);
    img.setAttribute("data-product-name", product.name);
    img.setAttribute("data-product-price", product.selling_price);
    img.setAttribute("data-product-unit", product.unit_name || "pcs");

    imageContainer.appendChild(img);

    // Card body
    const cardBody = document.createElement("div");
    cardBody.className = "card-body p-2 text-center";

    const title = document.createElement("h5");
    title.className = "card-title fs-4 mb-1";
    title.textContent = product.name;

    const price = document.createElement("p");
    price.className = "card-text fs-4";
    price.textContent = formatCurrency(product.selling_price);

    cardBody.appendChild(title);
    cardBody.appendChild(price);

    productCard.appendChild(imageContainer);
    productCard.appendChild(cardBody);
    productCol.appendChild(productCard);

    // Insert the new product at the beginning of the grid instead of appending to the end
    if (productGrid.firstChild) {
        productGrid.insertBefore(productCol, productGrid.firstChild);
    } else {
        productGrid.appendChild(productCol);
    }

    // Add a subtle highlight effect to the new product
    setTimeout(() => {
        productCol.style.transition = "background-color 1s ease";
        productCol.style.backgroundColor = "#e8f4ff";

        setTimeout(() => {
            productCol.style.backgroundColor = "";
        }, 1500);
    }, 100);
}

// Format currency helper function
function formatCurrency(amount) {
    return new Intl.NumberFormat(currencySettings.locale, {
        style: "currency",
        currency: currencySettings.currency_code,
        minimumFractionDigits: currencySettings.decimal_places,
        maximumFractionDigits: currencySettings.decimal_places,
    }).format(amount);
}

document.addEventListener("DOMContentLoaded", function () {
    const hasExpiryCheckbox = document.getElementById("has_expiry");
    const expiryDateField = document.querySelector(".expiry-date-field");

    hasExpiryCheckbox.addEventListener("change", function () {
        expiryDateField.style.display = this.checked ? "block" : "none";
    });
});
