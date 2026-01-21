<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Purchase Return #{{ $por->id }}</title>
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
            <div class="invoice-title">PURCHASE RETURN</div>
            <div><strong>Ref (PO): #{{ $por->purchase->invoice }}</strong></div>
            <div>Date: {{ $por->return_date->format('d F Y') }}</div>
            <br>
            <div class="status-badge">{{ ucfirst($por->status) }}</div>
        </div>
    </div>

    <table class="info-table">
        <tr>
            <td style="width: 50%;">
                <strong>Vendor (Supplier):</strong><br>
                {{ $por->purchase->supplier->name ?? 'N/A' }}<br>
                {{ $por->purchase->supplier->address ?? '' }}
            </td>
            <td style="width: 50%;">
                <strong>Reason:</strong><br>
                {{ $por->reason ?? 'N/A' }}
            </td>
        </tr>
    </table>

    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 5%">#</th>
                <th style="width: 40%">Product</th>
                <th style="width: 15%" class="text-center">Returned Qty</th>
                <th style="width: 20%" class="text-right">Return Price</th>
                <th style="width: 20%" class="text-right">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($por->items as $index => $item)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>
                        {{ $item->product->name ?? 'N/A' }}
                    </td>
                    <td class="text-center">{{ $item->quantity }}</td>
                    <td class="text-right">{{ \App\Helpers\CurrencyHelper::formatWithPosition($item->price) }}</td>
                    <td class="text-right">{{ \App\Helpers\CurrencyHelper::formatWithPosition($item->total) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="clearfix">
        <table class="totals-table">
            <tr class="total-row">
                <td>Total Refund:</td>
                <td>{{ \App\Helpers\CurrencyHelper::formatWithPosition($por->total_amount) }}</td>
            </tr>
        </table>
    </div>
    
    <div style="margin-top: 30px; font-size: 11px; color: #666;">
        Processed by: {{ $por->user->name ?? 'System' }}
    </div>

</body>
</html>
