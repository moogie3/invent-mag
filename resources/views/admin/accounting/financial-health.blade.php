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
            'days' => $ind['value'] === null
                ? '—'
                : number_format($ind['value'], 0).' '.__('messages.days'),
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
    {{-- Period filter lives in the page-header's right column, not as a card
         under the pretitles — a filter belongs with the page controls, and
         keeping it beside the title stops it crowding "Accounting". Presets
         and custom month ranges both reduce to ONE [from..to] window inside
         FinancialHealthService::resolvePeriod(), which also rejects
         future/in-progress months and caps the span. A plain GET form: works
         without JavaScript, and the URL stays shareable; financial-health.js
         only syncs preset ↔ month inputs. --}}
    @php
        $fhPresetLabels = [
            'last_month' => __('messages.last_month'),
            'last_3' => __('messages.last_3_months'),
            'last_6' => __('messages.last_6_months'),
            'ytd' => __('messages.year_to_date'),
            'last_year' => __('messages.last_year'),
        ];
        // Nothing submitted = default window; a preset is named; anything
        // else with from/to set is a custom range.
        $fhSelectedPreset = $period['preset'] ?? ($period['default'] ? '' : 'custom');
        // The latest month a filter may point at — the in-progress month's
        // books are never closed, so the inputs cap at the one before it.
        $fhMaxMonth = Carbon::now()->startOfMonth()->subMonth()->format('Y-m');
        $fhRange = $period['from']->format('M Y').' — '.$period['to']->format('M Y');
    @endphp
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
            <div class="col-auto">
                <form method="get" action="{{ route('admin.accounting.health') }}"
                      class="d-flex align-items-center gap-2 flex-wrap justify-content-end fh-period-form"
                      id="fhPeriodForm">
                    <label class="text-muted small mb-0" for="fhPresetSelect">{{ __('messages.period') }}</label>
                    <select name="preset" id="fhPresetSelect" class="form-select form-select-sm w-auto">
                        <option value="">{{ __('messages.fh_period_default') }}</option>
                        @foreach($fhPresetLabels as $value => $label)
                            <option value="{{ $value }}" @selected($fhSelectedPreset === $value)>{{ $label }}</option>
                        @endforeach
                        <option value="custom" @selected($fhSelectedPreset === 'custom')>{{ __('messages.custom_range') }}</option>
                    </select>
                    <input type="month" name="from" id="fhFromInput"
                           class="form-control form-control-sm w-auto fh-custom-range @if($fhSelectedPreset !== 'custom') d-none @endif"
                           max="{{ $fhMaxMonth }}" value="{{ $period['from']->format('Y-m') }}"
                           aria-label="{{ __('messages.from') }}">
                    <input type="month" name="to" id="fhToInput"
                           class="form-control form-control-sm w-auto fh-custom-range @if($fhSelectedPreset !== 'custom') d-none @endif"
                           max="{{ $fhMaxMonth }}" value="{{ $period['to']->format('Y-m') }}"
                           aria-label="{{ __('messages.to') }}">
                    <button type="submit" class="btn btn-sm btn-primary">
                        <i class="ti ti-filter me-1"></i>{{ __('messages.apply') }}
                    </button>
                    @if(! $period['default'])
                        <a href="{{ route('admin.accounting.health') }}" class="btn btn-sm btn-secondary">
                            <i class="ti ti-restore me-1"></i>{{ __('messages.reset') }}
                        </a>
                    @endif
                </form>
                {{-- The window actually on screen — always visible so a
                     filtered page can never be mistaken for the default. --}}
                <div class="text-end mt-1">
                    <span class="badge bg-primary-lt text-primary fh-period-chip">
                        <i class="ti ti-calendar-month me-1"></i>{{ __('messages.period_showing', ['range' => $fhRange]) }}
                    </span>
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
        <div class="d-flex align-items-center justify-content-between mb-2">
            <span class="text-muted small text-uppercase fw-bold">{{ __('messages.condition_now') }}</span>
            {{-- Soft pill matching the page's bg-*-lt badge language; opened
                 by financial-health.js (inline handlers are blocked by CSP). --}}
            <button type="button" class="btn btn-sm rounded-pill bg-primary-lt text-primary fh-soft-action fh-compare-all" id="fhCompareAllBtn">
                <i class="ti ti-arrows-left-right me-1"></i>{{ __('messages.compare_all') }}
            </button>
        </div>
        @php
            // Point-in-time metrics (latest balance-sheet snapshots) never
            // shift with the period filter — flagged with a lock so the
            // mismatch is explained before the modal says it out loud.
            $fhLockedKeys = collect($comparison['rows'])->where('comparable', false)->pluck('key')->all();
        @endphp
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
                                <div class="d-flex align-items-center gap-1">
                                    <span class="badge bg-{{ $tone }}-lt text-{{ $tone }}">{{ __($statusLabel[$ind['status']] ?? 'messages.steady') }}</span>
                                    {{-- Opened by financial-health.js (CSP blocks inline handlers). --}}
                                    <button type="button"
                                            class="btn btn-sm btn-icon bg-tertiary-lt fh-compare-btn"
                                            data-fh-key="{{ $ind['key'] }}"
                                            title="{{ __('messages.compare') }}"
                                            aria-label="{{ __('messages.compare') }} — {{ $ind['label'] }}">
                                        <i class="ti ti-arrows-left-right"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="text-muted">
                                {{ $ind['label'] }}
                                @if(! $period['default'] && in_array($ind['key'], $fhLockedKeys, true))
                                    <i class="ti ti-lock fs-6 text-muted" data-bs-toggle="tooltip"
                                       title="{{ __('messages.period_point_in_time_hint') }}"></i>
                                @endif
                            </div>
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
                    <div class="card-header flex-wrap">
                        <h3 class="card-title"><i class="ti ti-crystal-ball me-2"></i>{{ __('messages.next_month_outlook') }}</h3>
                        {{-- Own row so the month + confidence badge never crowd
                             the title — the header wraps here instead of
                             squeezing the badge into the text at 1366px. --}}
                        <div class="d-flex align-items-center gap-2 mt-1 w-100">
                            <span class="badge bg-secondary-lt text-secondary">{{ $o['month'] }}</span>
                            <span class="badge bg-{{ $confTone }}-lt text-{{ $confTone }}">{{ __('messages.confidence') }}: {{ __($confLabel) }}</span>
                        </div>
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
                                        {{-- Soft pill matching the Compare-all
                                             button, tinted by the item's own
                                             severity; fh-soft-action adds the
                                             border + hover that make it read
                                             as a button, not a badge. --}}
                                        <a href="{{ $item['url'] }}" class="btn btn-sm rounded-pill bg-{{ $tone }}-lt text-{{ $tone }} fh-soft-action">{{ $item['link_label'] }}</a>
                                    </div>
                                </div>
                            @endforeach
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- 6) Plain-language explainer: every card, the outlook, the colors.
             The section itself stays open — the intro and the color legend
             are always useful. Each metric entry, however, is collapsed to
             its title until clicked: reference material a user reads once,
             so 17 paragraphs of prose never bury the legend. Toggles are
             wired in financial-health.js (CSP blocks inline handlers). --}}
        @php
            // Each entry is [label key, body key] rendered in the order the
            // cards appear, so the explainer reads top-to-bottom with the grid.
            $explainerCards = [
                ['understanding_cash', 'understanding_cash_body'],
                ['understanding_revenue', 'understanding_revenue_body'],
                ['understanding_purchases', 'understanding_purchases_body'],
                ['understanding_receivables', 'understanding_receivables_body'],
                ['understanding_payables', 'understanding_payables_body'],
                ['understanding_payroll', 'understanding_payroll_body'],
                ['understanding_turnover', 'understanding_turnover_body'],
                ['understanding_debt_to_equity', 'understanding_debt_to_equity_body'],
                ['understanding_gross_margin', 'understanding_gross_margin_body'],
                ['understanding_net_margin', 'understanding_net_margin_body'],
                ['understanding_liquidity', 'understanding_liquidity_body'],
                ['understanding_dso', 'understanding_dso_body'],
                ['understanding_stock_value', 'understanding_stock_value_body'],
            ];
            $explainerOutlook = [
                ['understanding_confidence', 'understanding_confidence_body'],
                ['understanding_range', 'understanding_range_body'],
                ['understanding_runway', 'understanding_runway_body'],
                ['understanding_collection', 'understanding_collection_body'],
            ];
            $explainerColors = [
                ['good', 'ti-circle-check text-success'],
                ['warn', 'ti-alert-circle text-warning'],
                ['danger', 'ti-alert-triangle text-danger'],
                ['neutral', 'ti-circle-minus text-secondary'],
            ];
        @endphp
        <div class="row mt-3">
            <div class="col-12">
                <div class="card border-0 shadow-sm rounded-3">
                    <div class="card-header">
                        <h3 class="card-title"><i class="ti ti-book-2 me-2"></i>{{ __('messages.understanding_metrics') }}</h3>
                    </div>
                    <div class="card-body">
                        <p class="text-muted mb-4">{{ __('messages.understanding_intro') }}</p>

                        <div class="row">
                            @foreach($explainerCards as [$label, $body])
                                <div class="col-lg-6 mb-1">
                                    {{-- One toggle per metric: title always
                                         visible, prose one click away.
                                         btn-ghost-secondary = no box at
                                         rest, soft tint on hover, so the
                                         list reads as plain text. --}}
                                    <button type="button"
                                            class="btn btn-sm btn-ghost-secondary d-flex align-items-center w-100 justify-content-start text-start px-1 fh-explainer-item"
                                            aria-expanded="false"
                                            aria-controls="fh-expl-{{ $label }}">
                                        <i class="ti ti-chevron-right text-muted me-2 fh-explainer-chevron"></i>
                                        <span class="fw-bold">{{ __('messages.'.$label) }}</span>
                                    </button>
                                    <div class="collapse ps-4" id="fh-expl-{{ $label }}">
                                        <div class="text-muted small pb-1">{{ __('messages.'.$body) }}</div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <hr class="my-4">

                        <div class="row">
                            @foreach($explainerOutlook as [$label, $body])
                                <div class="col-lg-6 mb-1">
                                    <button type="button"
                                            class="btn btn-sm btn-ghost-secondary d-flex align-items-center w-100 justify-content-start text-start px-1 fh-explainer-item"
                                            aria-expanded="false"
                                            aria-controls="fh-expl-{{ $label }}">
                                        <i class="ti ti-chevron-right text-muted me-2 fh-explainer-chevron"></i>
                                        <span class="fw-bold">{{ __('messages.'.$label) }}</span>
                                    </button>
                                    <div class="collapse ps-4" id="fh-expl-{{ $label }}">
                                        <div class="text-muted small pb-1">{{ __('messages.'.$body) }}</div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <hr class="my-4">

                        <div class="fw-bold mb-2">{{ __('messages.understanding_colors') }}</div>
                        <p class="text-muted small mb-2">{{ __('messages.understanding_colors_body') }}</p>
                        <div class="d-flex flex-wrap gap-3">
                            @foreach($explainerColors as [$status, $icon])
                                <span class="d-inline-flex align-items-center small text-muted">
                                    <i class="ti {{ $icon }} me-1"></i>{{ __($statusLabel[$status]) }}
                                </span>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Comparison modal — one shared dialog for the per-card drill-down and the
     "Compare all" table. Shell only; the body is filled by financial-health.js
     from the #fh-compare-data island (CSP-safe, no inline executable script). --}}
<div class="modal fade" id="fhCompareModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title d-flex align-items-center">
                    <i class="ti ti-arrows-left-right me-2"></i><span id="fhCompareTitle">{{ __('messages.compare_title') }}</span>
                </h3>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('messages.close') }}"></button>
            </div>
            <div class="modal-body" id="fhCompareBody">
                <div class="text-muted">{{ __('messages.loading') }}</div>
            </div>
            <div class="modal-footer">
                <span class="text-muted small me-auto" id="fhCompareMonths"></span>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('messages.close') }}</button>
            </div>
        </div>
    </div>
</div>

{{-- Comparison payload as a JSON DATA ISLAND (never executed → exempt from the
     CSP script-src nonce; see the chart island below for why). financial-health.js
     parses it on every turbo:load to render the modal. --}}
@php
    $fhComparePayload = [
        'rows' => collect($comparison['rows'])->map(fn ($r) => [
            'key' => $r['key'],
            'label' => $r['label'],
            'display' => $r['display'],
            'status' => $r['status'],
            'reason' => $r['reason'],
            'comparable' => $r['comparable'],
            'higher_is_better' => $r['higher_is_better'],
            'lock_reason' => $r['lock_reason'],
            'current' => $r['current'],
            'previous' => $r['previous'],
            'delta' => $r['delta'],
            'trend' => $r['trend'],
            'spark' => $r['spark'],
            'spark_labels' => $r['spark_labels'] ?? [],
        ])->values()->all(),
        'compared' => $comparison['compared'],
        'period' => [
            'default' => $period['default'],
            'window' => $period['window'],
        ],
        'i18n' => [
            'compare_title' => __('messages.compare_title'),
            'compare_desc' => __('messages.compare_desc'),
            'compared_months' => __('messages.compared_months'),
            'previous_month' => __('messages.previous_month'),
            'condition_now' => __('messages.condition_now'),
            'trend_up' => __('messages.trend_up'),
            'trend_down' => __('messages.trend_down'),
            'trend_flat' => __('messages.trend_flat'),
            'trend_chart' => __('messages.trend_chart'),
            'days' => __('messages.days'),
            'healthy' => __('messages.healthy'),
            'needs_attention' => __('messages.needs_attention'),
            'critical' => __('messages.critical'),
            'steady' => __('messages.steady'),
        ],
    ];
@endphp
<script type="application/json" id="fh-compare-data">@json($fhComparePayload)</script>

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
