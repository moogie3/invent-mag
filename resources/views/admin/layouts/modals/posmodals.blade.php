<div class="modal modal-blur fade" id="paymentModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header border-bottom-0 pb-0">
                <h3 class="modal-title fw-semibold">Order Summary</h3>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body pt-2">
                <!-- Order Table -->
                <div class="table-responsive mb-4">
                    <table class="table table-borderless">
                        <thead style="background-color: #a7de7c;"
                            class="table-heading text-white text-uppercase rounded-top">
                            <tr>
                                <th>Product</th>
                                <th class="text-center">Qty</th>
                                <th class="text-end">Price</th>
                                <th class="text-end">Total</th>
                            </tr>
                        </thead>
                        <tbody id="modalProductList"></tbody>
                        <tfoot class="border-top border-secondary">
                            <tr>
                                <td colspan="3" class="text-end fw-medium">Subtotal:</td>
                                <td class="text-end fw-medium" id="modalSubtotal">Rp 0</td>
                            </tr>
                            <tr>
                                <td colspan="3" class="text-end text-muted">
                                    Discount <small id="modalDiscountDetail" class="text-muted"></small>:
                                </td>
                                <td class="text-end" id="modalDiscount">Rp 0</td>
                            </tr>
                            <tr>
                                <td colspan="3" class="text-end text-muted">
                                    Tax (PPN) <small id="modalTaxDetail" class="text-muted"></small>:
                                </td>
                                <td class="text-end" id="modalTax">Rp 0</td>
                            </tr>
                            <tr class="border-top border-2 border-primary">
                                <td colspan="3" class="text-end fw-bold fs-4">Grand Total:</td>
                                <td class="text-end fw-bold fs-4 text-primary" id="modalGrandTotal">Rp 0</td>
                            </tr>
                        </tfoot>
                    </table>

                </div>

                <!-- Payment Info -->
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Payment Method</label>
                        <select class="form-select" id="paymentMethod">
                            <option value="cash">Cash</option>
                            <option value="card">Credit/Debit Card</option>
                            <option value="transfer">Bank Transfer</option>
                            <option value="ewallet">E-Wallet</option>
                        </select>
                    </div>

                    <div class="col-md-6" id="cashPaymentDiv">
                        <label class="form-label fw-semibold">Amount Received</label>
                        <div class="input-group">
                            <span class="input-group-text">Rp</span>
                            <input type="number" class="form-control" id="amountReceived" min="0">
                            <button type="button" class="btn btn-outline-success" id="exactAmountBtn"
                                title="Exact Amount">
                                <i class="ti ti-equal"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Change -->
                <div class="row mt-4" id="changeRow">
                    <div class="col-md-12">
                        <div class="alert alert-success d-flex justify-content-between align-items-center">
                            <span class="fw-semibold fs-5">Change:</span>
                            <span class="fw-bold fs-5" id="changeAmount">Rp 0</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="modal-footer border-top-0 pt-0">
                <button type="button" class="btn btn-link text-muted" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success ms-auto" id="completePaymentBtn">
                    <i class="ti ti-check me-1"></i> Complete Transaction
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
            <div class="modal-header">
                <h5 class="modal-title" id="quickCreateCustomerModalLabel">
                    Create New Customer</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="quickCreateCustomerForm" action="{{ route('admin.customer.quickCreate') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="customerName" class="form-label">Name</label>
                        <input type="text" class="form-control" id="customerName" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="customerAddress" class="form-label">Address</label>
                        <input type="text" class="form-control" id="customerAddress" name="address" required>
                    </div>
                    <div class="mb-3">
                        <label for="customerPhone" class="form-label">Phone Number</label>
                        <input type="text" class="form-control" id="customerPhone" name="phone_number" required>
                    </div>
                    <div class="mb-3">
                        <label for="customerPaymentTerms" class="form-label">Payment Terms</label>
                        <input type="text" class="form-control" id="customerPaymentTerms" name="payment_terms"
                            placeholder="" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Customer</button>
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
            <div class="modal-header">
                <h5 class="modal-title" id="quickCreateProductModalLabel">Create New Product</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="quickCreateProductForm" enctype="multipart/form-data" method="POST"
                action="{{ route('admin.product.quickCreate') }}">
                @csrf
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Product Code</label>
                            <input type="text" class="form-control" name="code" placeholder="Enter code"
                                required>
                        </div>

                        <div class="col-md-8">
                            <label class="form-label">Product Name</label>
                            <input type="text" class="form-control" name="name" placeholder="Enter name"
                                required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Supplier</label>
                            <select class="form-select" name="supplier_id" required>
                                <option value="">Select Supplier</option>
                                @foreach ($suppliers as $supplier)
                                    <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Category</label>
                            <select class="form-select" name="category_id" required>
                                <option value="">Select Category</option>
                                @foreach ($categories as $category)
                                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Stock Quantity</label>
                            <input type="number" class="form-control" name="stock_quantity" placeholder="0"
                                required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Unit</label>
                            <select class="form-select" name="units_id" required>
                                <option value="">Select Unit</option>
                                @foreach ($units as $unit)
                                    <option value="{{ $unit->id }}">{{ $unit->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Buying Price</label>
                            <input type="number" step="0" class="form-control" name="price"
                                placeholder="0" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Selling Price</label>
                            <input type="number" step="0" class="form-control" name="selling_price"
                                placeholder="0" required>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" rows="2" placeholder="Optional description"></textarea>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Product Image</label>
                            <input type="file" class="form-control" name="image">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Product</button>
                </div>
            </form>
        </div>
    </div>
</div>
