@extends('admin.layouts.base')

@section('title', 'Purchase Order')

@section('content')
    <div class="page-wrapper">
        <div class="page-header no-print">
            <div class="container-xl">
                <div class="row align-items-center">
                    <div class="col">
                        <div class="page-pretitle">Overview</div>
                        <h2 class="page-title no-print">View Invoice Information</h2>
                    </div>
                    <div class="col text-end">
                        <button type="button" class="btn btn-secondary" onclick="javascript:window.print();">
                            Export Invoice
                        </button>
                        <a href="{{ route('admin.po.edit', $pos->id) }}" class="btn btn-primary">Edit Invoice</a>
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
                                    action="{{ route('admin.po.update', $pos->id) }}">
                                    @csrf
                                    @method('PUT')
                                    <h1 class="text-center no-print">Invoice Information</h1>
                                    @if ($pos->status !== 'Paid')
                                        <fieldset class="form-fieldset no-print">
                                            <div class="row">
                                                <div class="col-md-2 mb-3">
                                                    <label class="form-label">PAYMENT TYPE</label>
                                                    <select class="form-control" name="payment_type" id="payment_type"
                                                        {{ $pos->status == 'Paid' ? 'disabled' : '' }}>
                                                        <option value="Cash"
                                                            {{ $pos->payment_type == 'Cash' ? 'selected' : '' }}>Cash
                                                        </option>
                                                        <option value="Transfer"
                                                            {{ $pos->payment_type == 'Transfer' ? 'selected' : '' }}>
                                                            Transfer</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-1 mb-3">
                                                    <label class="form-label">STATUS</label>
                                                    <select class="form-control" name="status" id="status"
                                                        {{ $pos->status == 'Paid' ? 'disabled' : '' }}>
                                                        <option value="Paid"
                                                            {{ $pos->status == 'Paid' ? 'selected' : '' }}>Paid</option>
                                                        <option value="Unpaid"
                                                            {{ $pos->status == 'Unpaid' ? 'selected' : '' }}>Unpaid</option>
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
                                                            <h3>PO / {{ $pos->invoice }} / {{ $pos->supplier->location }} /
                                                                {{ $pos->supplier->code }}</h3>
                                                        </div>
                                                        <div class="row">
                                                            <div class="col-6">
                                                                <p class="h1">{{ $pos->supplier->name }}</p>
                                                                <address>
                                                                    {{ $pos->supplier->address }}<br>
                                                                    {{ $pos->supplier->phone_number }}<br>
                                                                    Order Date :
                                                                    {{ $pos->order_date->format('d-m-Y') }}<br>
                                                                    Due Date : {{ $pos->due_date->format('d-m-Y') }}
                                                                </address>
                                                            </div>
                                                            <div class="col-6 text-end">
                                                                <p class="h3">Payment Status</p>
                                                                <h3>
                                                                    @if ($pos->status !== 'Paid')
                                                                        <span class="badge bg-blue-lt">Payment
                                                                            Pending</span>
                                                                    @else
                                                                        <span class="badge bg-green-lt">Paid in
                                                                            {{ $pos->payment_date->format('d F Y') }}<br>{{ $pos->payment_date->format('H:i:s') }}</span>
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
                                                                    <th class="text-end" style="width: 10%">Discount</th>
                                                                    <th class="text-end" style="width: 20%">Amount</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                @foreach ($pos->items as $index => $item)
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
                                                                        <td class="text-end">
                                                                            {{ $item->discount }}%
                                                                        </td>
                                                                        <td class="text-end">
                                                                            {{ \App\Helpers\CurrencyHelper::format($item->quantity * $item->price - $item->discount) }}
                                                                        </td>
                                                                    </tr>
                                                                @endforeach
                                                            </tbody>
                                                        </table>
                                                        <br>
                                                        <h2 class="text-end">
                                                            Total Amount: <span id="totalPrice">
                                                                {{ \App\Helpers\CurrencyHelper::format(
                                                                    $pos->items->sum(fn($item) => $item->quantity * $item->price * (1 - $item->discount / 100)),
                                                                ) }}
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
