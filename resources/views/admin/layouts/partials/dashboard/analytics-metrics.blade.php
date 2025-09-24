@php
    $metrics =
        $type === 'customer'
            ? [
                [
                    'label' => __('messages.customer_retention_rate'),
                    'value' => $analytics['retentionRate'] ?? 0,
                    'type' => 'percent',
                ],
                ['label' => __('messages.customer_crm_key_metrics_average_order_value'), 'value' => $analytics['avgOrderValue'] ?? 0, 'type' => 'currency'],
                [
                    'label' => __('messages.customer_lifetime_value'),
                    'value' => $analytics['customerLifetimeValue'] ?? 0,
                    'type' => 'currency',
                ],
            ]
            : [
                [
                    'label' => __('messages.payment_performance'),
                    'value' => $analytics['supplierPaymentPerformance'] ?? 0,
                    'type' => 'percent',
                ],
                [
                    'label' => __('messages.average_purchase_value'),
                    'value' => $analytics['avgPurchaseValue'] ?? 0,
                    'type' => 'currency',
                ],
                ['label' => __('messages.total_outstanding'), 'value' => $analytics['totalOutstanding'] ?? 0, 'type' => 'currency'],
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
