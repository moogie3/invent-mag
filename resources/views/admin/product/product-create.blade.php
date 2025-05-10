@extends('admin.layouts.base')

@section('title', 'Create New Product')

@section('content')
    <div class="page-wrapper">
        <div class="page-header">
            <div class="container-xl">
                <div class="row align-items-center">
                    <div class="col">
                        <div class="page-pretitle">Product Management</div>
                        <h2 class="page-title fw-bold">Edit Product</h2>
                    </div>
                    <div class="col-auto ms-auto">
                        <div class="btn-list">
                            <a href="{{ route('admin.product') }}" class="btn btn-outline-primary">
                                <i class="ti ti-arrow-left me-1"></i> Back to Products
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="page-body">
            <div class="container-xl">
                <div class="row row-cards">
                    <div class="col-12">
                        <div class="card shadow-sm border">
                            <div class="card-header bg-light">
                                <h3 class="card-title d-flex align-items-center">
                                    <i class="ti ti-package me-2"></i> Product Information
                                </h3>
                            </div>
                            <div class="card-body">
                                <form enctype="multipart/form-data" method="POST"
                                    action="{{ route('admin.product.store') }}">
                                    @csrf
                                    <div class="mb-2 pb-2">
                                        <h4 class="fw-semibold mb-3 text-secondary d-flex align-items-center">
                                            <i class="ti ti-info-circle me-2 text-muted"></i> Basic Information
                                        </h4>
                                        <div class="row g-3">
                                            <div class="col-md-4">
                                                <label class="form-label ">Product Code</label>
                                                <input type="text" class="form-control" name="code"
                                                    placeholder="Enter code">
                                            </div>
                                            <div class="col-md-8">
                                                <label class="form-label ">Product Name</label>
                                                <input type="text" class="form-control" name="name"
                                                    placeholder="Enter name">
                                            </div>
                                            <div class="col-md-12">
                                                <label class="form-label">Description</label>
                                                <textarea class="form-control" name="description" rows="3" placeholder="Enter product description"></textarea>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mb-2 pb-2">
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <label class="form-label ">Supplier</label>
                                                <select class="form-select" name="supplier_id">
                                                    <option value="">Select Supplier</option>
                                                    @foreach ($suppliers as $supplier)
                                                        <option value="{{ $supplier->id }}">
                                                            {{ $supplier->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label ">Category</label>
                                                <select class="form-select" name="category_id">
                                                    <option value="">Select Category</option>
                                                    @foreach ($categories as $category)
                                                        <option value="{{ $category->id }}">
                                                            {{ $category->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mb-4 pb-2">
                                        <div class="row g-3">
                                            <div class="col-md-4">
                                                <label class="form-label ">Stock Quantity</label>
                                                <input type="number" class="form-control" name="stock_quantity"
                                                    placeholder="0">
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label">Low Stock Threshold</label>
                                                <input type="number" class="form-control" name="low_stock_threshold"
                                                    placeholder="Default (10)" min="1">
                                                <small class="form-text text-muted">Leave empty to use system
                                                    default</small>
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label ">Unit</label>
                                                <select class="form-select" name="units_id">
                                                    <option value="">Select Unit</option>
                                                    @foreach ($units as $unit)
                                                        <option value="{{ $unit->id }}">
                                                            {{ $unit->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-md-12">
                                                <label class="form-label ">Warehouse</label>
                                                <select name="warehouse_id" class="form-select" id="warehouse_id">
                                                    <option value="">Select Warehouse</option>
                                                    @foreach ($warehouses as $warehouse)
                                                        <option value="{{ $warehouse->id }}">
                                                            {{ $warehouse->name }}
                                                            {{ $warehouse->is_main ? '(Main)' : '' }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mb-4 pb-2">
                                        <h4 class="fw-semibold mb-3 text-secondary d-flex align-items-center">
                                            <i class="ti ti-cash me-2 text-muted"></i> Pricing Information
                                        </h4>
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <label class="form-label ">Buying Price</label>
                                                <div class="input-group">
                                                    <span class="input-group-text"><i class="ti ti-currency"></i></span>
                                                    <input type="number" step="0" class="form-control"
                                                        name="price" placeholder="0">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label ">Selling Price</label>
                                                <div class="input-group">
                                                    <span class="input-group-text"><i class="ti ti-currency"></i></span>
                                                    <input type="number" step="0" class="form-control"
                                                        name="selling_price" placeholder="0">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div>
                                        <h4 class="fw-semibold mb-3 text-secondary d-flex align-items-center">
                                            <i class="ti ti-settings me-2 text-muted"></i> Additional Details
                                        </h4>
                                        <div class="row g-3">
                                            <div class="col-md-4">
                                                <label class="form-label">Product Image</label>
                                                <input type="file"
                                                    class="form-control @error('image') is-invalid @enderror"
                                                    name="image">
                                                <small class="form-text text-muted">Recommended size: 400x400px (Max:
                                                    2MB)</small>
                                            </div>
                                            <div class="col-md-2"></div>
                                            <div class="col-md-6">
                                                <label class="form-label">Expiration Settings</label>
                                                <div class="row g-2 align-items-center">
                                                    <div class="col-auto d-flex align-items-center">
                                                        <div class="form-check form-switch m-0">
                                                            <input class="form-check-input" type="checkbox"
                                                                id="has_expiry" name="has_expiry" value="1">
                                                            <label class="form-check-label ms-2" for="has_expiry">Has
                                                                expiry</label>
                                                        </div>
                                                    </div>
                                                    <div class="col">
                                                        <div id="expiry_date_container" style="display: none;">
                                                            <input type="date" class="form-control" name="expiry_date"
                                                                id="expiry_date" placeholder="Select expiry date">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                    </div>

                                    {{-- Form Footer --}}
                                    <div class="form-footer mt-5">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <a href="{{ route('admin.product') }}"
                                                    class="btn btn-outline-secondary w-100">
                                                    <i class="ti ti-x me-1"></i> Cancel
                                                </a>
                                            </div>
                                            <div class="col-md-6">
                                                <button type="submit" class="btn btn-primary w-100">
                                                    <i class="ti ti-plus me-1"></i> Create Product
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection
