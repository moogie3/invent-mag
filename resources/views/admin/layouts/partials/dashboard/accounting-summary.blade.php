<div class="col-12">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="ti ti-calculator me-2"></i>{{ __('messages.accounting_summary') }}</h3>
        </div>
        <div class="card-body">
            <div class="row row-cards">
                @if(isset($financialItems))
                    @foreach($financialItems as $item)
                        <div class="col-md-4">
                            <div class="card card-sm">
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col-auto">
                                            <span class="bg-primary text-white avatar">
                                                <i class="ti {{ $item['icon'] }}"></i>
                                            </span>
                                        </div>
                                        <div class="col">
                                            <div class="font-weight-medium">
                                                {{ $item['label'] }}
                                            </div>
                                            <div class="text-muted">
                                                {{ \App\Helpers\CurrencyHelper::format($item['value']) }}
                                                @if($item['change'] != 0)
                                                    <span class="text-{{ $item['change'] > 0 ? 'success' : 'danger' }} ms-2">
                                                        {{ number_format($item['change'], 2) }}%
                                                        <i class="ti ti-trending-{{ $item['change'] > 0 ? 'up' : 'down' }}"></i>
                                                    </span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                @endif
            </div>
        </div>
    </div>
</div>
