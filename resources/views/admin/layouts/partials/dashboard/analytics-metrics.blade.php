@php
    $metrics =
        $type === 'customer'
            ? [
                [
                    'label' => 'Customer Retention Rate',
                    'value' => $analytics['retentionRate'] ?? 0,
                    'type' => 'percent',
                ],
                ['label' => 'Average Order Value', 'value' => $analytics['avgOrderValue'] ?? 0, 'type' => 'currency'],
                [
                    'label' => 'Customer Lifetime Value',
                    'value' => $analytics['customerLifetimeValue'] ?? 0,
                    'type' => 'currency',
                ],
            ]
            : [
                [
                    'label' => 'Payment Performance',
                    'value' => $analytics['supplierPaymentPerformance'] ?? 0,
                    'type' => 'percent',
                ],
                [
                    'label' => 'Average Purchase Value',
                    'value' => $analytics['avgPurchaseValue'] ?? 0,
                    'type' => 'currency',
                ],
                ['label' => 'Total Outstanding', 'value' => $analytics['totalOutstanding'] ?? 0, 'type' => 'currency'],
            ];
@endphp
@foreach ($metrics as $metric)
    <div class="mb-3">
        <div class="d-flex justify-content-between mb-1">
            <span class="text-muted">{{ $metric['label'] }}</span>
            <span class="fw-medium">
                @if ($metric['type'] === 'currency')
                    {{ \App\Helpers\CurrencyHelper::format($metric['value']) }}
                @elseif($metric['type'] === 'percent')
                    {{ $metric['value'] }}%
                @else
                    {{ $metric['value'] }}
                @endif
            </span>
        </div>
        @if ($metric['type'] === 'percent')
            <div class="progress progress-sm">
                <div class="progress-bar bg-{{ $type === 'customer' ? 'azure' : 'purple' }}"
                    style="width: {{ $metric['value'] }}%"></div>
            </div>
        @endif
    </div>
@endforeach
