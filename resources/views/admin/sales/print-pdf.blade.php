<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Sales Invoice #{{ $sales->invoice }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            color: #333;
        }
        .header {
            width: 100%;
            margin-bottom: 30px;
        }
        .header-left {
            float: left;
        }
        .header-right {
            float: right;
            text-align: right;
        }
        .company-name {
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .invoice-title {
            font-size: 24px;
            font-weight: bold;
            color: #333;
            margin-bottom: 10px;
        }
        .info-table {
            width: 100%;
            margin-bottom: 20px;
        }
        .info-table td {
            vertical-align: top;
            padding: 5px 0;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .items-table th, .items-table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        .items-table th {
            background-color: #f8f9fa;
            font-weight: bold;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .totals-table {
            width: 40%;
            float: right;
            border-collapse: collapse;
        }
        .totals-table td {
            padding: 5px;
            text-align: right;
        }
        .totals-table .total-row {
            font-weight: bold;
            font-size: 14px;
            border-top: 2px solid #333;
        }
        .status-badge {
            padding: 5px 10px;
            background-color: #eee;
            border-radius: 4px;
            font-weight: bold;
            display: inline-block;
        }
        .clearfix::after {
            content: "";
            clear: both;
            display: table;
        }
    </style>
</head>
<body>
    <div class="header clearfix">
        <div class="header-left">
            <div class="company-name">{{ $shopname ?? 'Inventory Manager' }}</div>
            <div>{{ $address ?? '' }}</div>
        </div>
        <div class="header-right">
            <div class="invoice-title">INVOICE</div>
            <div><strong>#{{ $sales->invoice }}</strong></div>
            <div>Date: {{ $sales->order_date->format('d F Y') }}</div>
            <div>Due Date: {{ $sales->due_date->format('d F Y') }}</div>
            <br>
            <div class="status-badge">{{ ucfirst($sales->status) }}</div>
        </div>
    </div>

    <table class="info-table">
        <tr>
            <td style="width: 50%;">
                <strong>Bill To:</strong><br>
                {{ $sales->customer->name }}<br>
                {{ $sales->customer->address }}<br>
                {{ $sales->customer->phone_number }}
            </td>
            <td style="width: 50%;">
                <!-- Additional Info if needed -->
            </td>
        </tr>
    </table>

    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 5%">#</th>
                <th style="width: 40%">Product</th>
                <th style="width: 10%" class="text-center">Qty</th>
                <th style="width: 15%" class="text-right">Price</th>
                <th style="width: 15%" class="text-right">Discount</th>
                <th style="width: 15%" class="text-right">Amount</th>
            </tr>
        </thead>
        <tbody>
            @php
                $summary = \App\Helpers\SalesHelper::calculateInvoiceSummary(
                    $sales->salesItems,
                    $sales->order_discount ?? 0,
                    $sales->order_discount_type ?? 'fixed',
                    $sales->tax_rate ?? 0,
                );
            @endphp
            @foreach($sales->salesItems as $index => $item)
                @php
                    $finalAmount = \App\Helpers\SalesHelper::calculateTotal(
                        $item->customer_price,
                        $item->quantity,
                        $item->discount,
                        $item->discount_type
                    );
                @endphp
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>
                        {{ $item->product->name ?? 'N/A' }}
                        @if(isset($item->product->sku))
                            <br><small style="color: #666">SKU: {{ $item->product->sku }}</small>
                        @endif
                    </td>
                    <td class="text-center">{{ $item->quantity }}</td>
                    <td class="text-right">{{ \App\Helpers\CurrencyHelper::formatWithPosition($item->customer_price) }}</td>
                    <td class="text-right">
                        @if($item->discount > 0)
                            {{ $item->discount_type === 'percentage' ? $item->discount . '%' : \App\Helpers\CurrencyHelper::formatWithPosition($item->discount) }}
                        @else
                            -
                        @endif
                    </td>
                    <td class="text-right">{{ \App\Helpers\CurrencyHelper::formatWithPosition($finalAmount) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="clearfix">
        <table class="totals-table">
            <tr>
                <td>Subtotal:</td>
                <td>{{ \App\Helpers\CurrencyHelper::formatWithPosition($summary['subtotal']) }}</td>
            </tr>
            @if($summary['orderDiscount'] > 0)
            <tr>
                <td>Order Discount:</td>
                <td>-{{ \App\Helpers\CurrencyHelper::formatWithPosition($summary['orderDiscount']) }}</td>
            </tr>
            @endif
            <tr>
                <td>Tax ({{ $sales->tax_rate }}%):</td>
                <td>{{ \App\Helpers\CurrencyHelper::formatWithPosition($summary['taxAmount']) }}</td>
            </tr>
            <tr class="total-row">
                <td>Grand Total:</td>
                <td>{{ \App\Helpers\CurrencyHelper::formatWithPosition($summary['finalTotal']) }}</td>
            </tr>
            <tr>
                <td>Paid:</td>
                <td>{{ \App\Helpers\CurrencyHelper::formatWithPosition($sales->total_paid) }}</td>
            </tr>
            <tr>
                <td>Balance:</td>
                <td>{{ \App\Helpers\CurrencyHelper::formatWithPosition($sales->balance) }}</td>
            </tr>
        </table>
    </div>

    @if($sales->notes)
        <div style="margin-top: 30px; border-top: 1px solid #eee; padding-top: 10px;">
            <strong>Notes:</strong><br>
            {{ $sales->notes }}
        </div>
    @endif

</body>
</html>
