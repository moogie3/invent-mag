<div class="modal modal-blur fade" id="paymentModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="ti ti-receipt me-2"></i>{{ __('messages.pos_order_summary_title') }}</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                    aria-label="Close"></button>
            </div>

            <div class="modal-body p-4">
                <!-- Enhanced Order Table -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="table-responsive">
                        <table class="table table-vcenter mb-0">
                            <thead class="bg-light">
                                <tr class="text-uppercase">
                                    <th class="fw-semibold text-muted">{{ __('messages.table_product') }}</th>
                                    <th class="text-center fw-semibold text-muted">{{ __('messages.table_qty') }}</th>
                                    <th class="text-end fw-semibold text-muted">{{ __('messages.table_price') }}</th>
                                    <th class="text-end fw-semibold text-muted">{{ __('messages.table_total') }}</th>
                                </tr>
                            </thead>
                            <tbody id="modalProductList">
                                <!-- Products will be populated here -->
                            </tbody>
                        </table>
                    </div>

                    <!-- Order Summary Section -->
                    <div class="bg-light border-top p-4">
                        <div class="row mb-2">
                            <div class="col-8 text-end">
                                <span class="fw-medium text-muted">{{ __('messages.po_subtotal') }}:</span>
                            </div>
                            <div class="col-4 text-end">
                                <span class="fw-semibold" id="modalSubtotal">Rp 0</span>
                            </div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-8 text-end">
                                <span class="text-muted">{{ __('messages.table_discount') }} <small id="modalDiscountDetail"></small>:</span>
                            </div>
                            <div class="col-4 text-end">
                                <span id="modalDiscount">Rp 0</span>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-8 text-end">
                                <span class="text-muted">{{ __('messages.pos_tax_ppn') }} <small id="modalTaxDetail"></small>:</span>
                            </div>
                            <div class="col-4 text-end">
                                <span id="modalTax">Rp 0</span>
                            </div>
                        </div>
                        <div class="row border-top pt-3">
                            <div class="col-8 text-end">
                                <span class="fw-bold fs-3 text-primary">{{ __('messages.po_grand_total') }}:</span>
                            </div>
                            <div class="col-4 text-end">
                                <span class="fw-bold fs-3 text-primary" id="modalGrandTotal">Rp 0</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Payment Info -->
                <div class="card shadow-sm">
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">{{ __('messages.pos_payment_method') }}</label>
                                <select class="form-select" id="paymentMethod">
                                    <option value="cash"> {{ __('messages.pos_payment_method_cash') }}</option>
                                    <option value="card"> {{ __('messages.pos_payment_method_card') }}</option>
                                    <option value="transfer"> {{ __('messages.pos_payment_method_transfer') }}</option>
                                    <option value="ewallet"> {{ __('messages.pos_payment_method_ewallet') }}</option>
                                </select>
                            </div>

                            <div class="col-md-6" id="cashPaymentDiv">
                                <label class="form-label fw-semibold">{{ __('messages.pos_amount_received') }}</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light">Rp</span>
                                    <input type="number" class="form-control" id="amountReceived" min="0"
                                        placeholder="0">
                                    <button type="button" class="btn btn-outline-primary" id="exactAmountBtn"
                                        title="{{ __('messages.pos_exact_amount') }}">
                                        <i class="ti ti-equal"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Change -->
                <div class="mt-3" id="changeRow" style="display: none;">
                    <div class="alert alert-success d-flex justify-content-between align-items-center m-0 shadow-sm">
                        <span class="fw-semibold fs-4">{{ __('messages.pos_change') }}</span>
                        <span class="fw-bold fs-4" id="changeAmount">Rp 0</span>
                    </div>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                    <i class="ti ti-x me-1"></i>{{ __('messages.cancel') }}
                </button>
                <button type="button" class="btn btn-primary ms-auto shadow-sm" id="completePaymentBtn">
                    <i class="ti ti-check me-1"></i>{{ __('messages.pos_complete_transaction') }}
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Quick Create Customer Modal -->
<div class="modal fade" id="quickCreateCustomerModal" tabindex="-1" aria-labelledby="quickCreateCustomerModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="quickCreateCustomerModalLabel"><i class="ti ti-user-plus me-2"></i>{{ __('messages.pos_create_new_customer') }}</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                    aria-label="Close"></button>
            </div>
            <form id="quickCreateCustomerForm" action="{{ route('admin.customer.quickCreate') }}" method="POST">
                @csrf
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label for="customerName" class="form-label fw-semibold">{{ __('messages.table_name') }}</label>
                        <input type="text" class="form-control" id="customerName" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="customerAddress" class="form-label fw-semibold">{{ __('messages.table_address') }}</label>
                        <input type="text" class="form-control" id="customerAddress" name="address" required>
                    </div>
                    <div class="mb-3">
                        <label for="customerPhone" class="form-label fw-semibold">{{ __('messages.supplier_modal_phone_number') }}</label>
                        <input type="text" class="form-control" id="customerPhone" name="phone_number" required>
                    </div>
                    <div class="mb-3">
                        <label for="customerPaymentTerms" class="form-label fw-semibold">{{ __('messages.table_payment_terms') }}</label>
                        <input type="text" class="form-control" id="customerPaymentTerms" name="payment_terms"
                            placeholder="e.g., Net 30" required>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal"><i
                            class="ti ti-x me-1"></i>{{ __('messages.cancel') }}</button>
                    <button type="submit" class="btn btn-primary shadow-sm"><i class="ti ti-plus me-1"></i>{{ __('messages.pos_create_customer_button') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Quick Create Product Modal -->
<div class="modal fade" id="quickCreateProductModal" tabindex="-1" aria-labelledby="quickCreateProductModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="quickCreateProductModalLabel"><i class="ti ti-box-seam me-2"></i>{{ __('messages.pos_create_new_product') }}</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                    aria-label="Close"></button>
            </div>
            <form id="quickCreateProductForm" enctype="multipart/form-data" method="POST"
                action="{{ route('admin.product.quickCreate') }}">
                @csrf
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">{{ __('messages.pos_product_code') }}</label>
                            <input type="text" class="form-control" name="code" placeholder="{{ __('messages.pos_enter_code') }}"
                                required>
                        </div>

                        <div class="col-md-8">
                            <label class="form-label fw-semibold">{{ __('messages.pos_product_name') }}</label>
                            <input type="text" class="form-control" name="name" placeholder="{{ __('messages.pos_enter_name') }}"
                                required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">{{ __('messages.supplier_title') }}</label>
                            <select class="form-select" name="supplier_id" required>
                                <option value="">{{ __('messages.pos_select_supplier') }}</option>
                                @foreach ($suppliers as $supplier)
                                    <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">{{ __('messages.categories') }}</label>
                            <select class="form-select" name="category_id" required>
                                <option value="">{{ __('messages.pos_select_category') }}</option>
                                @foreach ($categories as $category)
                                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">{{ __('messages.pos_stock_quantity') }}</label>
                            <input type="number" class="form-control" name="stock_quantity" placeholder="0"
                                required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">{{ __('messages.pos_low_stock_threshold') }}</label>
                            <input type="number" class="form-control" name="low_stock_threshold"
                                placeholder="{{ __('messages.pos_default_10') }}" min="1">
                            <small class="form-text text-muted">{{ __('messages.pos_low_stock_threshold_hint') }}</small>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">{{ __('messages.units') }}</label>
                            <select class="form-select" name="units_id" required>
                                <option value="">{{ __('messages.pos_select_unit') }}</option>
                                @foreach ($units as $unit)
                                    <option value="{{ $unit->id }}">{{ $unit->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">{{ __('messages.pos_buying_price') }}</label>
                            <input type="number" step="0" class="form-control" name="price"
                                placeholder="0" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">{{ __('messages.pos_selling_price') }}</label>
                            <input type="number" step="0" class="form-control" name="selling_price"
                                placeholder="0" required>
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold">{{ __('messages.table_description') }}</label>
                            <textarea class="form-control" name="description" rows="2" placeholder="{{ __('messages.pos_optional_description') }}"></textarea>
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold">{{ __('messages.pos_product_image') }}</label>
                            <input type="file" class="form-control" name="image">
                        </div>

                        <div class="col-md-6">
                            <div class="form-check form-switch mt-3">
                                <input class="form-check-input" type="checkbox" id="has_expiry" name="has_expiry"
                                    value="1">
                                <label class="form-check-label fw-semibold" for="has_expiry">{{ __('messages.pos_product_has_expiry') }} {{ __('messages.pos_product_has_expiry_date') }}</label>
                            </div>
                        </div>

                        <div class="col-md-6 expiry-date-field" style="display: none;">
                            <label class="form-label fw-semibold">{{ __('messages.table_expiry_date') }}</label>
                            <input type="date" class="form-control" name="expiry_date">
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal"><i
                            class="ti ti-x me-1"></i>{{ __('messages.cancel') }}</button>
                    <button type="submit" class="btn btn-primary shadow-sm"><i class="ti ti-plus me-1"></i>{{ __('messages.pos_create_product_button') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>
