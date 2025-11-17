<div class="col-12">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">{{ __('messages.accounting_summary') }}</h3>
        </div>
        <div class="card-body">
            <div class="row">
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
