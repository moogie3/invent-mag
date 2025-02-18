@extends('admin.layouts.base')

@section('title', 'Sales Order')

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
                            Sales Order
                        </h2>
                    </div>
                    <div class="col-auto ms-auto">
                        <div class="btn-list">
                            <a href="{{ route('admin.po.create') }}" class="btn btn-primary d-none d-sm-inline-block">
                                <i class="ti ti-plus fs-4"></i>
                                Create Sales Order
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
                                <table id="pott" class="table table-responsive">
                                    <thead>
                                        <tr class="dark">
                                            <th style="display:none;">id</th>
                                            <th>no</th>
                                            <th>Invoice</th>
                                            <th>Customer</th>
                                            <th>Order Date</th>
                                            <th>Total</th>
                                            <th>Payment Type</th>
                                            <th>Status</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    </tbody>
                                </table>
                                <script>
                                    $(document).ready(function () {
                                        $('#pott').DataTable();
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
