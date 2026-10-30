{{--
    Accounting › Financial Health — the full money-health page.
    Data comes from App\Services\FinancialHealthService::getOverview():
    GL-true indicators, rotating plain-word narrative, the next-month
    projection chain (best/worst-of-last-6 range), 6-month trends and an
    attention list with drill-down links.
--}}
@extends('admin.layouts.base')

@section('title', __('messages.financial_health'))

@section('content')
@php
    use Illuminate\Support\Carbon;

    $statusTone = ['good' => 'success', 'warn' => 'warning', 'danger' => 'danger', 'neutral' => 'secondary'];
    $statusLabel = [
        'good' => 'messages.healthy',
        'warn' => 'messages.needs_attention',
        'danger' => 'messages.critical',
        'neutral' => 'messages.steady',
    ];
    $sevIcon = ['danger' => 'ti-alert-triangle text-danger', 'warn' => 'ti-alert-circle text-warning', 'good' => 'ti-circle-check text-success', 'neutral' => 'ti-circle-minus text-secondary'];
    $formatValue = function ($ind) {
        return match ($ind['display']) {
            'percent' => number_format(($ind['value'] ?? 0) * 100, 1).'%',
            'ratio' => number_format($ind['value'] ?? 0, 1).'×',
            default => \App\Helpers\CurrencyHelper::format($ind['value'] ?? 0),
        };
    };
    $fmt = fn ($v) => \App\Helpers\CurrencyHelper::format($v);
    $trendLabels = collect($trends['labels'])
        ->map(fn ($m) => Carbon::parse($m.'-01')->format('M y'))
        ->values()
        ->all();
    $o = $outlook;
    $confLabel = $o['revenue_confidence'] >= 75 ? 'messages.confidence_high'
        : ($o['revenue_confidence'] >= 55 ? 'messages.confidence_medium' : 'messages.confidence_low');
    $confTone = $o['revenue_confidence'] >= 75 ? 'success'
        : ($o['revenue_confidence'] >= 55 ? 'warning' : 'danger');
@endphp

<div class="{{ $containerClass ?? 'container-xl' }}">
    <div class="page-header d-print-none mt-4">
        <div class="row g-2 align-items-center">
            <div class="col">
                <div class="page-pretitle">
                    {{ __('messages.accounting') }}
                </div>
                <h2 class="page-title d-flex align-items-center">
                    <i class="ti ti-heartbeat me-2"></i>{{ __('messages.financial_health') }}
                    <a href="#" class="ms-3 text-muted trigger-tutorial-modal" data-bs-toggle="tooltip" title="{{ __('messages.tutorial') }}">
                        <i class="ti ti-help-circle fs-3"></i>
                    </a>
                </h2>
                <div class="text-muted mt-1">
                    {{ __('messages.financial_health_desc') }}
                </div>
            </div>
        </div>
    </div>

    <div class="page-body mt-4">
        {{-- Overall banner — the two-second verdict --}}
        @php $bannerTone = $statusTone[$overall] ?? 'secondary'; @endphp
        <div class="card border-0 shadow-sm rounded-3 mb-3 bg-{{ $bannerTone }}-lt">
            <div class="card-body d-flex align-items-center gap-3">
                <span class="avatar bg-{{ $bannerTone }} text-white"><i class="ti ti-heartbeat"></i></span>
                <div>
                    <div class="h3 mb-0">{{ __('messages.overall') }}: {{ __($statusLabel[$overall] ?? 'messages.steady') }}</div>
                    <div class="text-muted">
                        {{ count($attention) > 0
                            ? count($attention).' × '.__('messages.attention_list').' · '.__('messages.what_stands_out')
                            : __('messages.nothing_needs_attention') }}
                    </div>
                </div>
            </div>
        </div>

        {{-- 1) The condition right now — every indicator with its reason --}}
        <div class="row row-cards mb-3">
            @foreach($indicators as $ind)
                @php $tone = $statusTone[$ind['status']] ?? 'secondary'; @endphp
                <div class="col-6 col-md-4 col-xl-3">
                    <div class="card card-sm shadow-none rounded-3 h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <span class="avatar avatar-sm bg-{{ $tone }}-lt text-{{ $tone }}">
                                    <i class="ti {{ $ind['icon'] }}"></i>
                                </span>
                                <span class="badge bg-{{ $tone }}-lt text-{{ $tone }}">{{ __($statusLabel[$ind['status']] ?? 'messages.steady') }}</span>
                            </div>
                            <div class="text-muted">{{ $ind['label'] }}</div>
                            <div class="h3 mb-1 @if($ind['status'] === 'danger') text-danger @elseif($ind['status'] === 'good') text-success @endif">
                                {{ $formatValue($ind) }}
                                @if($ind['delta'] !== null && abs($ind['delta']) >= 0.01)
                                    <span class="badge bg-{{ $ind['delta'] > 0 ? 'success' : 'danger' }}-lt text-{{ $ind['delta'] > 0 ? 'success' : 'danger' }} ms-1">
                                        {{ $ind['delta'] > 0 ? '↑' : '↓' }}{{ number_format(abs($ind['delta']) * 100, 0) }}%
                                    </span>
                                @endif
                            </div>
                            <div class="text-muted small">{{ $ind['reason'] }}</div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="row mb-3">
            {{-- 2) What stands out — the smart plain-word layer --}}
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm rounded-3 h-100">
                    <div class="card-header">
                        <h3 class="card-title"><i class="ti ti-message-report me-2"></i>{{ __('messages.what_stands_out') }}</h3>
                    </div>
                    <div class="card-body">
                        @forelse($narrative as $line)
                            <div class="d-flex align-items-start gap-2 mb-2">
                                <i class="ti {{ $sevIcon[$line['severity']] ?? $sevIcon['neutral'] }} mt-1"></i>
                                <span>{{ $line['text'] }}</span>
                            </div>
                        @empty
                            <div class="text-muted">{{ __('messages.no_data') }}</div>
                        @endforelse
                    </div>
                </div>
            </div>

            {{-- 3) Next month, as a chain that ends in one number --}}
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm rounded-3 h-100">
                    <div class="card-header">
                        <h3 class="card-title"><i class="ti ti-crystal-ball me-2"></i>{{ __('messages.next_month_outlook') }} — {{ $o['month'] }}</h3>
                        <span class="badge bg-{{ $confTone }}-lt text-{{ $confTone }}">{{ __('messages.confidence') }}: {{ __($confLabel) }}</span>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">{{ __('messages.revenue_next') }}</span>
                            <span class="font-weight-medium">{{ $fmt($o['revenue']) }}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2 ps-3 small text-muted">
                            <span><i class="ti ti-corner-down-right me-1"></i>{{ __('messages.cash_in') }}
                                ({{ __('messages.collection_assumption', ['pct' => number_format($o['collection_ratio'] * 100, 0)]) }})</span>
                            <span>{{ $fmt($o['cash_in']) }}</span>
                        </div>
                        <hr class="my-2">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted">{{ __('messages.expenses_next') }}</span>
                            <span class="font-weight-medium">−{{ $fmt($o['expenses']) }}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2 ps-3 small text-muted">
                            <span><i class="ti ti-shopping-cart me-1"></i>{{ __('messages.purchases_next') }} ({{ __('messages.weighted_trend') }})</span>
                            <span>{{ $fmt($o['purchases']) }}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2 ps-3 small text-muted">
                            <span><i class="ti ti-users me-1"></i>{{ __('messages.payroll_next') }} ({{ __('messages.committed_payroll') }})</span>
                            <span>{{ $fmt($o['payroll']) }}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2 ps-3 small text-muted">
                            <span><i class="ti ti-tool me-1"></i>{{ __('messages.operations_next') }} ({{ __('messages.weighted_trend') }})</span>
                            <span>{{ $fmt($o['operations']) }}</span>
                        </div>
                        <hr class="my-2">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted">{{ __('messages.net_change') }}</span>
                            <span class="font-weight-medium {{ $o['net'] >= 0 ? 'text-success' : 'text-danger' }}">
                                {{ $o['net'] >= 0 ? '+' : '−' }}{{ $fmt(abs($o['net'])) }}
                            </span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-muted">{{ __('messages.projected_cash') }}</span>
                            <span class="h3 mb-0 {{ $o['tone'] === 'down' ? 'text-danger' : ($o['tone'] === 'up' ? 'text-success' : '') }}">
                                {{ $fmt($o['projected']) }}
                            </span>
                        </div>
                        @if($o['range_available'])
                            <div class="d-flex justify-content-between small text-muted mb-2">
                                <span>{{ __('messages.expected_range') }} ({{ __('messages.worst_case') }} – {{ __('messages.best_case') }})</span>
                                <span>{{ $fmt($o['range_low']) }} – {{ $fmt($o['range_high']) }}</span>
                            </div>
                        @endif
                        @if($o['runway'] !== null)
                            <div class="d-flex justify-content-between small mb-2">
                                <span class="text-muted">{{ __('messages.runway') }}</span>
                                <span class="text-danger font-weight-medium">{{ __('messages.runway_months', ['n' => number_format($o['runway'], 1)]) }}</span>
                            </div>
                        @endif
                        <div class="alert alert-{{ $o['tone'] === 'down' ? 'danger' : ($o['tone'] === 'up' ? 'success' : 'secondary') }} mb-0 mt-2">
                            <i class="ti ti-quote me-1"></i>{{ $o['statement'] }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- 4) Six-month trends --}}
        <div class="row mb-3">
            <div class="col-12">
                <div class="card border-0 shadow-sm rounded-3">
                    <div class="card-header">
                        <h3 class="card-title"><i class="ti ti-chart-line me-2"></i>{{ __('messages.trends_6m') }}</h3>
                        <div class="ms-3 text-muted small">{{ __('messages.included_vat_note') }} · {{ __('messages.current_month_partial') }}</div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-lg-4 mb-3 mb-lg-0">
                                <div class="text-muted small mb-1">{{ __('messages.ledger_money_trend') }}</div>
                                <div style="position: relative; height: 240px;"><canvas id="fh-chart-ledger"></canvas></div>
                            </div>
                            <div class="col-lg-4 mb-3 mb-lg-0">
                                <div class="text-muted small mb-1">{{ __('messages.sales_purchases_trend') }}</div>
                                <div style="position: relative; height: 240px;"><canvas id="fh-chart-sub"></canvas></div>
                            </div>
                            <div class="col-lg-4">
                                <div class="text-muted small mb-1">{{ __('messages.cash_balance_trend') }}</div>
                                <div style="position: relative; height: 240px;"><canvas id="fh-chart-cash"></canvas></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- 5) Attention list — with drill-downs --}}
        <div class="row">
            <div class="col-12">
                <div class="card border-0 shadow-sm rounded-3">
                    <div class="card-header">
                        <h3 class="card-title"><i class="ti ti-list-check me-2"></i>{{ __('messages.attention_list') }}</h3>
                    </div>
                    <div class="card-body">
                        @if(count($attention) === 0)
                            <div class="empty m-0">
                                <div class="empty-icon">
                                    <i class="ti ti-circle-check fs-1 text-success"></i>
                                </div>
                                <p class="empty-title">{{ __('messages.nothing_needs_attention') }}</p>
                            </div>
                        @else
                            @foreach($attention as $item)
                                @php $tone = $statusTone[$item['status']] ?? 'secondary'; @endphp
                                <div class="d-flex align-items-center justify-content-between py-2 @if(!$loop->last) border-bottom @endif">
                                    <div class="d-flex align-items-center gap-3">
                                        <span class="avatar avatar-sm bg-{{ $tone }}-lt text-{{ $tone }}">
                                            <i class="ti {{ $item['icon'] }}"></i>
                                        </span>
                                        <span>{{ $item['text'] }}</span>
                                    </div>
                                    <div class="d-flex align-items-center gap-3">
                                        @if($item['amount'] !== null)
                                            <span class="font-weight-medium">{{ $fmt($item['amount']) }}</span>
                                        @endif
                                        <a href="{{ $item['url'] }}" class="btn btn-sm btn-outline-primary">{{ $item['link_label'] }}</a>
                                    </div>
                                </div>
                            @endforeach
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{--
    Chart data as a JSON DATA ISLAND (type="application/json" is never
    executed, so it is exempt from the CSP script-src nonce — inline
    executable scripts are blocked on Turbo visits because the document
    keeps the first response's nonce). resources/js/admin/financial-health.js
    (loaded globally via script.blade.php) parses this and draws the charts
    on every turbo:load.

    The payload is precomputed above: the json directive must not contain
    nested parentheses (Blade matches it non-greedily and truncates at the
    first closing paren). NOTE: never write directive names inside blade
    comments — they compile and pair with the real end-directive.
--}}
@php
    $fhPayload = [
        'labels' => $trendLabels,
        'revenue_gl' => $trends['revenue_gl'],
        'expenses_gl' => $trends['expenses_gl'],
        'sales' => $trends['sales'],
        'purchases' => $trends['purchases'],
        'cash' => $trends['cash_end'],
        'i18n' => [
            'revenue' => __('messages.revenue'),
            'expenses' => __('messages.expenses'),
            'purchases' => __('messages.purchases'),
            'cash' => __('messages.cash'),
        ],
    ];
@endphp
<script type="application/json" id="fh-trend-data">@json($fhPayload)</script>
@endsection
