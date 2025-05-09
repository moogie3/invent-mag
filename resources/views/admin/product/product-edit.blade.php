@extends('admin.layouts.base')

@section('title', 'Edit Product')

@section('content')
    <div class="page-wrapper">
        <div class="page-header">
            <div class="container-xl">
                <div class="row align-items-center">
                    <div class="col">
                        <div class="page-pretitle">Product Management</div>
                        <h2 class="page-title">Edit Product</h2>
                    </div>
                </div>
            </div>
        </div>

        <div class="page-body">
            <div class="container-xl">
                <div class="row row-cards">
                    <div class="col-12">
                        <form method="POST" action="{{ route('admin.product.update', $products->id) }}"
                            enctype="multipart/form-data">
                            @csrf
                            @method('PUT')

                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title"><i class="ti ti-box fs-4"></i> Product Information</h3>
                                </div>
                                <div class="card-body">
                                    <div class="row g-3">
                                        <div class="col-md-2">
                                            <label class="form-label">Code</label>
                                            <input type="text" class="form-control" value="{{ $products->code }}"
                                                disabled>
                                        </div>

                                        <div class="col-md-4">
                                            <label class="form-label">Supplier</label>
                                            <select name="supplier_id" class="form-select">
                                                <option value="{{ $products->supplier_id }}">{{ $products->supplier->name }}
                                                </option>
                                                @foreach ($suppliers as $supplier)
                                                    <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div class="col-md-6">
                                            <label class="form-label">Product Name</label>
                                            <input type="text" name="name" class="form-control"
                                                value="{{ $products->name }}">
                                        </div>
                                    </div>

                                    <hr class="my-4">

                                    <div class="row g-3">
                                        <div class="col-md-3">
                                            <label class="form-label">Category</label>
                                            <select name="category_id" class="form-select">
                                                <option value="{{ $products->category_id }}">{{ $products->category->name }}
                                                </option>
                                                @foreach ($categories as $category)
                                                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div class="col-md-3">
                                            <label class="form-label">Unit</label>
                                            <select name="units_id" class="form-select">
                                                <option value="{{ $products->units_id }}">{{ $products->unit->name }}
                                                </option>
                                                @foreach ($units as $unit)
                                                    <option value="{{ $unit->id }}">{{ $unit->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div class="col-md-3">
                                            <label class="form-label">Stock Quantity</label>
                                            <input type="text" name="stock_quantity" class="form-control"
                                                value="{{ $products->stock_quantity }}">
                                        </div>

                                        <div class="col-md-3">
                                            <label class="form-label">Low Stock Threshold</label>
                                            <input type="number" class="form-control" name="low_stock_threshold"
                                                value="{{ $products->low_stock_threshold }}" placeholder="Default (10)"
                                                min="1">
                                            <small class="form-text text-muted">Leave empty to use system default
                                                (10)</small>
                                        </div>
                                    </div>

                                    <hr class="my-4">

                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label">Buying Price</label>
                                            <input type="text" name="price" class="form-control"
                                                value="{{ $products->price }}">
                                        </div>

                                        <div class="col-md-6">
                                            <label class="form-label">Selling Price</label>
                                            <input type="text" name="selling_price" class="form-control"
                                                value="{{ $products->selling_price }}">
                                        </div>
                                    </div>

                                    <hr class="my-4">

                                    <div class="form-group">
                                        <label class="form-label">Warehouse</label>
                                        <select name="warehouse_id" class="form-select" id="edit_warehouse_id">
                                            <option value="">Select Warehouse</option>
                                            @foreach ($warehouses as $warehouse)
                                                <option value="{{ $warehouse->id }}"
                                                    {{ isset($products) && $products->warehouse_id == $warehouse->id ? 'selected' : '' }}>
                                                    {{ $warehouse->name }} {{ $warehouse->is_main ? '(Main)' : '' }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <hr class="my-4">

                                    <div class="mb-3">
                                        <label class="form-label">Description</label>
                                        <textarea name="description" class="form-control" rows="3">{{ $products->description }}</textarea>
                                    </div>

                                    <hr class="my-4">

                                    <div class="row g-3 align-items-end">
                                        <div class="col-md-6">
                                            <label class="form-label">Current Image</label>
                                            <div class="mb-2">
                                                <img src="{{ asset($products->image) }}" alt="Product Image"
                                                    class="rounded border shadow-sm" width="200">
                                            </div>
                                            <input type="file" name="image" class="form-control">
                                            <small class="text-muted">Upload a new image to replace the current
                                                one.</small>
                                        </div>
                                    </div>

                                    <hr class="my-4">

                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <div class="form-check form-switch mt-3">
                                                <input class="form-check-input" type="checkbox" id="has_expiry"
                                                    name="has_expiry" value="1"
                                                    {{ $products->has_expiry ? 'checked' : '' }}>
                                                <label class="form-check-label" for="has_expiry">Product has expiration
                                                    date</label>
                                            </div>
                                        </div>

                                        <div class="col-md-6 expiry-date-field"
                                            style="{{ $products->has_expiry ? '' : 'display: none;' }}">
                                            <label class="form-label">Expiry Date</label>
                                            <input type="date" class="form-control" name="expiry_date"
                                                id="expiry_date"
                                                value="{{ $products->expiry_date ? $products->expiry_date->format('Y-m-d') : '' }}">
                                        </div>
                                    </div>
                                </div>
                                <div class="card-footer text-end">
                                    <button type="submit" class="btn btn-primary">Save Changes</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
