@extends('admin.layouts.base')

@section('title', 'Customer')

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
                        Customer
                    </h2>
                </div>
                <div class="col-auto ms-auto">
                    <div class="btn-list">
                        <a href="{{ route('admin.customer.create') }}" class="btn btn-primary d-none d-sm-inline-block">
                            <i class="ti ti-plus fs-4"></i>
                            Create Customer
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
                            <table id="customert" class="table table-responsive">
                                <thead>
                                    <tr class="dark">
                                        <th>no</th>
                                        <th>Name</th>
                                        <th>Address</th>
                                        <th>Phone Number</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($customers as $customer)
                                        <tr>
                                            <td>{{ $customer->id }}</td>
                                            <td>{{ $customer->name }}</td>
                                            <td>{{ $customer->address }}</td>
                                            <td>{{ $customer->phone_number }}</td>
                                            <td>
                                                <div class="d-flex gap-2 justify-content-center">
                                                    <a href="{{ route('admin.customer.edit', $customer->id) }}"
                                                        class="btn btn-secondary"><i class="ti ti-edit"></i></a>
                                                    <form method="POST"
                                                        action="{{ route('admin.customer.destroy', $customer->id) }}">
                                                        @method('delete')
                                                        @csrf
                                                        <button type="submit" class="btn btn-danger"><i
                                                                class="ti ti-trash"></i></button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                            <script>
                                $(document).ready(function () {
                                    $('#customert').DataTable();
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
