@extends('admin.layouts.base')

@section('title', __('messages.general_journal'))

@section('content')
    <div class="container-xl">
        <div class="page-header d-print-none mt-4">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <div class="page-pretitle">
                        {{ __('messages.accounting') }}
                    </div>
                    <h2 class="page-title">
                        <span class="nav-link-icon d-md-none d-lg-inline-block">
                            <i class="ti ti-notebook"></i>
                        </span>
                        {{ __('messages.general_journal') }}
                    </h2>
                    <div class="text-muted mt-1">
                        {{ __('messages.general_journal_summary') }}
                    </div>
                </div>
            </div>
        </div>

        <div class="page-body mt-4">
            <div class="card">
                <div class="card-body">
                    @if ($entries->isEmpty())
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
                            <p class="empty-title">{{ __('messages.no_journal_entries_found') }}</p>
                            <p class="empty-subtitle text-muted">
                                {{ __('messages.journal_entries_description') }}
                            </p>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-vcenter card-table">
                                <thead>
                                    <tr>
                                        <th>{{ __('messages.date') }}</th>
                                        <th>{{ __('messages.description') }}</th>
                                        <th>{{ __('messages.account') }}</th>
                                        <th class="text-end">{{ __('messages.debit') }}</th>
                                        <th class="text-end">{{ __('messages.credit') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($entries as $entry)
                                        @foreach ($entry->transactions as $index => $transaction)
                                            <tr>
                                                @if ($index === 0)
                                                    <td rowspan="{{ $entry->transactions->count() }}" class="align-top">
                                                        {{ $entry->date->format('d M Y') }}</td>
                                                    <td rowspan="{{ $entry->transactions->count() }}" class="align-top">
                                                        {{ $entry->description }}
                                                        @if ($entry->sourceable && method_exists($entry->sourceable, 'path'))
                                                            <a href="{{ $entry->sourceable->path() }}" target="_blank"
                                                                class="ms-2">({{ __('messages.view_source') }})</a>
                                                        @endif
                                                    </td>
                                                @endif
                                                <td
                                                    style="padding-left: {{ $transaction->type == 'credit' ? '2rem' : '0.5rem' }};">
                                                    {{ __($transaction->account->name) }}
                                                </td>
                                                <td class="text-end">
                                                    @if ($transaction->type == 'debit')
                                                        {{ \App\Helpers\CurrencyHelper::format($transaction->amount) }}
                                                    @endif
                                                </td>
                                                <td class="text-end">
                                                    @if ($transaction->type == 'credit')
                                                        {{ \App\Helpers\CurrencyHelper::format($transaction->amount) }}
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                        @if (!$loop->last)
                                            <tr class="table-border-bottom"></tr>
                                        @endif
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="card-footer d-flex align-items-center">
                            {{ $entries->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
