<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Quotation #{{ $opportunity->id }}</title>
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
            <div class="invoice-title">QUOTATION / ESTIMATE</div>
            <div><strong>#{{ str_pad($opportunity->id, 5, '0', STR_PAD_LEFT) }}</strong></div>
            <div>Date: {{ now()->format('d F Y') }}</div>
            @if($opportunity->expected_close_date)
            <div>Valid Until: {{ $opportunity->expected_close_date->format('d F Y') }}</div>
            @endif
            <br>
            <div class="status-badge">{{ ucfirst($opportunity->status) }}</div>
        </div>
    </div>

    <table class="info-table">
        <tr>
            <td style="width: 50%;">
                <strong>Quotation For:</strong><br>
                {{ $opportunity->customer->name }}<br>
                {{ $opportunity->customer->address }}<br>
                {{ $opportunity->customer->phone_number }}
            </td>
            <td style="width: 50%;">
                <table style="width: 100%; font-size: 12px; border-collapse: collapse;">
                    <tr>
                        <td style="padding-bottom: 5px; width: 40%;"><strong>Opportunity Name:</strong></td>
                        <td style="padding-bottom: 5px; width: 60%;">{{ $opportunity->name }}</td>
                    </tr>
                    <tr>
                        <td style="padding-bottom: 5px;"><strong>Pipeline Stage:</strong></td>
                        <td style="padding-bottom: 5px;">{{ $opportunity->stage->name ?? 'N/A' }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 5%">#</th>
                <th style="width: 45%">Product</th>
                <th style="width: 15%" class="text-center">Qty</th>
                <th style="width: 15%" class="text-right">Price</th>
                <th style="width: 20%" class="text-right">Total</th>
            </tr>
        </thead>
        <tbody>
            @php
                $subtotal = 0;
            @endphp
            @foreach($opportunity->items as $index => $item)
                @php
                    $lineTotal = $item->quantity * $item->price;
                    $subtotal += $lineTotal;
                @endphp
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>
                        {{ $item->product->name ?? 'Unknown Product' }}
                        @if(isset($item->product->code))
                            <br><small style="color: #666">Code: {{ $item->product->code }}</small>
                        @endif
                    </td>
                    <td class="text-center">{{ $item->quantity }}</td>
                    <td class="text-right">{{ \App\Helpers\CurrencyHelper::formatWithPosition($item->price) }}</td>
                    <td class="text-right">{{ \App\Helpers\CurrencyHelper::formatWithPosition($lineTotal) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="clearfix">
        <table class="totals-table">
            <tr>
                <td>Subtotal:</td>
                <td>{{ \App\Helpers\CurrencyHelper::formatWithPosition($subtotal) }}</td>
            </tr>
            @php
                $tax = \App\Models\Tax::where('is_active', 1)->first();
                $taxRate = $tax ? $tax->rate : 0;
                $taxAmount = $subtotal * ($taxRate / 100);
                $grandTotal = $subtotal + $taxAmount;
            @endphp
            @if($taxRate > 0)
            <tr>
                <td>Tax ({{ $taxRate }}%):</td>
                <td>{{ \App\Helpers\CurrencyHelper::formatWithPosition($taxAmount) }}</td>
            </tr>
            @endif
            <tr class="total-row">
                <td>Estimated Total:</td>
                <td>{{ \App\Helpers\CurrencyHelper::formatWithPosition($grandTotal) }}</td>
            </tr>
        </table>
    </div>

    @if($opportunity->description)
        <div style="margin-top: 30px; border-top: 1px solid #eee; padding-top: 10px;">
            <strong>Notes / Description:</strong><br>
            {{ $opportunity->description }}
        </div>
    @endif

</body>
</html>
