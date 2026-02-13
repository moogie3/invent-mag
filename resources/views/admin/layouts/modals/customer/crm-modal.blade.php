<div class="modal modal-blur fade" id="crmCustomerModal" tabindex="-1" aria-labelledby="crmCustomerModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-3">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="crmCustomerModalLabel"><i
                        class="ti ti-users me-2"></i>{{ __('messages.customer_crm_title') }} <span
                        id="crmCustomerNameInHeader" class="text-white fw-bold"></span></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                    aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <ul class="nav nav-tabs nav-fill border-0 flex-column flex-md-row" id="crmTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="overview-tab" data-bs-toggle="tab"
                            data-bs-target="#overview" type="button" role="tab" aria-controls="overview"
                            aria-selected="true">
                            <i class="ti ti-dashboard me-2"></i>{{ __('messages.supplier_srm_tab_overview') }}
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="historical-sales-tab" data-bs-toggle="tab"
                            data-bs-target="#historical-sales" type="button" role="tab"
                            aria-controls="historical-sales" aria-selected="false">
                            <i class="ti ti-history me-2"></i>{{ __('messages.customer_crm_tab_historical_sales') }}
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="purchase-history-tab" data-bs-toggle="tab"
                            data-bs-target="#purchase-history" type="button" role="tab"
                            aria-controls="purchase-history" aria-selected="false">
                            <i
                                class="ti ti-shopping-cart me-2"></i>{{ __('messages.supplier_srm_tab_product_history') }}
                        </button>
                    </li>
                </ul>
                <div class="tab-content">
                    <div class="tab-pane fade show active" id="overview" role="tabpanel"
                        aria-labelledby="overview-tab">
                        <div class="p-3">
                            <!-- Customer Summary -->
                            <div class="card shadow-sm mb-3">
                                <div class="card-header">
                                    <h3 class="card-title"><i
                                            class="ti ti-user me-2 text-primary"></i>{{ __('messages.customer_info_title') }}
                                    </h3>
                                </div>
                                <div class="card-body">
                                    <div class="row g-3">
                                        <div class="col-md-4 text-center">
                                            <div id="crmCustomerImageContainer" class="customer-image-placeholder mb-3">
                                                <img id="crmCustomerImage" src="" alt="Customer Image"
                                                    class="img-thumbnail" style="max-width: 120px; max-height: 120px;">
                                            </div>
                                            <h4 id="crmCustomerName" class="mb-1"></h4>
                                        </div>
                                        <div class="col-md-8">
                                            <div class="row row-cols-1 row-cols-md-2 g-2">
                                                <div class="col">
                                                    <p class="mb-1"><strong>{{ __('messages.table_email') }}:</strong>
                                                        <span id="crmCustomerEmail"></span>
                                                    </p>
                                                </div>
                                                <div class="col">
                                                    <p class="mb-1">
                                                        <strong>{{ __('messages.customer_crm_phone') }}:</strong> <span
                                                            id="crmCustomerPhone"></span>
                                                    </p>
                                                </div>
                                                <div class="col">
                                                    <p class="mb-1">
                                                        <strong>{{ __('messages.table_address') }}:</strong> <span
                                                            id="crmCustomerAddress"></span>
                                                    </p>
                                                </div>
                                                <div class="col">
                                                    <p class="mb-1">
                                                        <strong>{{ __('messages.table_payment_terms') }}:</strong> <span
                                                            id="crmCustomerPaymentTerms"></span>
                                                    </p>
                                                </div>
                                                <div class="col">
                                                    <p class="mb-1">
                                                        <strong>{{ __('messages.customer_crm_member_since') }}:</strong>
                                                        <span id="crmMemberSince"></span>
                                                    </p>
                                                </div>
                                                <div class="col">
                                                    <p class="mb-1">
                                                        <strong>{{ __('messages.customer_crm_last_purchase') }}:</strong>
                                                        <span id="crmLastPurchase"></span>
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Key Metrics -->
                            <div class="card shadow-sm mb-3">
                                <div class="card-header">
                                    <h3 class="card-title"><i
                                            class="ti ti-chart-bar me-2 text-primary"></i>{{ __('messages.supplier_srm_key_metrics_title') }}
                                    </h3>
                                </div>
                                <div class="card-body">
                                    <div class="row row-cols-1 row-cols-md-3 g-3 text-center">
                                        <div class="col">
                                            <div class="card card-sm">
                                                <div class="card-body">
                                                    <div class="font-weight-medium fw-bold">
                                                        {{ __('messages.supplier_srm_key_metrics_lifetime_value') }}
                                                    </div>
                                                    <div id="crmLifetimeValue" class="text-success fw-bold fs-3">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col">
                                            <div class="card card-sm">
                                                <div class="card-body">
                                                    <div class="font-weight-medium fw-bold">
                                                        {{ __('messages.customer_crm_key_metrics_total_sales_count') }}
                                                    </div>
                                                    <div id="crmTotalSalesCount" class="fs-3">N/A</div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col">
                                            <div class="card card-sm">
                                                <div class="card-body">
                                                    <div class="font-weight-medium fw-bold">
                                                        {{ __('messages.supplier_srm_key_metrics_average_order_value') }}
                                                    </div>
                                                    <div id="crmAverageOrderValue" class="fs-3">N/A</div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col">
                                            <div class="card card-sm">
                                                <div class="card-body">
                                                    <div class="font-weight-medium fw-bold">
                                                        {{ __('messages.supplier_srm_key_metrics_last_interaction_date') }}
                                                    </div>
                                                    <div id="crmLastInteractionDate" class="fs-3">N/A</div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col">
                                            <div class="card card-sm">
                                                <div class="card-body">
                                                    <div class="font-weight-medium fw-bold">
                                                        {{ __('messages.supplier_srm_key_metrics_most_purchased_product') }}
                                                    </div>
                                                    <div id="crmMostPurchasedProduct" class="fs-3">N/A</div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col">
                                            <div class="card card-sm">
                                                <div class="card-body">
                                                    <div class="font-weight-medium fw-bold">
                                                        {{ __('messages.supplier_srm_key_metrics_total_products_purchased') }}
                                                    </div>
                                                    <div id="crmTotalProductsPurchased" class="fs-3">N/A</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Interaction History -->
                            <div class="card shadow-sm mb-0">
                                <div class="card-header">
                                    <h3 class="card-title"><i
                                            class="ti ti-messages me-2 text-primary"></i>{{ __('messages.supplier_srm_interaction_history_title') }}
                                    </h3>
                                </div>
                                <div class="card-body pb-2">
                                    <form id="interactionForm" class="mb-3">
                                        <div class="row g-2">
                                            <div class="col-md-3">
                                                <select class="form-select" name="type" required>
                                                    <option value="">
                                                        {{ __('messages.supplier_srm_interaction_history_select_type') }}
                                                    </option>
                                                    <option value="call">
                                                        {{ __('messages.supplier_srm_interaction_history_type_call') }}
                                                    </option>
                                                    <option value="email">
                                                        {{ __('messages.customer_crm_interaction_history_type_email') }}
                                                    </option>
                                                    <option value="meeting">
                                                        {{ __('messages.supplier_srm_interaction_history_type_meeting') }}
                                                    </option>
                                                    <option value="note">
                                                        {{ __('messages.supplier_srm_interaction_history_type_note') }}
                                                    </option>
                                                </select>
                                            </div>
                                            <div class="col-md-3">
                                                <input type="date" class="form-control" name="interaction_date"
                                                    value="2025-07-08" required>
                                            </div>
                                            <div class="col-md-4">
                                                <textarea class="form-control" name="notes" rows="1"
                                                    placeholder="{{ __('messages.supplier_srm_interaction_history_add_placeholder') }}" required></textarea>
                                            </div>
                                            <div class="col-md-2">
                                                <button type="submit" class="btn btn-primary w-100"><i
                                                        class="ti ti-plus me-2"></i>{{ __('messages.supplier_srm_interaction_history_add_button') }}</button>
                                            </div>
                                        </div>
                                    </form>
                                    <div id="interactionTimeline" class="list-group list-group-flush"></div>
                                    <div id="noInteractionsMessage" class="empty" style="display: none;">
                                        <div class="empty-img">
                                            <i class="ti ti-messages fs-1 text-muted"></i>
                                        </div>
                                        <p class="empty-title">
                                            {{ __('messages.supplier_srm_interaction_history_no_interactions') }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="historical-sales" role="tabpanel"
                        aria-labelledby="historical-sales-tab">
                        <div class="p-3">
                            <div id="transactionHistory" class="accordion">
                                <div class="empty">
                                    <div class="empty-img">
                                        <i class="ti ti-shopping-cart-off fs-1 text-muted"></i>
                                    </div>
                                    <p class="empty-title">{{ __('messages.no_historical_sales_found') }}</p>
                                    <p class="empty-subtitle text-muted">
                                        {{ __('messages.this_customer_hasnt_made_any_purchases_yet') }}
                                    </p>
                                </div>
                            </div>
                            <div class="text-center mt-3">
                                <button class="btn btn-secondary" id="loadMoreTransactions" style="display: none;"><i
                                        class="ti ti-reload me-2"></i>{{ __('messages.customer_crm_historical_sales_load_more') }}</button>
                            </div>
                            <div id="noTransactionsMessage" class="empty" style="display: none;">
                                <div class="empty-img">
                                    <i class="ti ti-shopping-cart-off fs-1 text-muted"></i>
                                </div>
                                <p class="empty-title">{{ __('messages.no_historical_sales_found') }}</p>
                                <p class="empty-subtitle text-muted">
                                    {{ __('messages.this_customer_hasnt_made_any_purchases_yet') }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="purchase-history" role="tabpanel"
                        aria-labelledby="purchase-history-tab">
                        <div class="p-3">
                            <div id="productHistoryContent">
                                <!-- Content for Historical Purchase will be loaded here via JavaScript -->
                            </div>
                            <div id="noProductHistoryMessage" class="empty" style="display: none;">
                                <div class="empty-img">
                                    <i class="ti ti-shopping-cart-off fs-1 text-muted"></i>
                                </div>
                                <p class="empty-title">{{ __('messages.no_product_history_found') }}</p>
                                <p class="empty-subtitle text-muted">
                                    {{ __('messages.this_customer_hasnt_purchased_any_products_yet') }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
