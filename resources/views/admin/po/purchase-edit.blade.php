@extends('admin.layouts.base')

@section('title', 'Purchase Order')

@section('content')
    <div class="page-wrapper">
        <!-- Page header -->
        <div class="page-header">
            <div class="container-xl">
                <div class="row align-items-center">
                    <div class="col">
                        <div class="page-pretitle">Overview</div>
                        <h2 class="page-title">Edit Purchase Order</h2>
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
                                    action="{{ route('admin.po.update', $pos->id) }}">
                                    @csrf
                                    @method('PUT')

                                    <fieldset class="form-fieldset container-xl">
                                        <div class="row">
                                            <div class="col-md-1 mb-3">
                                                <label class="form-label">INVOICE</label>
                                                <input type="text" class="form-control" value="{{$pos->invoice}}"
                                                    disabled />
                                            </div>
                                            <div class="col-md-2 mb-3">
                                                <label class="form-label">ORDER DATE</label>
                                                <input type="text" class="form-control" value="{{$pos->order_date->format('d-m-Y')}}"
                                                    disabled />
                                            </div>
                                            <div class="col-md-2 mb-3">
                                                <label class="form-label">DUE DATE</label>
                                                <input type="text" class="form-control" value="{{$pos->due_date->format('d-m-Y')}}"
                                                    disabled />
                                            </div>
                                            @if($pos->status == 'Paid')
                                            <div class="col-md-3 mb-3 ms-auto text-end">
                                                <label class="form-label">Payment Date</label>
                                                <input type="text" class="form-control" value="{{ $pos->updated_at->format('d-m-Y H:i:s') }}" disabled />
                                            </div>
                                            @endif
                                        </div>

                                        <div class="row">
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label">SUPPLIER</label>
                                                <input type="text" class="form-control" value="{{$pos->supplier->name}}"
                                                    disabled />
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-2 mb-3">
                                                <label class="form-label">PAYMENT TYPE</label>
                                                <select class="form-control" name="payment_type" id="payment_type" {{ $pos->status == 'Paid' ? 'disabled' : '' }}>
                                                    <option value="Cash" {{ $pos->payment_type == 'Cash' ? 'selected' : '' }}>
                                                        Cash</option>
                                                    <option value="Transfer" {{ $pos->payment_type == 'Transfer' ? 'selected' : '' }}>Transfer</option>
                                                </select>
                                            </div>
                                            <div class="col-md-1 mb-3">
                                                <label class="form-label">STATUS</label>
                                                <select class="form-control" name="status" id="status" {{ $pos->status == 'Paid' ? 'disabled' : '' }}>
                                                    <option value="Paid" {{ $pos->status == 'Paid' ? 'selected' : '' }}>Paid
                                                    </option>
                                                    <option value="Unpaid" {{ $pos->status == 'Unpaid' ? 'selected' : '' }}>
                                                        Unpaid</option>
                                                </select>
                                            </div>
                                        </div>
                                    </fieldset>

                                    <table id="ptable" class="table table-responsive">
                                        <thead>
                                            <tr>
                                                <th>No</th>
                                                <th>Product</th>
                                                <th>Quantity</th>
                                                <th>Price</th>
                                                <th>Total</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($pos->items as $index => $item)
                                                <tr>
                                                    <td>{{$index + 1}}</td>
                                                    <td>{{ $item->product->name }}</td>
                                                    <td>{{ $item->quantity }}</td>
                                                    <td>{{ \App\Helpers\CurrencyHelper::format($item->price) }}</td>
                                                    <td>{{ \App\Helpers\CurrencyHelper::format($item->quantity * $item->price) }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                    <br>
                                    <h1 class="text-end">
                                        Total Invoice: <span id="totalPrice">
                                            {{ \App\Helpers\CurrencyHelper::format($pos->items->sum(fn($item) => $item->quantity * $item->price)) }}
                                        </span>
                                    </h1>

                                    <div class="text-end">
                                        @if($pos->status !== 'Paid')
                                            <button type="submit" class="btn btn-success">Save</button>
                                        @endif
                                    </div>

                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
        $(document).ready(function () {
            $('#ptable').DataTable();
        });
    </script>
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
