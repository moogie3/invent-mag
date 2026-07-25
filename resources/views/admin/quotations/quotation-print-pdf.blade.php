<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('messages.quotation') }} #{{ $quotation->quotation_number }}</title>
    <style>
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 12px; color: #333; }
        .header { text-align: center; margin-bottom: 30px; }
        .header h1 { font-size: 24px; margin: 0; color: #1a1a1a; }
        .header p { margin: 5px 0; color: #666; }
        .info-section { margin-bottom: 30px; }
        .info-section table { width: 100%; }
        .info-section td { vertical-align: top; padding: 5px 0; }
        .items-table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
        .items-table th { background: #f5f5f5; padding: 10px 8px; text-align: center; border-bottom: 2px solid #ddd; font-size: 11px; text-transform: uppercase; }
        .items-table td { padding: 8px; text-align: center; border-bottom: 1px solid #eee; }
        .items-table .text-left { text-align: left; }
        .summary { width: 300px; margin-left: auto; }
        .summary table { width: 100%; }
        .summary td { padding: 4px 8px; }
        .summary .total-row td { font-weight: bold; font-size: 14px; border-top: 2px solid #333; padding-top: 8px; }
        .footer { text-align: center; margin-top: 50px; color: #999; font-size: 10px; }
        .status-badge { display: inline-block; padding: 4px 12px; border-radius: 4px; font-size: 11px; font-weight: bold; text-transform: uppercase; }
        .terms { margin-top: 30px; padding-top: 15px; border-top: 1px solid #ddd; }
        .terms h6 { margin-bottom: 5px; }
    </style>
</head>
<body>
    <div class="header">
        @if($shopname)
            <h1>{{ $shopname }}</h1>
        @endif
        @if($address)
            <p>{{ $address }}</p>
        @endif
        <h2 style="margin-top: 20px;">{{ __('messages.quotation') }}</h2>
        <p>{{ __('messages.quotation_number') }}: {{ $quotation->quotation_number }}</p>
    </div>

    <div class="info-section">
        <table>
            <tr>
                <td style="width: 50%;">
                    <strong>{{ __('messages.quotation_for') }}:</strong><br>
                    {{ $quotation->customer->name ?? __('messages.not_available') }}<br>
                    @if($quotation->customer?->address)
                        {{ $quotation->customer->address }}<br>
                    @endif
                    @if($quotation->customer?->phone)
                        {{ $quotation->customer->phone }}<br>
                    @endif
                    @if($quotation->customer?->email)
                        {{ $quotation->customer->email }}
                    @endif
                </td>
                <td style="width: 50%; text-align: right;">
                    <strong>{{ __('messages.quotation_date') }}:</strong> {{ $quotation->quotation_date?->format('d M Y') }}<br>
                    <strong>{{ __('messages.quotation_valid_until') }}:</strong> {{ $quotation->valid_until?->format('d M Y') ?? '-' }}<br>
                    <strong>{{ __('messages.quotation_status') }}:</strong>
                    <span class="status-badge">{{ __("messages.quotation_status_{$quotation->status}") }}</span>
                </td>
            </tr>
        </table>
    </div>

    <table class="items-table">
        <thead>
            <tr>
                <th>{{ __('messages.table_no') }}</th>
                <th class="text-left">{{ __('messages.table_product') }}</th>
                <th>{{ __('messages.quantity') }}</th>
                <th>{{ __('messages.table_price') }}</th>
                <th>{{ __('messages.table_discount') }}</th>
                <th>{{ __('messages.table_amount') }}</th>
            </tr>
        </thead>
        <tbody>
            @php
                $items = collect($quotation->items)->map(fn($item) => is_array($item) ? (object) $item : $item);
            @endphp
            @foreach($items as $index => $item)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td class="text-left">{{ $item->product_name ?? $item->product?->name ?? __('messages.not_available') }}</td>
                <td>{{ $item->quantity }}</td>
                <td>{{ \App\Helpers\CurrencyHelper::format($item->price) }}</td>
                <td>
                    @if($item->discount > 0)
                        {{ $item->discount_type === 'percentage' ? $item->discount . '%' : \App\Helpers\CurrencyHelper::format($item->discount) }}
                    @else
                        -
                    @endif
                </td>
                <td>{{ \App\Helpers\CurrencyHelper::format($item->total) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="summary">
        <table>
            <tr>
                <td>{{ __('messages.quotation_subtotal') }}</td>
                <td style="text-align: right;">{{ \App\Helpers\CurrencyHelper::format($quotation->subtotal) }}</td>
            </tr>
            @if($quotation->discount_amount > 0)
            <tr>
                <td>{{ __('messages.quotation_discount') }}
                    @if($quotation->discount_type === 'percentage')
                        ({{ $quotation->discount_value }}%)
                    @endif
                </td>
                <td style="text-align: right;">-{{ \App\Helpers\CurrencyHelper::format($quotation->discount_amount) }}</td>
            </tr>
            @endif
            @if($quotation->tax_amount > 0)
            <tr>
                <td>{{ __('messages.quotation_tax') }}
                    @if($quotation->tax_type !== 'none')
                        ({{ $quotation->tax_rate }}%, {{ $quotation->tax_type }})
                    @endif
                </td>
                <td style="text-align: right;">{{ \App\Helpers\CurrencyHelper::format($quotation->tax_amount) }}</td>
            </tr>
            @endif
            <tr class="total-row">
                <td>{{ __('messages.quotation_total') }}</td>
                <td style="text-align: right;">{{ \App\Helpers\CurrencyHelper::format($quotation->total) }}</td>
            </tr>
        </table>
    </div>

    @if($quotation->notes)
    <div class="terms">
        <h6>{{ __('messages.quotation_notes') }}</h6>
        <p>{{ $quotation->notes }}</p>
    </div>
    @endif

    @if($quotation->terms_conditions)
    <div class="terms">
        <h6>{{ __('messages.quotation_terms_conditions') }}</h6>
        <p>{{ $quotation->terms_conditions }}</p>
    </div>
    @endif

    <div class="footer">
        <p>{{ __('messages.quotation') }} - {{ $quotation->quotation_number }} | {{ date('d M Y') }}</p>
    </div>
</body>
</html>
