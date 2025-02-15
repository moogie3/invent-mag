@extends('admin.layouts.base')

@section('title', 'Purchase Order')

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
                            Purchase Order
                        </h2>
                    </div>
                    <div class="col-auto ms-auto">
                        <div class="btn-list">
                            <a href="{{ route('admin.po.create') }}" class="btn btn-primary d-none d-sm-inline-block">
                                <i class="ti ti-plus fs-4"></i>
                                Create Purchase Order
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
                                            <th>Supplier</th>
                                            <th>Order Date</th>
                                            <th>Due Date</th>
                                            <th>Total</th>
                                            <th>Payment Type</th>
                                            <th>Status</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($pos as $index => $po)
                                            <tr>
                                                <td style="display: none;">{{ $po->id }}</td>
                                                <td>{{$index + 1}}</td>
                                                <td>{{ $po->invoice }}</td>
                                                <td>{{ $po->supplier->name }}</td>
                                                <td>{{ $po->order_date->format('d F Y') }}</td>
                                                <td>{{ $po->due_date->format('d F Y')}}</td>
                                                <td>{{ \App\Helpers\CurrencyHelper::format($po->total) }}</td>
                                                <td>{{ $po->payment_type }}</td>
                                                <td>{{ $po->status }}</td>
                                                <td>
                                                    <div class="d-flex gap-2 justify-content-center">
                                                        <a href="{{ route('admin.po.edit', $po->id) }}" class=" btn btn-secondary"><i class="ti ti-zoom-scan"></i></a>
                                                        <form method="POST" action="{{ route('admin.po.destroy', $po->id) }}">
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
