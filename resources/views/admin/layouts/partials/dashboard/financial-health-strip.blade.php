{{--
    Financial Health strip — the dashboard's money-at-a-glance row.
    Replaces the old subledger-based "Accounting Summary" mini-cards with
    GL-true indicators (red/amber/green + one-line reason), the sharpest
    narrative lines, and a CTA into the full Accounting > Financial Health
    page (every cell links there). Gated upstream by the accounting plan
    feature + view-accounting permission.
--}}
@php
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
@endphp

<div class="col-12">
    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-header">
            <h3 class="card-title"><i class="ti ti-heartbeat me-2"></i>{{ __('messages.financial_health') }}</h3>
            <div class="card-actions">
                <a href="{{ $healthUrl }}" class="btn btn-sm btn-primary">
                    {{ __('messages.open_financial_health') }} <i class="ti ti-arrow-right ms-1"></i>
                </a>
            </div>
        </div>
        <div class="card-body">
            <div class="row row-cards">
                @foreach($healthSummary['indicators'] as $ind)
                    @php $tone = $statusTone[$ind['status']] ?? 'secondary'; @endphp
                    <div class="col-6 col-md-4 col-xl-3">
                        <a href="{{ $healthUrl }}" class="card card-sm shadow-none rounded-3 text-decoration-none d-block h-100">
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
                                </div>
                                <div class="text-muted small text-truncate" title="{{ $ind['reason'] }}">{{ $ind['reason'] }}</div>
                            </div>
                        </a>
                    </div>
                @endforeach
            </div>

            @if(count($healthSummary['narrative'] ?? []) > 0)
                <div class="mt-3 pt-3 border-top">
                    @foreach($healthSummary['narrative'] as $line)
                        <div class="d-flex align-items-start gap-2 mb-1">
                            <i class="ti {{ $sevIcon[$line['severity']] ?? $sevIcon['neutral'] }} mt-1"></i>
                            <span class="small">{{ $line['text'] }}</span>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>
