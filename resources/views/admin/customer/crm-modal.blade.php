<div class="modal modal-blur fade" id="crmCustomerModal" tabindex="-1" aria-labelledby="crmCustomerModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="crmCustomerModalLabel"><i class="ti ti-users me-2"></i>CRM: <span id="crmCustomerName"></span></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <ul class="nav nav-tabs nav-fill border-0 flex-column flex-md-row" id="crmTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="overview-tab" data-bs-toggle="tab" data-bs-target="#overview" type="button" role="tab" aria-controls="overview" aria-selected="true">
                            <i class="ti ti-dashboard me-2"></i>Overview & Interactions
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="history-tab" data-bs-toggle="tab" data-bs-target="#history" type="button" role="tab" aria-controls="history" aria-selected="false">
                            <i class="ti ti-history me-2"></i>Historical Transactions
                        </button>
                    </li>
                </ul>
                <div class="tab-content">
                    <div class="tab-pane fade show active" id="overview" role="tabpanel" aria-labelledby="overview-tab">
                        <div class="p-4">
                            <!-- Customer Summary -->
                            <div class="card shadow-sm mb-4">
                                <div class="card-header">
                                    <h3 class="card-title"><i class="ti ti-user me-2 text-primary"></i>Customer Information</h3>
                                </div>
                                <div class="card-body">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <p class="mb-1"><strong>Email:</strong> <span id="crmCustomerEmail"></span></p>
                                            <p class="mb-1"><strong>Phone:</strong> <span id="crmCustomerPhone"></span></p>
                                        </div>
                                        <div class="col-md-6">
                                            <p class="mb-1"><strong>Member Since:</strong> <span id="crmMemberSince"></span></p>
                                            <p class="mb-1"><strong>Last Purchase:</strong> <span id="crmLastPurchase"></span></p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Key Metrics -->
                            <div class="card shadow-sm mb-4">
                                <div class="card-header">
                                    <h3 class="card-title"><i class="ti ti-chart-bar me-2 text-primary"></i>Key Metrics</h3>
                                </div>
                                <div class="card-body">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <p class="mb-1"><strong>Lifetime Value:</strong> <span id="crmLifetimeValue" class="fw-bold text-success"></span></p>
                                        </div>
                                        <div class="col-md-6">
                                            <p class="mb-1"><strong>Favorite Category:</strong> <span id="crmFavoriteCategory"></span></p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Interaction History -->
                            <div class="card shadow-sm">
                                <div class="card-header">
                                    <h3 class="card-title"><i class="ti ti-messages me-2 text-primary"></i>Interaction History</h3>
                                </div>
                                <div class="card-body">
                                    <form id="interactionForm" class="mb-4">
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
                                                <input type="date" class="form-control" name="interaction_date" value="{{ date('Y-m-d') }}" required>
                                            </div>
                                            <div class="col-md-4">
                                                <textarea class="form-control" name="notes" rows="1" placeholder="Add a new interaction..." required></textarea>
                                            </div>
                                            <div class="col-md-2">
                                                <button type="submit" class="btn btn-primary w-100"><i class="ti ti-plus me-2"></i>Add</button>
                                            </div>
                                        </div>
                                    </form>
                                    <div id="interactionTimeline" class="list-group list-group-flush"></div>
                                    <p id="noInteractionsMessage" class="text-muted text-center mt-3" style="display: none;">No interactions yet.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="history" role="tabpanel" aria-labelledby="history-tab">
                        <div class="p-4">
                            <div id="transactionHistory" class="accordion"></div>
                            <div class="text-center mt-4">
                                <button class="btn btn-secondary" id="loadMoreTransactions" style="display: none;"><i class="ti ti-reload me-2"></i>Load More Transactions</button>
                            </div>
                            <p id="noTransactionsMessage" class="text-muted text-center mt-3" style="display: none;">No transactions found.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>