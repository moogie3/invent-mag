@extends('admin.layouts.base')

@section('title', __('messages.journal_entry_details'))

@section('content')
<div class="container-xl">
    <div class="page-header d-print-none mt-4">
        <div class="row g-2 align-items-center">
            <div class="col">
                <div class="page-pretitle">{{ __('messages.accounting') }}</div>
                <h2 class="page-title">
                    <span class="nav-link-icon d-md-none d-lg-inline-block">
                        <i class="ti ti-journal"></i>
                    </span>
                    {{ __('messages.journal_entry_details') }}
                </h2>
            </div>
            <div class="col-auto ms-auto d-print-none">
                <div class="btn-list">
                    @if($entry->status === 'draft')
                        <a href="{{ route('admin.accounting.journal-entries.edit', $entry) }}" class="btn btn-primary">
                            <i class="ti ti-edit"></i>
                            {{ __('messages.edit') }}
                        </a>
                        <form action="{{ route('admin.accounting.journal-entries.post', $entry) }}" method="POST" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-success">
                                <i class="ti ti-check"></i>
                                {{ __('messages.post_entry') }}
                            </button>
                        </form>
                    @endif
                    @if($entry->status === 'posted')
                        <button type="button" class="btn btn-warning" onclick="reverseEntry('{{ route('admin.accounting.journal-entries.reverse', $entry) }}')">
                            <i class="ti ti-arrow-back-up"></i>
                            {{ __('messages.reverse') }}
                        </button>
                        <button type="button" class="btn btn-danger" onclick="voidEntry('{{ route('admin.accounting.journal-entries.void', $entry) }}')">
                            <i class="ti ti-x"></i>
                            {{ __('messages.void') }}
                        </button>
                    @endif
                    <a href="{{ route('admin.accounting.journal-entries.index') }}" class="btn btn-secondary">
                        <i class="ti ti-arrow-left"></i>
                        {{ __('messages.back') }}
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="page-body mt-4">
        <div class="row g-3">
            <div class="col-md-8">
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
                                <strong>{{ __('messages.notes') }}:</strong><br>
                                {{ $entry->notes }}
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
            </div>

            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">{{ __('messages.audit_log') }}</h4>
                    </div>
                    <div class="card-body">
                        @if($auditLogs->isEmpty())
                            <p class="text-muted">{{ __('messages.no_audit_logs') }}</p>
                        @else
                            <div class="timeline">
                                @foreach($auditLogs as $log)
                                    <div class="timeline-item">
                                        <div class="timeline-badge bg-{{ $log->action === 'create' ? 'success' : ($log->action === 'delete' ? 'danger' : 'primary') }}"></div>
                                        <div class="timeline-content">
                                            <div class="text-muted small">{{ $log->created_at->format('d/m/Y H:i') }}</div>
                                            <div>{{ $log->description }}</div>
                                            <div class="text-muted small">by {{ $log->user_name }}</div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>

                @if($entry->reversedEntry || $entry->reversingEntry)
                    <div class="card mt-3">
                        <div class="card-header">
                            <h4 class="card-title">{{ __('messages.related_entries') }}</h4>
                        </div>
                        <div class="card-body">
                            @if($entry->reversedEntry)
                                <div class="mb-2">
                                    <strong>{{ __('messages.reversed_entry') }}:</strong>
                                    <a href="{{ route('admin.accounting.journal-entries.show', $entry->reversedEntry) }}">
                                        #{{ $entry->reversedEntry->id }} - {{ $entry->reversedEntry->description }}
                                    </a>
                                </div>
                            @endif
                            @if($entry->reversingEntry)
                                <div>
                                    <strong>{{ __('messages.reversing_entry') }}:</strong>
                                    <a href="{{ route('admin.accounting.journal-entries.show', $entry->reversingEntry) }}">
                                        #{{ $entry->reversingEntry->id }} - {{ $entry->reversingEntry->description }}
                                    </a>
                                </div>
                            @endif
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
