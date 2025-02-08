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
                        Edit Product
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
                            <form enctype="multipart/form-data" method="POST"
                                action="{{ route('admin.product.update', $products->id)}}">
                                @csrf
                                @method('PUT')
                                <fieldset class="form-fieldset container-xl">
                                    <div class="row">
                                        <div class="col-md-1 mb-3">
                                            <label class="form-label">CODE</label>
                                            <input type="text" class="form-control" name="code" id="code"
                                                placeholder="Code" value="{{$products->code}}" />
                                        </div>
                                        <div class="col-md-2 mb-3">
                                            <label class="form-label">SUPPLIER</label>
                                            <select class="form-control" name="supplier_id" required>
                                                <option value="{{ $products->supplier_id }}">{{ $products->supplier->name}}</option>
                                                @foreach($suppliers as $supplier)
                                                    <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">NAME</label>
                                            <input type="text" class="form-control" name="name" id="name"
                                                placeholder="Name" value="{{$products->name}}"/>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">QTY</label>
                                            <input type="text" class="form-control" name="quantity" id="quantity"
                                                placeholder="Quantity" value="{{$products->quantity}}" />
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">CATEGORY</label>
                                            <select class="form-control" name="category_id" required>
                                                <option value="{{ $products->category_id }}">{{ $products->category->name}}</option>
                                                @foreach($categories as $category)
                                                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Buying Price</label>
                                            <input type="text" class="form-control" name="price" id="price"
                                                placeholder="Price" value="{{$products->price}}" />
                                        </div>

                                    </div>

                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">UNIT</label>
                                            <select class="form-control" name="units_id" required>
                                                <option value="{{ $products->units_id }}">{{ $products->unit->name}}</option>
                                                @foreach($units as $unit)
                                                    <option value="{{ $unit->id }}">{{ $unit->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Selling Price</label>
                                            <input type="text" class="form-control" name="selling_price"
                                                id="selling_price" placeholder="Selling Price" value="{{$products->selling_price}}"/>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-12 mb-3">
                                            <label class="form-label">DESC</label>
                                            <input type="text" class="form-control" name="description" id="description"
                                                placeholder="Description" value="{{$products->description}}"/>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">IMAGE</label>
                                            <input type="file" class="form-control" name="image"/>
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
@if($errors->any())
    <div class="alert alert-danger">
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
@endsection
