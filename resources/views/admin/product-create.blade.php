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
                            <fieldset class="form-fieldset">
                                <div class="mb-3">
                                    <label class="form-label required">Code</label>
                                    <input type="text" class="form-control" name="code" id="code" placeholder="Product Code" value="code"/>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label required">Name</label>
                                    <input type="text" class="form-control" name="name" id="name" placeholder="Name" value="name"/>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label required">Category</label>
                                    <input type="text" class="form-control" name="category" id="category" placeholder="Category" value="category"/>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label required">Quantity</label>
                                    <input type="text" class="form-control" name="quantity" id="quantity" placeholder="Quantity" value="quantity"/>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label required">Supplier</label>
                                    <input type="text" class="form-control" name="supplier" id="supplier" placeholder="Supplier" value="supplier"/>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label required">Description</label>
                                    <input type="text" class="form-control" name="description" id="description" placeholder="Description" value=""description/>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label required">Image</label>
                                    <input type="text" class="form-control" name="image"/>
                                </div>
                                <div class="card-footer">
                                    <button type="submit" class="btn btn-primary">Submit</button>
                                </div>
                            </fieldset>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
