    <div class="modal modal-blur fade" id="createProductModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
            <div class="modal-content">

                <div class="modal-header">
                    <h5 class="modal-title">Create New Product</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <form enctype="multipart/form-data" method="POST" action="{{ route('admin.product.store') }}">
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
                                <input type="number" step="0" class="form-control" name="price" placeholder="0"
                                    required>
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


    {{-- MODAL --}}
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-danger" id="deleteModalLabel">Confirm Delete</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center">
                    <i class="ti ti-alert-circle icon text-danger icon-lg mb-10"></i>
                    <p class="mt-3">Are you sure you want to delete this product?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form id="deleteForm" method="POST">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger">Delete</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
