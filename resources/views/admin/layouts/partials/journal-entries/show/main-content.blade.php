<div class="card">
    <div class="card-header">
        <h3 class="card-title">{{ $entry->description }}</h3>
        <div class="card-actions">
            @if($entry->status === 'draft')
                <span class="badge bg-warning text-dark">{{ __('messages.draft') }}</span>
            @elseif($entry->status === 'posted')
                <span class="badge bg-success">{{ __('messages.posted') }}</span>
            @else
                <span class="badge bg-danger">{{ __('messages.void') }}</span>
            @endif
        </div>
    </div>
    <div class="card-body">
        <div class="row g-3 mb-3">
            <div class="col-md-4">
                <label class="form-label text-muted">{{ __('messages.date') }}</label>
                <div>{{ $entry->date->format('d/m/Y') }}</div>
            </div>
            <div class="col-md-4">
                <label class="form-label text-muted">{{ __('messages.entry_type') }}</label>
                <div>{{ ucfirst($entry->entry_type) }}</div>
            </div>
            <div class="col-md-4">
                <label class="form-label text-muted">{{ __('messages.reference') }}</label>
                <div>#{{ $entry->id }}</div>
            </div>
        </div>
        
        @if($entry->notes)
            <div class="alert alert-info mb-3">
                <strong>{{ __('messages.notes') }}:</strong><br>{{ $entry->notes }}
            </div>
        @endif
        
        <h4 class="mb-3">{{ __('messages.transactions') }}</h4>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>{{ __('messages.account') }}</th>
                    <th class="text-end">{{ __('messages.debit') }}</th>
                    <th class="text-end">{{ __('messages.credit') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($entry->transactions as $transaction)
                    <tr>
                        <td>
                            <strong>{{ $transaction->account->code }}</strong> - {{ __($transaction->account->name) }}
                        </td>
                        <td class="text-end">
                            @if($transaction->type === 'debit')
                                {{ \App\Helpers\CurrencyHelper::format($transaction->amount) }}
                            @endif
                        </td>
                        <td class="text-end">
                            @if($transaction->type === 'credit')
                                {{ \App\Helpers\CurrencyHelper::format($transaction->amount) }}
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="table-active">
                    <td><strong>{{ __('messages.total') }}</strong></td>
                    <td class="text-end"><strong>{{ \App\Helpers\CurrencyHelper::format($entry->total_debit) }}</strong></td>
                    <td class="text-end"><strong>{{ \App\Helpers\CurrencyHelper::format($entry->total_credit) }}</strong></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

@if($entry->approvedBy)
    <div class="card mt-3">
        <div class="card-header">
            <h4 class="card-title">{{ __('messages.approval_information') }}</h4>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <label class="form-label text-muted">{{ __('messages.approved_by') }}</label>
                    <div>{{ $entry->approvedBy->name }}</div>
                </div>
                <div class="col-md-6">
                    <label class="form-label text-muted">{{ __('messages.approved_at') }}</label>
                    <div>{{ $entry->approved_at->format('d/m/Y H:i') }}</div>
                </div>
            </div>
        </div>
    </div>
@endif
