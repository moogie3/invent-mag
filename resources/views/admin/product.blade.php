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
                        <a href="#" class="btn btn-primary d-none d-sm-inline-block">
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
                            <table id="product" class="table table-responsive">
                                <thead>
                                    <tr>
                                        <th>no</th>
                                        <th>Picture</th>
                                        <th>Name</th>
                                        <th>QTY</th>
                                        <th>Category</th>
                                        <th>Price</th>
                                        <th>Selling Price</th>
                                        <th>Supplier</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($products as $product)
                                        <tr>
                                            <td>{{ $product->id }}</td>
                                            <td><img src="{{ asset('storage/item/' . $product->image) }}" width="50%"></td>
                                            <td>{{ $product->name }}</td>
                                            <td>{{ $product->quantity }}</td>
                                            <td>{{ $product->category_id }} {{ $product->unit_id }}</td>
                                            <td>{{ $product->price }}</td>
                                            <td>{{ $product->selling_price }}</td>
                                            <td>{{ $product->supplier_id }}</td>
                                            <td>
                                                <a href="{{ route('#', $product->id) }}" class="btn btn-secondary"><i
                                                        class="fas fa-edit"></i></a>
                                                <form method="POST" action="{{ route('#', $product->id) }}">
                                                    @method('delete')
                                                    @csrf
                                                    <button type="submit" class="btn btn-danger"><i
                                                            class="fas fa-trash-alt"></i></button>
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                            <script>
                                $(document).ready(function () {
                                    $('#product').DataTable();
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
    <style>
        /* Change DataTables search bar text color */
        body.dark-mode .dataTables_filter input {
            color: white !important;
            background-color: #5d62714b !important;
            border: 1px solid #bbb !important;
            margin-bottom: 20px;
        }

        body.dark-mode .dataTables_filter input::placeholder {
            color: rgba(255, 255, 255, 0) !important;
        }

        body.dark-mode .dataTables_filter label {
            color: white !important;
        }

        body.dark-mode .dataTables_length label {
            color: white !important;
        }

        body.dark-mode .dataTables_info {
            color: white !important;
        }

        body.dark-mode .dataTables_empty {
            color: white !important;
            background-color: #182234;
            opacity: 70%;
        }
    </style>
@endpush
@endsection