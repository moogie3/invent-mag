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
                                                                <address>
                                                                    Due Date :
                                                                    {{ $sales->due_date->format('d-m-Y') }}<br>
                                                                </address>
                                                            </div>
                                                        </div>
                                                        <table class="table table-transparent table-responsive">
                                                            <thead>
                                                                <tr>
                                                                    <th class="text-center" style="width: 1%">No</th>
                                                                    <th>Product</th>
                                                                    <th class="text-center" style="width: 1%">QTY</th>
                                                                    <th class="text-end" style="width: 15%">Unit Price</th>
                                                                    <th class="text-end" style="width: 15%">Discount Type
                                                                    </th>
                                                                    <th class="text-end" style="width: 15%">Discount</th>
                                                                    <th class="text-end" style="width: 15%">Total</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                @foreach ($sales->items as $index => $item)
                                                                    @php
                                                                        $discountAmount =
                                                                            $item->discount_type === 'percentage'
                                                                                ? $item->price *
                                                                                    $item->quantity *
                                                                                    ($item->discount / 100)
                                                                                : $item->discount;
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
                                                                        <td class="text-end">
                                                                            @if ($item->discount_type === 'percentage')
                                                                                Percentage
                                                                            @else
                                                                                Fixed
                                                                            @endif
                                                                        </td>
                                                                        <td class="text-end">
                                                                            @if ($item->discount_type === 'percentage')
                                                                                {{ $item->discount }}%
                                                                            @else
                                                                                {{ \App\Helpers\CurrencyHelper::format($item->discount) }}
                                                                            @endif
                                                                        </td>
                                                                        <td class="text-end">
                                                                            {{ \App\Helpers\CurrencyHelper::format($totalAmount) }}
                                                                        </td>
                                                                    </tr>
                                                                @endforeach
                                                            </tbody>
                                                            @php
                                                                // Calculate total discount as a fixed amount
                                                                $totalDiscount = $sales->items->sum(function ($item) {
                                                                    return $item->discount_type === 'percentage'
                                                                        ? $item->price *
                                                                                $item->quantity *
                                                                                ($item->discount / 100)
                                                                        : // Apply percentage discount to total item price
                                                                        $item->discount; // Fixed discount is applied only once per item, not per unit
                                                                });

                                                                // Calculate total before discount
                                                                $totalBeforeDiscount = $sales->items->sum(
                                                                    fn($item) => $item->price * $item->quantity,
                                                                );

                                                                // Apply discount correctly
                                                                $subTotal = $totalBeforeDiscount - $totalDiscount;

                                                                // Calculate tax amount if applicable
                                                                $taxAmount =
                                                                    isset($tax) && $tax->is_active
                                                                        ? $subTotal * ($tax->rate / 100)
                                                                        : 0;
                                                                $grandTotal = $subTotal + $taxAmount;
                                                            @endphp

                                                            @php
                                                                $totals = \App\Helpers\SalesHelper::calculateTotals(
                                                                    $sales->items,
                                                                    $tax->rate,
                                                                );
                                                            @endphp
                                                            <tfoot>
                                                                <tr>
                                                                    <td colspan="6" class="text-end"><strong>Sub
                                                                            Total :</strong></td>
                                                                    <td class="text-end">
                                                                        {{ \App\Helpers\CurrencyHelper::format($totals['subTotal']) }}
                                                                    </td>
                                                                </tr>
                                                                <tr>
                                                                    <td colspan="6" class="text-end">
                                                                        <strong>Discount :</strong>
                                                                    </td>
                                                                    <td class="text-end">
                                                                        {{ \App\Helpers\CurrencyHelper::format($totals['totalDiscount']) }}
                                                                    </td>
                                                                </tr>
                                                                @if (isset($tax) && $tax->is_active)
                                                                    <tr>
                                                                        <td colspan="6" class="text-end"><strong>Tax
                                                                                ({{ $tax->rate }}%) :</strong></td>
                                                                        <td class="text-end">
                                                                            {{ \App\Helpers\CurrencyHelper::format($totals['taxAmount']) }}
                                                                        </td>
                                                                    </tr>
                                                                @endif
                                                                <tr>
                                                                    <td colspan="6" class="text-end"><strong>Grand
                                                                            Total :</strong></td>
                                                                    <td class="text-end">
                                                                        {{ \App\Helpers\CurrencyHelper::format($totals['grandTotal']) }}
                                                                    </td>
                                                                </tr>
                                                            </tfoot>
                                                        </table>
                                                        <input type="hidden" id="totalDiscountInput" name="total_discount"
                                                            value="{{ $totalDiscount }}">
                                                        <input type="hidden" id="taxInput" name="tax_amount"
                                                            value="{{ $taxAmount }}">
                                                    </div> {{-- End of Card Body --}}
                                                </div> {{-- End of Card --}}
                                            </div>
                                        </div>
                                    </div> {{-- End of Page Wrapper --}}
                                </form>
                            </div> {{-- End of Card Body --}}
                        </div> {{-- End of Card --}}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
