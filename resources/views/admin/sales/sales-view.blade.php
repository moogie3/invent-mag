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
                                <h1 class="text-center mt-3 no-print">Invoice Details {{ $sales->invoice }}
                                    {{ $sales->customer->name }}</h1>
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
                                                                Order Date : {{ $sales->order_date->format('d-m-Y') }}<br>
                                                            </address>
                                                        </div>
                                                        <div class="col-6 text-end">
                                                            <p class="h3">Payment Status</p>
                                                            <h3>
                                                                @if ($sales->status !== 'Paid')
                                                                    <span class="badge bg-blue-lt">Payment Pending</span>
                                                                @else
                                                                    <span class="badge bg-green-lt">
                                                                        Paid on
                                                                        {{ $sales->payment_date->format('d F Y') }}<br>
                                                                        {{ $sales->payment_date->format('H:i:s') }}
                                                                    </span>
                                                                @endif
                                                            </h3>
                                                            <address>
                                                                Due Date : {{ $sales->due_date->format('d-m-Y') }}<br>
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
                                                                <th class="text-end" style="width: 15%">Discount Type</th>
                                                                <th class="text-end" style="width: 15%">Discount</th>
                                                                <th class="text-end" style="width: 15%">Total</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            @foreach ($sales->items as $index => $item)
                                                                @php
                                                                    if ($item->discount_type === 'percentage') {
                                                                        $discountAmount =
                                                                            $item->customer_price *
                                                                            ($item->discount / 100) *
                                                                            $item->quantity;
                                                                    } else {
                                                                        $discountAmount =
                                                                            $item->discount * $item->quantity;
                                                                    }

                                                                    $itemTotal =
                                                                        $item->customer_price * $item->quantity -
                                                                        $discountAmount;
                                                                @endphp
                                                                <tr>
                                                                    <td class="text-center">{{ $index + 1 }}</td>
                                                                    <td>
                                                                        <p class="strong mb-1">{{ $item->product->name }}
                                                                        </p>
                                                                    </td>
                                                                    <td class="text-center">{{ $item->quantity }}</td>
                                                                    <td class="text-end">
                                                                        {{ \App\Helpers\CurrencyHelper::format($item->customer_price) }}
                                                                    </td>
                                                                    <td class="text-end">
                                                                        {{ $item->discount_type === 'percentage' ? 'Percentage' : 'Fixed' }}
                                                                    </td>
                                                                    <td class="text-end">
                                                                        @if ($item->discount_type === 'percentage')
                                                                            {{ $item->discount }}%
                                                                        @else
                                                                            {{ \App\Helpers\CurrencyHelper::format($item->discount) }}
                                                                        @endif
                                                                    </td>
                                                                    <td class="text-end">
                                                                        {{ \App\Helpers\CurrencyHelper::format($itemTotal) }}
                                                                    </td>
                                                                </tr>
                                                            @endforeach
                                                        </tbody>

                                                        @php
                                                            // Sum all item totals and discounts
                                                            $totalBeforeDiscount = $sales->items->sum(
                                                                fn($item) => $item->customer_price * $item->quantity,
                                                            );
                                                            $totalItemDiscount = $sales->items->sum(function ($item) {
                                                                return $item->discount_type === 'percentage'
                                                                    ? $item->customer_price *
                                                                            ($item->discount / 100) *
                                                                            $item->quantity
                                                                    : $item->discount * $item->quantity;
                                                            });
                                                            $subTotal = $totalBeforeDiscount - $totalItemDiscount;

                                                            // Order discount
                                                            $orderDiscount = $sales->order_discount ?? 0;
                                                            $orderDiscountType = $sales->order_discount_type ?? 'fixed';
                                                            $orderDiscountAmount =
                                                                $orderDiscountType === 'percentage'
                                                                    ? $totalBeforeDiscount * ($orderDiscount / 100)
                                                                    : $orderDiscount;

                                                            // Tax after order discount
                                                            $taxableAmount = $subTotal - $orderDiscountAmount;
                                                            $taxRate = $sales->tax_rate ?? 0;
                                                            $taxAmount =
                                                                $taxRate > 0 ? $taxableAmount * ($taxRate / 100) : 0;

                                                            $grandTotal = $taxableAmount + $taxAmount;
                                                        @endphp

                                                        <tfoot>
                                                            <tr>
                                                                <td colspan="6" class="text-end"><strong>Sub
                                                                        Total:</strong></td>
                                                                <td class="text-end">
                                                                    {{ \App\Helpers\CurrencyHelper::format($subTotal) }}
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <td colspan="6" class="text-end"><strong>Order
                                                                        Discount:</strong></td>
                                                                <td class="text-end">
                                                                    {{ \App\Helpers\CurrencyHelper::format($orderDiscountAmount) }}
                                                                </td>
                                                            </tr>
                                                            @if ($taxRate > 0)
                                                                <tr>
                                                                    <td colspan="6" class="text-end"><strong>Tax
                                                                            ({{ $taxRate }}%):</strong></td>
                                                                    <td class="text-end">
                                                                        {{ \App\Helpers\CurrencyHelper::format($taxAmount) }}
                                                                    </td>
                                                                </tr>
                                                            @endif
                                                            <tr>
                                                                <td colspan="6" class="text-end"><strong>Grand
                                                                        Total:</strong></td>
                                                                <td class="text-end">
                                                                    {{ \App\Helpers\CurrencyHelper::format($grandTotal) }}
                                                                </td>
                                                            </tr>
                                                        </tfoot>
                                                    </table>

                                                    <input type="hidden" id="totalDiscountInput" name="total_discount"
                                                        value="{{ $orderDiscountAmount }}">
                                                    <input type="hidden" id="taxInput" name="tax_amount"
                                                        value="{{ $taxAmount }}">
                                                </div> {{-- End Card Body --}}
                                            </div> {{-- End Card --}}
                                        </div>
                                    </div>
                                </div>
                            </div> {{-- End Card Body --}}
                        </div> {{-- End Card --}}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
