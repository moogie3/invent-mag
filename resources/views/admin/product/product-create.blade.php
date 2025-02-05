@extends('admin.layouts.base')

@section('title', 'Product')

@section('content')
<div class="page-wrapper">
    <!-- Page header -->
    <div class="page-header">
        <div class="container-xl">
            <div class="row align-items-center">
                <div class="col">
                    <div class="page-pretitle">
                        Overview
                    </div>
                    <h2 class="page-title">
                        Create Product
                    </h2>
                </div>
            </div>
        </div>
    </div>
    <!-- Page body -->
    <div class="page-body">
        <div class="container-xl">
            <div class="row row-deck row-cards">
                <div class="col-md-12">
                    <div class="card card-primary">
                        <div class="card-body">
                            <form enctype="multipart/form-data" method="POST" action="{{ route('admin.product.store') }}">
                                @csrf
                            <fieldset class="form-fieldset container-xl">
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">CODE</label>
                                        <input type="text" class="form-control" name="code" id="code"
                                            placeholder="Product Code" required/>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">SUPPLIER</label>
                                        <select class="form-control" name="supplier_id" required>
                                            <option value="">Select Supplier</option>
                                            @foreach($suppliers as $supplier)
                                                <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">NAME</label>
                                        <input type="text" class="form-control" name="name" id="name"
                                            placeholder="Name" required/>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">CATEGORY</label>
                                        <select class="form-control" name="category_id" required>
                                            <option value="">Select Category</option>
                                            @foreach($categories as $category)
                                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">UNIT</label>
                                        <select class="form-control" name="unit_id" required>
                                            <option value="">Select Unit</option>
                                            @foreach($units as $unit)
                                                <option value="{{ $unit->id }}">{{ $unit->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Buying Price</label>
                                        <input type="text" class="form-control" name="price" id="price" placeholder="Price" required/>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Selling Price</label>
                                        <input type="text" class="form-control" name="selling_price" id="selling_price" placeholder="Selling Price" required/>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">IMAGE</label>
                                        <input type="file" class="form-control" name="image"/>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">DESC</label>
                                        <input type="text" class="form-control" name="description" id="description"
                                            placeholder="Description"/>
                                    </div>
                                </div>
                                <div class="text-end">
                                    <button type="submit" class="btn btn-primary">Submit</button>
                                </div>
                            </fieldset>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
