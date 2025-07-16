<div class="modal modal-blur fade" id="srmSupplierModal" tabindex="-1" aria-labelledby="srmSupplierModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="srmSupplierModalLabel"><i class="ti ti-users me-2"></i>Supplier Relationship
                    Management</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                    aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <ul class="nav nav-tabs nav-fill border-0 flex-column flex-md-row" id="srmTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="srm-overview-tab" data-bs-toggle="tab"
                            data-bs-target="#srm-overview" type="button" role="tab" aria-controls="srm-overview"
                            aria-selected="true">
                            <i class="ti ti-dashboard me-2"></i>Overview & Interactions
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="srm-historical-purchases-tab" data-bs-toggle="tab"
                            data-bs-target="#srm-historical-purchases" type="button" role="tab"
                            aria-controls="srm-historical-purchases" aria-selected="false">
                            <i class="ti ti-history me-2"></i>Historical Purchases
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="srm-product-history-tab" data-bs-toggle="tab"
                            data-bs-target="#srm-product-history" type="button" role="tab"
                            aria-controls="srm-product-history" aria-selected="false">
                            <i class="ti ti-shopping-cart me-2"></i>Product History
                        </button>
                    </li>
                </ul>
                <div class="tab-content">
                    <div class="tab-pane fade show active" id="srm-overview" role="tabpanel"
                        aria-labelledby="srm-overview-tab">
                        <div class="p-3">
                            <!-- Supplier Summary -->
                            <div class="card shadow-sm mb-3">
                                <div class="card-header">
                                    <h3 class="card-title"><i class="ti ti-user me-2 text-primary"></i>Supplier
                                        Information</h3>
                                </div>
                                <div class="card-body">
                                    <div class="row g-3">
                                        <div class="col-md-4 text-center">
                                            <div id="srmSupplierImageContainer" class="supplier-image-placeholder mb-3">
                                                <img id="srmSupplierImage" src="" alt="Supplier Image"
                                                    class="img-thumbnail" style="max-width: 120px; max-height: 120px;">
                                            </div>
                                            <h4 id="srmSupplierName" class="mb-1"></h4>
                                        </div>
                                        <div class="col-md-8">
                                            <div class="row row-cols-1 row-cols-md-2 g-2">
                                                <div class="col">
                                                    <p class="mb-1"><strong>Email:</strong> <span
                                                            id="srmSupplierEmail"></span></p>
                                                </div>
                                                <div class="col">
                                                    <p class="mb-1"><strong>Phone:</strong> <span
                                                            id="srmSupplierPhone"></span></p>
                                                </div>
                                                <div class="col">
                                                    <p class="mb-1"><strong>Address:</strong> <span
                                                            id="srmSupplierAddress"></span></p>
                                                </div>
                                                <div class="col">
                                                    <p class="mb-1"><strong>Payment Terms:</strong> <span
                                                            id="srmSupplierPaymentTerms"></span></p>
                                                </div>
                                                <div class="col">
                                                    <p class="mb-1"><strong>Member Since:</strong> <span
                                                            id="srmMemberSince"></span></p>
                                                </div>
                                                <div class="col">
                                                    <p class="mb-1"><strong>Last Purchase:</strong> <span
                                                            id="srmLastPurchase"></span></p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Key Metrics -->
                            <div class="card shadow-sm mb-3">
                                <div class="card-header">
                                    <h3 class="card-title"><i class="ti ti-chart-bar me-2 text-primary"></i>Key Metrics
                                    </h3>
                                </div>
                                <div class="card-body">
                                    <div class="row row-cols-1 row-cols-md-3 g-3 text-center">
                                        <div class="col">
                                            <div class="card card-sm">
                                                <div class="card-body">
                                                    <div class="font-weight-medium">Lifetime Value</div>
                                                    <div id="srmLifetimeValue" class="text-success fw-bold fs-3">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col">
                                            <div class="card card-sm">
                                                <div class="card-body">
                                                    <div class="font-weight-medium">Total Purchases Count</div>
                                                    <div id="srmTotalPurchasesCount" class="fs-3">N/A</div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col">
                                            <div class="card card-sm">
                                                <div class="card-body">
                                                    <div class="font-weight-medium">Average Order Value</div>
                                                    <div id="srmAverageOrderValue" class="fs-3">N/A</div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col">
                                            <div class="card card-sm">
                                                <div class="card-body">
                                                    <div class="font-weight-medium">Last Interaction Date</div>
                                                    <div id="srmLastInteractionDate" class="fs-3">N/A</div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col">
                                            <div class="card card-sm">
                                                <div class="card-body">
                                                    <div class="font-weight-medium">Most Purchased Product</div>
                                                    <div id="srmMostPurchasedProduct" class="fs-3">N/A</div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col">
                                            <div class="card card-sm">
                                                <div class="card-body">
                                                    <div class="font-weight-medium">Total Products Purchased</div>
                                                    <div id="srmTotalProductsPurchased" class="fs-3">N/A</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Interaction History -->
                            <div class="card shadow-sm mb-0">
                                <div class="card-header">
                                    <h3 class="card-title"><i class="ti ti-messages me-2 text-primary"></i>Interaction
                                        History</h3>
                                </div>
                                <div class="card-body pb-2">
                                    <form id="srmInteractionForm" class="mb-3">
                                        <div class="row g-2">
                                            <div class="col-md-3">
                                                <select class="form-select" name="type" required>
                                                    <option value="">Select Type</option>
                                                    <option value="call">Call</option>
                                                    <option value="email">Email</option>
                                                    <option value="meeting">Meeting</option>
                                                    <option value="note">Note</option>
                                                </select>
                                            </div>
                                            <div class="col-md-3">
                                                <input type="date" class="form-control" name="interaction_date"
                                                    value="2025-07-08" required>
                                            </div>
                                            <div class="col-md-4">
                                                <textarea class="form-control" name="notes" rows="1" placeholder="Add a new interaction..." required></textarea>
                                            </div>
                                            <div class="col-md-2">
                                                <button type="submit" class="btn btn-primary w-100"><i
                                                        class="ti ti-plus me-2"></i>Add</button>
                                            </div>
                                        </div>
                                    </form>
                                    <div id="srmInteractionTimeline" class="list-group list-group-flush"></div>
                                    <p id="srmNoInteractionsMessage" class="text-muted text-center py-2 mb-0"
                                        style="display: none;">No interactions yet.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="srm-historical-purchases" role="tabpanel"
                        aria-labelledby="srm-historical-purchases-tab">
                        <div class="p-3">
                            <div id="srmHistoricalPurchaseContent">
                                <!-- Content for Historical Purchase will be loaded here via JavaScript -->
                                <p class="text-muted text-center">Loading historical purchase data...</p>
                            </div>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="srm-product-history" role="tabpanel"
                        aria-labelledby="srm-product-history-tab">
                        <div class="p-3">
                            <div id="srmProductHistoryContent">
                                <!-- Content for Historical Purchase will be loaded here via JavaScript -->
                                <p class="text-muted text-center">Loading historical purchase data...</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>