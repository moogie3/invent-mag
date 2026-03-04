<div class="col-12">
    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-header">
            <h3 class="card-title"><i class="ti ti-chart-line me-2"></i>{{ __('messages.sales_forecast') }}</h3>
        </div>
        <div class="card-body">
            @if(isset($salesForecast) && is_array($salesForecast) && count($salesForecast['labels'] ?? []) > 0)
                <canvas id="sales-forecast-chart"></canvas>
            @else
                <div class="empty">
                    <div class="empty-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24"
                            viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"
                            stroke-linecap="round" stroke-linejoin="round">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                            <path d="M8 9l4 -4l4 4" />
                            <path d="M16 15l-4 4l-4 -4" />
                            <path d="M3 9h18" />
                            <path d="M3 15h18" />
                        </svg>
                    </div>
                    <p class="empty-title">{{ __('messages.no_forecast_data') }}</p>
                    <p class="empty-subtitle text-muted">
                        {{ __('messages.forecast_description') }}
                    </p>
                </div>
            @endif
        </div>
    </div>
</div>
