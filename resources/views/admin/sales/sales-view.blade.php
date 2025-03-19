@extends('admin.layouts.base')

@section('title', 'Sales Order')

@section('content')
    <div class="page-wrapper">
        <div class="page-header no-print">
            <div class="container-xl">
                <div class="row align-items-center">
                    <div class="col">
                        <div class="page-pretitle">Overview</div>
                        <h2 class="page-title no-print">View Sales Invoice</h2>
                    </div>
                    <div class="col text-end">
                        <button type="button" class="btn btn-secondary" onclick="javascript:window.print();">
                            Export Invoice
                        </button>
                        <a href="{{ route('admin.sales.edit', $sales->id) }}" class="btn btn-primary">Edit Invoice</a>
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
                                    <h1 class="text-center no-print">Invoice Details</h1>
                                    @if ($sales->status !== 'Paid')
                                        <fieldset class="form-fieldset no-print">
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
                                                <div class="col-md-9 mb-3 mt-4 text-end">
                                                    <button type="submit" class="btn btn-success">Save</button>
                                                </div>
                                            </div>
                                        </fieldset>
                                    @endif
                                    <div class="page-wrapper">
                                        <div class="page-body">
                                            <div class="container-xl">
                                                <div class="card card-lg">
                                                    <div class="card-body">
                                                        <div class="col-auto">
                                                            <h3>SALE / {{ $sales->invoice }} /
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
                                                                        <span class="badge bg-green-lt">
                                                                            Paid on
                                                                            {{ $sales->payment_date->format('d F Y') }}<br>
                                                                            {{ $sales->payment_date->format('H:i:s') }}
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
                                                                    <th class="text-end" style="width: 15%">Unit Price</th>
                                                                    <th class="text-end" style="width: 10%">Discount (%)
                                                                    </th> {{-- Keep percentage --}}
                                                                    <th class="text-end" style="width: 15%">Total</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                @foreach ($sales->items as $index => $item)
                                                                    @php
                                                                        $discountAmount =
                                                                            $item->price *
                                                                            $item->quantity *
                                                                            ($item->discount / 100);
                                                                        $totalAmount =
                                                                            $item->price * $item->quantity -
                                                                            $discountAmount;
                                                                    @endphp
                                                                    <tr>
                                                                        <td class="text-center">{{ $index + 1 }}</td>
                                                                        <td>
                                                                            <p class="strong mb-1">
                                                                                {{ $item->product->name }}</p>
                                                                        </td>
                                                                        <td class="text-center">{{ $item->quantity }}</td>
                                                                        <td class="text-end">
                                                                            {{ \App\Helpers\CurrencyHelper::format($item->price) }}
                                                                        </td>
                                                                        <td class="text-end">{{ $item->discount }}%</td>
                                                                        {{-- Keep percentage --}}
                                                                        <td class="text-end">
                                                                            {{ \App\Helpers\CurrencyHelper::format($totalAmount) }}
                                                                        </td>
                                                                    </tr>
                                                                @endforeach
                                                            </tbody>
                                                        </table>

                                                        <br>
                                                        @php
                                                            $totalDiscount = $sales->items->sum(
                                                                fn($item) => $item->price *
                                                                    $item->quantity *
                                                                    ($item->discount / 100),
                                                            );
                                                            $grandTotal =
                                                                $sales->items->sum(
                                                                    fn($item) => $item->price * $item->quantity,
                                                                ) - $totalDiscount;
                                                        @endphp

                                                        <div class="row">
                                                            <div class="col-6">
                                                                <h2 class="text-end">
                                                                    Total Discount: <span id="totalDiscount">
                                                                        {{ \App\Helpers\CurrencyHelper::format($totalDiscount) }}
                                                                    </span>
                                                                </h2>
                                                            </div>
                                                            <div class="col-6">
                                                                <h2 class="text-end">
                                                                    Total Amount: <span id="totalPrice">
                                                                        {{ \App\Helpers\CurrencyHelper::format($grandTotal) }}
                                                                    </span>
                                                                </h2>
                                                            </div>
                                                        </div>
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
