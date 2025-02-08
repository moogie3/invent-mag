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
                        Product
                    </h2>
                </div>
                <div class="col-auto ms-auto">
                    <div class="btn-list">
                        <a href="{{ route('admin.product.create') }}" class="btn btn-primary d-none d-sm-inline-block">
                            <i class="ti ti-plus fs-4"></i>
                            Create Product
                        </a>
                    </div>
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
                            <table id="productt" class="table table-responsive">
                                <thead>
                                    <tr class="dark">
                                        <th style="display:none;">id</th>
                                        <th>no</th>
                                        <th>Picture</th>
                                        <th>Code</th>
                                        <th>Name</th>
                                        <th>QTY</th>
                                        <th>Category</th>
                                        <th>Unit</th>
                                        <th>Price</th>
                                        <th>Selling Price</th>
                                        <th>Supplier</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($products as $index => $product)
                                        <tr>
                                            <td style="display: none;">{{ $product->id }}</td>
                                            <td>{{$index + 1}}</td>
                                            <td style="width:120px">
                                                <img src="{{ asset($product->image) }}" width="120px">
                                            </td>
                                            <td>{{ $product->code }}</td>
                                            <td>{{ $product->name }}</td>
                                            <td>{{ $product->quantity }}</td>
                                            <td>{{ $product->category->name ?? 'No Category' }}</td>
                                            <td>{{ $product->unit->name ?? 'No Unit'}}</td>
                                            <td>{{ $product->price }}</td>
                                            <td>{{ $product->selling_price }}</td>
                                            <td>{{ $product->supplier->name ?? 'No Supplier' }}</td>
                                            <td>
                                                <div class="d-flex gap-2 justify-content-center">
                                                    <a href="{{ route('admin.product.edit', $product->id) }}" class="btn btn-secondary"><i class="ti ti-edit"></i></a>
                                                    <form method="POST" action="{{ route('admin.product.destroy', $product->id) }}">
                                                        @method('delete')
                                                        @csrf
                                                        <button type="submit" class="btn btn-danger"><i class="ti ti-trash"></i></button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                            <script>
                                $(document).ready(function () {
                                    $('#productt').DataTable();
                                });
                            </script>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@push('styles')
@endpush
@endsection
