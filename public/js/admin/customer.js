document.addEventListener("DOMContentLoaded", function () {
    const editCustomerModal = document.getElementById("editCustomerModal");
    const createCustomerModal = document.getElementById("createCustomerModal");
    const crmCustomerModal = document.getElementById("crmCustomerModal");

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
                    bsModal.hide();
                    // Listen for the 'hidden.bs.modal' event to ensure the modal is fully closed
                    bsModal._element.addEventListener('hidden.bs.modal', function handler() {
                        bsModal._element.removeEventListener('hidden.bs.modal', handler); // Remove the listener
                        form.reset(); // Clear form fields
                        // Explicitly remove any remaining modal backdrops
                        const backdrops = document.querySelectorAll('.modal-backdrop');
                        backdrops.forEach(backdrop => backdrop.remove());
                        location.reload();
                    });
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
    if (editCustomerModal) {
        editCustomerModal.addEventListener("show.bs.modal", function (event) {
            const button = event.relatedTarget;

            if (!button) return; // Prevent errors if button is null

            const customerId = button.getAttribute("data-id") || "";
            const customerName = button.getAttribute("data-name") || "";
            const customerAddress = button.getAttribute("data-address") || "";
            const customerPhone = button.getAttribute("data-phone_number") || "";
            const customerPayment = button.getAttribute("data-payment_terms") || "";

            document.getElementById("customerId").value = customerId;
            document.getElementById("customerNameEdit").value = customerName;
            document.getElementById("customerAddressEdit").value = customerAddress;
            document.getElementById("customerPhoneEdit").value = customerPhone;
            document.getElementById("customerPaymentTermsEdit").value = customerPayment;

            const routeBase = document.getElementById("updateRouteBase").value;
            document.getElementById("editCustomerForm").action = routeBase + "/" + customerId;
        });

        // Add submit listener for edit form
        const editCustomerForm = document.getElementById("editCustomerForm");
        if (editCustomerForm) {
            editCustomerForm.addEventListener("submit", (event) => handleFormSubmission(event, editCustomerModal));
        }
    }

    // Add submit listener for create form
    const createCustomerForm = document.getElementById("createCustomerForm");
    if (createCustomerForm) {
        createCustomerForm.addEventListener("submit", (event) => handleFormSubmission(event, createCustomerModal, true));
    }

    // CRM Modal Logic
    if (crmCustomerModal) {
        let currentPage = 1;
        let customerId = null;
        let lastPage = 1;

        crmCustomerModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            if (!button) return;

            customerId = button.getAttribute('data-id');
            currentPage = 1;
            // Clear previous data and show loading state
            document.getElementById('crmCustomerName').textContent = 'Loading...';
            document.getElementById('crmCustomerEmail').textContent = 'Loading...';
            document.getElementById('crmCustomerPhone').textContent = 'Loading...';
            document.getElementById('crmLifetimeValue').textContent = 'Loading...';
            document.getElementById('crmFavoriteCategory').textContent = 'Loading...';
            document.getElementById('crmMemberSince').textContent = 'Loading...';
            document.getElementById('crmLastPurchase').textContent = 'Loading...';
            document.getElementById('interactionTimeline').innerHTML = '<div class="text-center py-3"><div class="spinner-border text-primary" role="status"></div></div>';
            document.getElementById('transactionHistory').innerHTML = '<div class="text-center py-3"><div class="spinner-border text-primary" role="status"></div></div>';
            document.getElementById('loadMoreTransactions').style.display = 'none';
            document.getElementById('noInteractionsMessage').style.display = 'none';
            document.getElementById('noTransactionsMessage').style.display = 'none';

            loadCrmData(customerId, currentPage);
        });

        document.getElementById('loadMoreTransactions').addEventListener('click', function () {
            currentPage++;
            loadCrmData(customerId, currentPage, true);
        });

        document.getElementById('interactionForm').addEventListener('submit', function (event) {
            event.preventDefault();
            const form = event.target;
            const formData = new FormData(form);

            fetch(`/admin/customers/${customerId}/interactions`, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                },
            })
            .then(response => response.json())
            .then(data => {
                if (data.id) {
                    showToast('Success', 'Interaction added.', 'success');
                    const timeline = document.getElementById('interactionTimeline');
                    const newInteraction = document.createElement('div');
                    newInteraction.classList.add('list-group-item', 'list-group-item-action');
                    newInteraction.innerHTML = `
                        <div class="d-flex w-100 justify-content-between">
                            <h5 class="mb-1">${data.type.charAt(0).toUpperCase() + data.type.slice(1)} on ${new Date(data.interaction_date).toLocaleDateString('id-ID')}</h5>
                            <small class="text-muted">by ${data.user.name}</small>
                        </div>
                        <p class="mb-1">${data.notes}</p>
                    `;
                    timeline.prepend(newInteraction);
                    document.getElementById('noInteractionsMessage').style.display = 'none';
                    form.reset();
                    form.querySelector('input[name="interaction_date"]').value = new Date().toISOString().slice(0,10);
                } else {
                    showToast('Error', 'Failed to add interaction.', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('Error', 'An error occurred.', 'error');
            });
        });

        function formatCurrency(value) {
            return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(parseFloat(value) || 0);
        }

        function getStatusBadgeHtml(status, dueDate) {
            let badgeClass = '';
            let statusText = status;

            const today = new Date();
            const due = new Date(dueDate);
            today.setHours(0,0,0,0);
            due.setHours(0,0,0,0);

            if (status === 'Paid') {
                badgeClass = 'bg-success';
            } else if (status === 'Unpaid' && due < today) {
                badgeClass = 'bg-danger';
                statusText = 'Overdue';
            } else if (status === 'Unpaid') {
                badgeClass = 'bg-warning';
            } else {
                badgeClass = 'bg-secondary';
            }
            return `<span class="badge ${badgeClass}">${statusText}</span>`;
        }

        function loadCrmData(id, page, append = false) {
            fetch(`/admin/customers/${id}/crm-details?page=${page}`)
                .then(response => response.json())
                .then(data => {
                    console.log(data);
                    // Populate Overview tab
                    document.getElementById('crmCustomerName').textContent = data.customer.name;
                    document.getElementById('crmCustomerEmail').textContent = data.customer.email || 'N/A';
                    document.getElementById('crmCustomerPhone').textContent = data.customer.phone_number || 'N/A';
                    document.getElementById('crmLifetimeValue').textContent = formatCurrency(data.lifetimeValue);
                    document.getElementById('crmFavoriteCategory').textContent = data.favoriteCategory || 'N/A';
                    document.getElementById('crmMemberSince').textContent = new Date(data.customer.created_at).toLocaleDateString('id-ID');
                    document.getElementById('crmLastPurchase').textContent = data.lastPurchaseDate ? new Date(data.lastPurchaseDate).toLocaleDateString('id-ID') : 'N/A';

                    // Populate Interactions
                    const interactionTimeline = document.getElementById('interactionTimeline');
                    if (!append) {
                        interactionTimeline.innerHTML = ''; // Clear for fresh load
                    }
                    if (data.customer.interactions.length > 0) {
                        document.getElementById('noInteractionsMessage').style.display = 'none';
                        data.customer.interactions.forEach(interaction => {
                            const interactionElement = document.createElement('div');
                            interactionElement.classList.add('list-group-item', 'list-group-item-action');
                            interactionElement.innerHTML = `
                                <div class="d-flex w-100 justify-content-between">
                                    <h5 class="mb-1">${interaction.type.charAt(0).toUpperCase() + interaction.type.slice(1)} on ${new Date(interaction.interaction_date).toLocaleDateString('id-ID')}</h5>
                                    <small class="text-muted">by ${interaction.user.name}</small>
                                </div>
                                <p class="mb-1">${interaction.notes}</p>
                            `;
                            interactionTimeline.appendChild(interactionElement);
                        });
                    } else if (!append) {
                        document.getElementById('noInteractionsMessage').style.display = 'block';
                    }

                    // Populate Historical Transactions
                    const transactionHistory = document.getElementById('transactionHistory');
                    if (!append) {
                        transactionHistory.innerHTML = ''; // Clear for fresh load
                    }

                    lastPage = data.sales.last_page; // Update lastPage

                    if (data.sales.data.length > 0) {
                        document.getElementById('noTransactionsMessage').style.display = 'none';
                        data.sales.data.forEach(sale => {
                            const saleElement = document.createElement('div');
                            saleElement.classList.add('accordion-item');
                            saleElement.innerHTML = `
                                <h2 class="accordion-header" id="heading-${sale.id}">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-${sale.id}" aria-expanded="false" aria-controls="collapse-${sale.id}">
                                        <div class="d-flex justify-content-between w-100 pe-3">
                                            <div>
                                                Invoice #${sale.invoice_number} - ${new Date(sale.created_at).toLocaleDateString('id-ID')}
                                                ${getStatusBadgeHtml(sale.status, sale.due_date)}
                                            </div>
                                            <div class="fw-bold">${formatCurrency(sale.grand_total)}</div>
                                        </div>
                                    </button>
                                </h2>
                                <div id="collapse-${sale.id}" class="accordion-collapse collapse" aria-labelledby="heading-${sale.id}" data-bs-parent="#transactionHistory">
                                    <div class="accordion-body">
                                        <div class="row mb-3">
                                            <div class="col-md-6">
                                                <p class="mb-1"><strong>Order Date:</strong> ${new Date(sale.order_date).toLocaleDateString('id-ID')}</p>
                                                <p class="mb-1"><strong>Due Date:</strong> ${new Date(sale.due_date).toLocaleDateString('id-ID')}</p>
                                                <p class="mb-1"><strong>Payment Type:</strong> ${sale.payment_type || 'N/A'}</p>
                                            </div>
                                            <div class="col-md-6 text-end">
                                                <p class="mb-1"><strong>Subtotal:</strong> ${formatCurrency(sale.sub_total)}</p>
                                                <p class="mb-1"><strong>Discount:</strong> ${formatCurrency(sale.order_discount)}</p>
                                                <p class="mb-1"><strong>Tax:</strong> ${formatCurrency(sale.tax)}</p>
                                                <p class="mb-1"><strong>Grand Total:</strong> <span class="text-primary fw-bold">${formatCurrency(sale.grand_total)}</span></p>
                                            </div>
                                        </div>
                                        <h6>Items:</h6>
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
                                                    ${sale.items.map(item => `
                                                        <tr>
                                                            <td>${item.product ? item.product.name : 'N/A'}</td>
                                                            <td class="text-center">${item.quantity}</td>
                                                            <td class="text-end">${formatCurrency(item.unit_price)}</td>
                                                            <td class="text-end">${formatCurrency(item.quantity * item.unit_price)}</td>
                                                        </tr>
                                                    `).join('')}
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            `;
                            transactionHistory.appendChild(saleElement);
                        });
                    } else if (!append) {
                        document.getElementById('noTransactionsMessage').style.display = 'block';
                    }

                    // Manage Load More button visibility
                    if (data.sales.current_page < data.sales.last_page) {
                        document.getElementById('loadMoreTransactions').style.display = 'block';
                    } else {
                        document.getElementById('loadMoreTransactions').style.display = 'none';
                    }
                })
                .catch(error => {
                    console.error('Error loading CRM data:', error);
                    showToast('Error', 'Failed to load CRM data.', 'error');
                    document.getElementById('interactionTimeline').innerHTML = '<p class="text-danger">Failed to load interactions.</p>';
                    document.getElementById('transactionHistory').innerHTML = '<p class="text-danger">Failed to load transactions.</p>';
                });
        }
    }
});
