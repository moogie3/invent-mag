@extends('admin.layouts.base')

@section('title', 'Purchase Order')

@section('content')
    <div class="page-wrapper">
        <div class="page-header no-print">
            <div class="container-xl">
                <div class="row align-items-center">
                    <div class="col">
                        <div class="page-pretitle">Overview</div>
                        <h2 class="page-title no-print">View PO Invoice</h2>
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
                                <h1 class="text-center mt-3 no-print">Invoice Details {{ $pos->invoice }}
                                    {{ $pos->supplier->code }}</h1>
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
                                                                {{ $pos->order_date->format('d-m-Y') }}
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
                                                                        {{ $pos->payment_date->setTimezone(auth()->user()->timezone)->format('d F Y') }}<br>
                                                                        {{ $pos->payment_date->setTimezone(auth()->user()->timezone)->format('H:i:s') }}</span>
                                                                @endif
                                                            </h3>
                                                            <address>
                                                                Due Date : {{ $pos->due_date->format('d-m-Y') }}</br>
                                                            </address>
                                                        </div>
                                                    </div>
                                                    <table class="table table-transparent table-responsive">
                                                        <thead>
                                                            <tr>
                                                                <th class="text-center">No</th>
                                                                <th>Product</th>
                                                                <th class="text-center">QTY</th>
                                                                <th class="text-end">Price</th>
                                                                <th class="text-end">Unit Discount</th>
                                                                <th class="text-end">Amount</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            @php
                                                                $subtotal = 0;
                                                                $totalProductDiscount = 0;
                                                            @endphp

                                                            @foreach ($pos->items as $index => $item)
                                                                @php
                                                                    // Use the helper class to calculate the final amount
                                                                    $finalAmount = \App\Helpers\PurchaseHelper::calculateTotal(
                                                                        $item->price,
                                                                        $item->quantity,
                                                                        $item->discount,
                                                                        $item->discount_type,
                                                                    );

                                                                    // Calculate the discount per unit for display
                                                                    $discountPerUnit =
                                                                        $item->discount_type === 'percentage'
                                                                            ? ($item->price * $item->discount) / 100
                                                                            : $item->discount;

                                                                    // Add to subtotal
                                                                    $subtotal += $finalAmount;
                                                                    $totalProductDiscount +=
                                                                        $discountPerUnit * $item->quantity;
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
                                                                    <td class="text-end">
                                                                        {{ $item->discount_type === 'percentage' ? $item->discount . '%' : \App\Helpers\CurrencyHelper::format($item->discount) }}
                                                                    </td>
                                                                    <td class="text-end">
                                                                        {{ \App\Helpers\CurrencyHelper::format($finalAmount) }}
                                                                    </td>
                                                                </tr>
                                                            @endforeach

                                                            @php
                                                                // Use the helper class to calculate order-level discount
                                                                $orderDiscount = \App\Helpers\PurchaseHelper::calculateDiscount(
                                                                    $subtotal,
                                                                    $pos->discount_total,
                                                                    $pos->discount_total_type,
                                                                );

                                                                // Final total after order-level discount
                                                                $finalTotal = $subtotal - $orderDiscount;
                                                            @endphp
                                                        </tbody>
                                                        <tfoot>
                                                            <tr>
                                                                <td colspan="5" class="text-end">
                                                                    <strong>Sub Total :</strong>
                                                                </td>
                                                                <td class="text-end">
                                                                    {{ \App\Helpers\CurrencyHelper::format($subtotal) }}
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <td colspan="5" class="text-end">
                                                                    <strong>Order Discount :</strong>
                                                                </td>
                                                                <td class="text-end">
                                                                    <small>({{ $pos->discount_total_type === 'percentage' ? $pos->discount_total . '%' : 'Fixed' }})</small>
                                                                    {{ \App\Helpers\CurrencyHelper::format($orderDiscount) }}
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <td colspan="5" class="text-end">
                                                                    <strong>Grand Total :</strong>
                                                                </td>
                                                                <td class="text-end">
                                                                    {{ \App\Helpers\CurrencyHelper::format($finalTotal) }}
                                                                </td>
                                                            </tr>
                                                        </tfoot>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
