<div class="col-md-12">
    <div class="card shadow-sm border-1 mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="card-title">
                <i class="ti ti-chart-bar fs-3 me-2"></i> {{ __('messages.revenue_vs_expenses') }}
            </h3>
        </div>
        <div class="table-responsive">
            <table class="table table-vcenter table-hover mb-0">
                <thead>
                    <tr>
                        <th>{{ __('messages.month') }}</th>
                        <th class="text-end">{{ __('messages.revenue') }}</th>
                        <th class="text-end">{{ __('messages.expenses') }}</th>
                        <th class="text-end">{{ __('messages.profit') }}</th>
                        <th class="text-center">{{ __('messages.margin') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($monthlyData ?? [] as $data)
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $data['month'] }}</div>
                            </td>
                            <td class="text-end fw-medium text-success">
                                {{ \App\Helpers\CurrencyHelper::format($data['revenue']) }}
                            </td>
                            <td class="text-end fw-medium text-danger">
                                {{ \App\Helpers\CurrencyHelper::format($data['expenses']) }}
                            </td>
                            <td class="text-end fw-medium">
                                {{ \App\Helpers\CurrencyHelper::format($data['profit']) }}
                            </td>
                            <td class="text-center">
                                <span
                                    class="badge {{ $data['margin'] > 20 ? 'bg-success' : ($data['margin'] > 10 ? 'bg-warning' : 'bg-danger') }}-lt">
                                    {{ number_format($data['margin'], 1) }}%
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-3 text-muted">{{ __('messages.no_data_available_for_period') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
