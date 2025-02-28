@extends('admin.layouts.base')

@section('title', 'Sales')

@section('content')
    <div class="page-wrapper">
        <div class="page-header">
            <div class="container-xl">
                <div class="row align-items-center">
                    <div class="col">
                        @if ($sales->status == 'Unpaid')
                            <div class="page-pretitle">Overview</div>
                            <h2 class="page-title">Edit Sales Order</h2>
                        @else
                            <div class="page-pretitle">Overview</div>
                            <h2 class="page-title">Sales Invoice Information</h2>
                        @endif
                    </div>
                    <div class="col text-end">
                        <button type="button" class="btn btn-primary" onclick="javascript:window.print();">
                            Print Invoice
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="page-body">
            <div class="container-xl">
                <div class="row row-deck row-cards">
                    <div class="col-md-12">
                        <div class="card card-primary">
                            <div class="card-body">
                                <form enctype="multipart/form-data" method="POST"
                                    action="{{ route('admin.sales.update', $sales->id) }}">
                                    @csrf
                                    @method('PUT')
                                    @if ($sales->status !== 'Paid')
                                        <fieldset class="form-fieldset">
                                            <div class="row">
                                                <div class="col-md-2 mb-3">
                                                    <label class="form-label">PAYMENT TYPE</label>
                                                    <select class="form-control" name="payment_type" id="payment_type"
                                                        {{ $sales->status == 'Paid' ? 'disabled' : '' }}>
                                                        <option value="Cash"
                                                            {{ $sales->payment_type == 'Cash' ? 'selected' : '' }}>
                                                            Cash</option>
                                                        <option value="Transfer"
                                                            {{ $sales->payment_type == 'Transfer' ? 'selected' : '' }}>
                                                            Transfer</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-1 mb-3">
                                                    <label class="form-label">STATUS</label>
                                                    <select class="form-control" name="status" id="status"
                                                        {{ $sales->status == 'Paid' ? 'disabled' : '' }}>
                                                        <option value="Paid"
                                                            {{ $sales->status == 'Paid' ? 'selected' : '' }}>Paid
                                                        </option>
                                                        <option value="Unpaid"
                                                            {{ $sales->status == 'Unpaid' ? 'selected' : '' }}>
                                                            Unpaid</option>
                                                    </select>
                                                </div>
                                                @if ($sales->status !== 'Paid')
                                                    <div class="col-md-9 mb-3 mt-4 text-end">
                                                        <button type="submit" class="btn btn-success">Save</button>
                                                    </div>
                                                @endif
                                            </div>
                                        </fieldset>
                                    @endif
                                    <div class="page-wrapper">
                                        <div class="page-body">
                                            <div class="container-xl">
                                                <div class="card card-lg">
                                                    <div class="card-body">
                                                        <div class="col-auto">
                                                            <h3>SALES / {{ $sales->invoice }} /
                                                                {{ $sales->customer->address }}
                                                            </h3>
                                                        </div>
                                                        <div class="row">
                                                            <div class="col-6">
                                                                <p class="h1">{{ $sales->customer->name }}</p>
                                                                <address>
                                                                    {{ $sales->customer->address }}<br>
                                                                    {{ $sales->customer->phone_number }}<br>
                                                                    Order Date :
                                                                    {{ $sales->order_date->format('d-m-Y') }}<br>
                                                                </address>
                                                            </div>
                                                            <div class="col-6 text-end">
                                                                <p class="h3">Payment Status</p>
                                                                <h3>
                                                                    @if ($sales->status !== 'Paid')
                                                                        <span class="badge bg-blue-lt">
                                                                            Payment Pending
                                                                        </span>
                                                                    @else
                                                                        <span class="badge bg-green-lt">Paid in
                                                                            {{ $sales->payment_date->format('d F Y') }}<br>
                                                                            {{ $sales->payment_date->format('H:i:s') }}</i>
                                                                        </span>
                                                                    @endif
                                                                </h3>
                                                            </div>
                                                        </div>
                                                        <table class="table table-transparent table-responsive">
                                                            <thead>
                                                                <tr>
                                                                    <th class="text-center" style="width: 1%">No</th>
                                                                    <th>Product</th>
                                                                    <th class="text-center" style="width: 1%">QTY</th>
                                                                    <th class="text-end" style="width: 20%">Unit</th>
                                                                    <th class="text-end" style="width: 20%">Amount</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                @foreach ($sales->items as $index => $item)
                                                                    <tr>
                                                                        <td>{{ $index + 1 }}</td>
                                                                        <td>
                                                                            <p class="strong mb-1">
                                                                                {{ $item->product->name }}</p>
                                                                        </td>
                                                                        <td>{{ $item->quantity }}</td>
                                                                        <td class="text-end">
                                                                            {{ \App\Helpers\CurrencyHelper::format($item->price) }}
                                                                        </td>
                                                                        <td class="text-end">
                                                                            {{ \App\Helpers\CurrencyHelper::format($item->quantity * $item->price) }}
                                                                        </td>
                                                                    </tr>
                                                                @endforeach
                                                            </tbody>
                                                        </table>
                                                        <br>
                                                        <h2 class="text-end">
                                                            Total Amount : <span id="totalPrice">
                                                                {{ \App\Helpers\CurrencyHelper::format($sales->items->sum(fn($item) => $item->quantity * $item->price)) }}
                                                            </span>
                                                        </h2>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
