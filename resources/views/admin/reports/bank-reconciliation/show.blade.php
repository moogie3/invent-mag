@extends('admin.layouts.base')

@section('title', __('messages.bank_reconciliation') . ': ' . ($reconciliation->account?->name ?? ''))

@section('content')
    <div class="page-wrapper">
        <div class="page-header d-print-none">
            <div class="{{ ($containerClass ?? 'container-xl') === 'container-xl' ? 'container-fluid' : $containerClass }}"
                 style="{{ ($containerClass ?? 'container-xl') === 'container-xl' ? 'max-width: 90%;' : '' }}">
                <div class="row align-items-center">
                    <div class="col">
                        <div class="page-pretitle">
                            <a href="{{ route('admin.bank-reconciliation.index') }}" class="text-muted text-decoration-none">
                                <i class="ti ti-arrow-left"></i> {{ __('messages.back') }}
                            </a>
                        </div>
                        <h2 class="page-title d-flex align-items-center">
                            <i class="ti ti-building-bank me-2"></i> {{ $reconciliation->account?->name }}
                            @if($reconciliation->isCompleted())
                                <span class="badge bg-success text-white ms-3">{{ __('messages.bank_reconciliation_completed') }}</span>
                            @else
                                <span class="badge bg-warning text-white ms-3">{{ __('messages.bank_reconciliation_open') }}</span>
                            @endif
                            <a href="#" class="ms-3 text-muted trigger-tutorial-modal" data-bs-toggle="tooltip" title="{{ __('messages.tutorial') }}">
                                <i class="ti ti-help-circle fs-3"></i>
                            </a>
                        </h2>
                        <div class="text-muted mt-1">
                            {{ $reconciliation->period_start->format('d M Y') }} – {{ $reconciliation->period_end->format('d M Y') }}
                        </div>
                    </div>
                    <div class="col-auto ms-auto d-print-none">
                        <div class="btn-list">
                            @if(! $reconciliation->isCompleted())
                                @if(abs($reconciliation->variance) > 0.005)
                                    <button type="button" class="btn btn-success" disabled title="{{ __('messages.bank_reconciliation_variance_pending') }}">
                                        <i class="ti ti-check me-2"></i> {{ __('messages.bank_reconciliation_complete') }}
                                    </button>
                                @else
                                    <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#completeReconciliationModal">
                                        <i class="ti ti-check me-2"></i> {{ __('messages.bank_reconciliation_complete') }}
                                    </button>
                                @endif
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="page-body">
            <div class="{{ ($containerClass ?? 'container-xl') === 'container-xl' ? 'container-fluid' : $containerClass }}"
                 style="{{ ($containerClass ?? 'container-xl') === 'container-xl' ? 'max-width: 90%;' : '' }}">
                <div class="row row-cards">
                    <div class="col-sm-6 col-lg-3">
                        <div class="card card-sm border-0 shadow-sm rounded-3">
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col-auto">
                                        <span class="bg-blue-lt text-blue avatar"><i class="ti ti-arrow-up"></i></span>
                                    </div>
                                    <div class="col">
                                        <div class="font-weight-medium">{{ __('messages.bank_reconciliation_opening_balance') }}</div>
                                        <div class="fw-bold">{{ \App\Helpers\CurrencyHelper::format($reconciliation->opening_balance) }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-lg-3">
                        <div class="card card-sm border-0 shadow-sm rounded-3">
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col-auto">
                                        <span class="bg-purple-lt text-purple avatar"><i class="ti ti-arrow-down"></i></span>
                                    </div>
                                    <div class="col">
                                        <div class="font-weight-medium">{{ __('messages.bank_reconciliation_closing_balance') }}</div>
                                        <div class="fw-bold">{{ \App\Helpers\CurrencyHelper::format($reconciliation->closing_balance) }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-lg-3">
                        <div class="card card-sm border-0 shadow-sm rounded-3">
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col-auto">
                                        <span class="bg-cyan-lt text-cyan avatar"><i class="ti ti-calculator"></i></span>
                                    </div>
                                    <div class="col">
                                        <div class="font-weight-medium">{{ __('messages.bank_reconciliation_computed_closing') }}</div>
                                        <div class="fw-bold">{{ \App\Helpers\CurrencyHelper::format($reconciliation->computed_closing_balance) }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-lg-3">
                        <div class="card card-sm border-0 shadow-sm rounded-3">
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col-auto">
                                        <span class="bg-{{ $reconciliation->variance == 0 ? 'success' : 'danger' }}-lt text-{{ $reconciliation->variance == 0 ? 'success' : 'danger' }} avatar"><i class="ti ti-scale"></i></span>
                                    </div>
                                    <div class="col">
                                        <div class="font-weight-medium">{{ __('messages.bank_reconciliation_variance') }}</div>
                                        <div class="fw-bold text-{{ $reconciliation->variance == 0 ? 'success' : 'danger' }}">
                                            {{ $reconciliation->variance == 0 ? '0' : \App\Helpers\CurrencyHelper::format($reconciliation->variance) }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row row-cards mt-1">
                    <div class="col-sm-6 col-lg-3">
                        <div class="card card-sm border-0 shadow-sm rounded-3">
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col-auto">
                                        <span class="bg-green-lt text-green avatar"><i class="ti ti-link"></i></span>
                                    </div>
                                    <div class="col">
                                        <div class="font-weight-medium">{{ __('messages.bank_reconciliation_matched_amount') }}</div>
                                        <div class="fw-bold text-success">{{ \App\Helpers\CurrencyHelper::format($reconciliation->matched_amount) }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-lg-3">
                        <div class="card card-sm border-0 shadow-sm rounded-3">
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col-auto">
                                        <span class="bg-orange-lt text-orange avatar"><i class="ti ti-unlink"></i></span>
                                    </div>
                                    <div class="col">
                                        <div class="font-weight-medium">{{ __('messages.bank_reconciliation_unmatched_amount') }}</div>
                                        <div class="fw-bold text-warning">{{ \App\Helpers\CurrencyHelper::format($reconciliation->unmatched_amount) }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-lg-3">
                        <div class="card card-sm border-0 shadow-sm rounded-3">
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col-auto">
                                        <span class="bg-teal-lt text-teal avatar"><i class="ti ti-list"></i></span>
                                    </div>
                                    <div class="col">
                                        <div class="font-weight-medium">{{ __('messages.bank_reconciliation_lines') }}</div>
                                        <div class="fw-bold">{{ $reconciliation->statement_lines_count }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm rounded-3 mt-3">
                    <div class="card-header">
                        <h3 class="card-title">{{ __('messages.bank_reconciliation_statement_lines') }}</h3>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-vcenter card-table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>{{ __('messages.date') }}</th>
                                    <th>{{ __('messages.description') }}</th>
                                    <th>{{ __('messages.reference') }}</th>
                                    <th class="text-center">{{ __('messages.type') }}</th>
                                    <th class="text-end">{{ __('messages.amount') }}</th>
                                    <th>{{ __('messages.bank_reconciliation_status') }}</th>
                                    <th>{{ __('messages.bank_reconciliation_journal_entry') }}</th>
                                    <th class="w-1">{{ __('messages.action') ?? 'Action' }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($reconciliation->items as $item)
                                    @php
                                        $itemCandidates = $candidates->filter(function ($entry) use ($item) {
                                            return $entry->transactions->contains(
                                                fn ($tx) => abs(round((float) $tx->amount, 2) - round((float) $item->amount, 2)) < 0.001
                                            );
                                        });
                                    @endphp
                                    <tr>
                                        <td>{{ $item->line_date->format('d M Y') }}</td>
                                        <td>{{ $item->description }}</td>
                                        <td>{{ $item->reference ?? '-' }}</td>
                                        <td class="text-center">
                                            @if($item->type === 'debit')
                                                <span class="badge bg-blue-lt text-blue">{{ __('messages.debit') }}</span>
                                            @else
                                                <span class="badge bg-danger-lt text-danger">{{ __('messages.credit') }}</span>
                                            @endif
                                        </td>
                                        <td class="text-end">{{ \App\Helpers\CurrencyHelper::format($item->amount) }}</td>
                                        <td>
                                            @if($item->isMatched())
                                                <span class="badge bg-success text-white">{{ __('messages.bank_reconciliation_matched') }}</span>
                                            @else
                                                <span class="badge bg-warning text-white">{{ __('messages.bank_reconciliation_unmatched') }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($item->journalEntry)
                                                <a href="{{ route('admin.accounting.journal-entries.show', $item->journalEntry->id) }}" class="text-decoration-none">
                                                    JE #{{ $item->journalEntry->id }}
                                                </a>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if(! $reconciliation->isCompleted())
                                                @if($item->isMatched())
                                                    <form action="{{ route('admin.bank-reconciliation.unmatch', [$reconciliation->id, $item->id]) }}" method="POST">
                                                        @csrf
                                                        <button type="submit" class="btn btn-icon btn-ghost-warning" title="{{ __('messages.bank_reconciliation_unmatch') }}">
                                                            <i class="ti ti-unlink"></i>
                                                        </button>
                                                    </form>
                                                @elseif($itemCandidates->isNotEmpty())
                                                    <form action="{{ route('admin.bank-reconciliation.match', [$reconciliation->id, $item->id]) }}" method="POST" class="d-flex gap-1">
                                                        @csrf
                                                        <select name="journal_entry_id" class="form-select form-select-sm" required>
                                                            <option value="">JE #…</option>
                                                            @foreach ($itemCandidates as $entry)
                                                                <option value="{{ $entry->id }}">
                                                                    #{{ $entry->id }} · {{ $entry->date->format('d M Y') }} · {{ $entry->description }}
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                        <button type="submit" class="btn btn-sm btn-primary" title="{{ __('messages.bank_reconciliation_match') }}">
                                                            <i class="ti ti-link"></i>
                                                        </button>
                                                    </form>
                                                @else
                                                    <span class="text-muted small">{{ __('messages.bank_reconciliation_no_candidate') }}</span>
                                                @endif
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal modal-blur fade" id="completeReconciliationModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
            <div class="modal-content">
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                <div class="modal-status bg-success"></div>
                <div class="modal-body text-center py-4">
                    <i class="ti ti-check icon mb-2 text-success" style="font-size: 3rem;"></i>
                    <h3>{{ __('messages.bank_reconciliation_complete_confirm') }}</h3>
                    <div class="text-muted">{{ __('messages.bank_reconciliation_complete_desc') }}</div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-link link-secondary me-auto" data-bs-dismiss="modal">{{ __('messages.cancel') }}</button>
                    <form action="{{ route('admin.bank-reconciliation.complete', $reconciliation->id) }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-success">{{ __('messages.bank_reconciliation_complete') }}</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
