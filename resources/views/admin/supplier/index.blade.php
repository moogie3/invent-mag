@extends('admin.layouts.base')

@section('title', 'Supplier')

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
                            Supplier
                        </h2>
                    </div>
                    <div class="col-auto ms-auto">
                        <div class="btn-list">
                            <a href="{{ route('admin.supplier.create') }}" class="btn btn-primary d-none d-sm-inline-block">
                                <i class="ti ti-plus fs-4"></i>
                                Create Supplier
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
                            <div class="table-responsive" id="table-default">
                                <table class="table">
                                    <thead>
                                        <tr class="dark">
                                            <th>no</th>
                                            <th>Code</th>
                                            <th>Name</th>
                                            <th>Location</th>
                                            <th>Payment Terms</th>
                                            <th style="width: 180px; text-align: center;">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($suppliers as $supplier)
                                            <tr>
                                                <td>{{ $supplier->id }}</td>
                                                <td>{{ $supplier->code }}</td>
                                                <td>{{ $supplier->name }}</td>
                                                <td>{{ $supplier->location }}</td>
                                                <td>{{ $supplier->payment_terms }}</td>
                                                <td>
                                                    <div class="d-flex gap-2 justify-content-center">
                                                        <a href="{{ route('admin.supplier.edit', $supplier->id) }}"
                                                            class="btn btn-secondary"><i class="ti ti-edit"></i></a>
                                                        <form method="POST"
                                                            action="{{ route('admin.supplier.destroy', $supplier->id) }}">
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
                                        $('#suppliert').DataTable();
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
