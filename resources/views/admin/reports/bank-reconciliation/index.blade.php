@extends('admin.layouts.base')

@section('title', __('messages.bank_reconciliation'))

@section('content')
    <div class="page-wrapper">
        <div class="page-header d-print-none">
            <div class="{{ ($containerClass ?? 'container-xl') === 'container-xl' ? 'container-fluid' : $containerClass }}"
                 style="{{ ($containerClass ?? 'container-xl') === 'container-xl' ? 'max-width: 90%;' : '' }}">
                <div class="row align-items-center">
                    <div class="col">
                        <div class="page-pretitle">
                            {{ __('messages.reports') }}
                        </div>
                        <h2 class="page-title d-flex align-items-center">
                            <i class="ti ti-building-bank me-2"></i> {{ __('messages.bank_reconciliation') }}
                            <a href="#" class="ms-3 text-muted trigger-tutorial-modal" data-bs-toggle="tooltip" title="{{ __('messages.tutorial') }}">
                                <i class="ti ti-help-circle fs-3"></i>
                            </a>
                        </h2>
                        <div class="text-muted mt-1">
                            {{ __('messages.bank_reconciliation_summary') }}
                        </div>
                    </div>
                    <div class="col-auto ms-auto d-print-none">
                        <div class="btn-list">
                            <a href="{{ route('admin.bank-reconciliation.create') }}" class="btn btn-primary d-none d-sm-inline-block">
                                <i class="ti ti-plus me-2"></i>
                                {{ __('messages.bank_reconciliation_import') }}
                            </a>
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
                                        <span class="bg-blue-lt text-blue avatar"><i class="ti ti-building-bank"></i></span>
                                    </div>
                                    <div class="col">
                                        <div class="font-weight-medium">{{ __('messages.bank_reconciliation_total') }}</div>
                                        <div class="text-muted">{{ $reconciliations->total() }}</div>
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
                                        <span class="bg-warning-lt text-warning avatar"><i class="ti ti-hourglass"></i></span>
                                    </div>
                                    <div class="col">
                                        <div class="font-weight-medium">{{ __('messages.bank_reconciliation_open') }}</div>
                                        <div class="text-muted">{{ $totalOpen }}</div>
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
                                        <span class="bg-success-lt text-success avatar"><i class="ti ti-circle-check"></i></span>
                                    </div>
                                    <div class="col">
                                        <div class="font-weight-medium">{{ __('messages.bank_reconciliation_completed') }}</div>
                                        <div class="text-muted">{{ $totalCompleted }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm rounded-3 mt-3">
                    <div class="card-header">
                        <h3 class="card-title">{{ __('messages.bank_reconciliation_list') }}</h3>
                    </div>
                    <div class="card-body border-bottom py-3">
                        <form action="{{ route('admin.bank-reconciliation.index') }}" method="GET" class="row g-2">
                            <div class="col-auto">
                                <select name="month" class="form-select">
                                    <option value="">{{ __('messages.select_month') }}</option>
                                    @foreach (range(1, 12) as $m)
                                        <option value="{{ $m }}" {{ ($filters['month'] ?? '') == $m ? 'selected' : '' }}>
                                            {{ \Carbon\Carbon::create()->month($m)->translatedFormat('F') }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-auto">
                                <select name="year" class="form-select">
                                    <option value="">{{ __('messages.select_year') }}</option>
                                    @foreach (range(now()->year, now()->year - 4) as $y)
                                        <option value="{{ $y }}" {{ ($filters['year'] ?? '') == $y ? 'selected' : '' }}>{{ $y }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-auto">
                                <button type="submit" class="btn btn-primary">
                                    <i class="ti ti-filter me-1"></i> {{ __('messages.filter') }}
                                </button>
                            </div>
                        </form>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-vcenter card-table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>{{ __('messages.account') }}</th>
                                    <th>{{ __('messages.period') }}</th>
                                    <th class="text-end">{{ __('messages.bank_reconciliation_opening_balance') }}</th>
                                    <th class="text-end">{{ __('messages.bank_reconciliation_closing_balance') }}</th>
                                    <th class="text-end">{{ __('messages.bank_reconciliation_matched_amount') }}</th>
                                    <th class="text-center">{{ __('messages.bank_reconciliation_lines') }}</th>
                                    <th>{{ __('messages.bank_reconciliation_status') }}</th>
                                    <th class="w-1">{{ __('messages.action') ?? 'Action' }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($reconciliations as $reconciliation)
                                    <tr>
                                        <td><strong>{{ $reconciliation->account?->name }}</strong></td>
                                        <td>{{ $reconciliation->period_start->format('d M Y') }} – {{ $reconciliation->period_end->format('d M Y') }}</td>
                                        <td class="text-end">{{ \App\Helpers\CurrencyHelper::format($reconciliation->opening_balance) }}</td>
                                        <td class="text-end">{{ \App\Helpers\CurrencyHelper::format($reconciliation->closing_balance) }}</td>
                                        <td class="text-end">{{ \App\Helpers\CurrencyHelper::format($reconciliation->matched_amount) }}</td>
                                        <td class="text-center">{{ $reconciliation->statement_lines_count }}</td>
                                        <td>
                                            @if($reconciliation->isCompleted())
                                                <span class="badge bg-success text-white">{{ __('messages.bank_reconciliation_completed') }}</span>
                                            @else
                                                <span class="badge bg-warning text-white">{{ __('messages.bank_reconciliation_open') }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="btn-list">
                                                <a href="{{ route('admin.bank-reconciliation.show', $reconciliation->id) }}" class="btn btn-icon btn-ghost-primary" title="{{ __('messages.view') ?? 'View' }}">
                                                    <i class="ti ti-eye"></i>
                                                </a>
                                                <button type="button" class="btn btn-icon btn-ghost-danger" data-bs-toggle="modal"
                                                    data-bs-target="#deleteReconciliationModal"
                                                    data-delete-url="{{ route('admin.bank-reconciliation.destroy', $reconciliation->id) }}"
                                                    data-delete-label="{{ $reconciliation->account?->name }}" title="{{ __('messages.delete') ?? 'Delete' }}">
                                                    <i class="ti ti-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8">
                                            <div class="empty">
                                                <div class="empty-img">
                                                    <i class="ti ti-building-bank" style="font-size: 5rem; color: #ccc;"></i>
                                                </div>
                                                <p class="empty-title">{{ __('messages.bank_reconciliation_empty') }}</p>
                                                <p class="empty-subtitle text-muted">{{ __('messages.bank_reconciliation_empty_desc') }}</p>
                                                <div class="empty-action">
                                                    <a href="{{ route('admin.bank-reconciliation.create') }}" class="btn btn-primary">
                                                        <i class="ti ti-plus me-2"></i>{{ __('messages.bank_reconciliation_import') }}
                                                    </a>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if ($reconciliations->hasPages())
                        <div class="card-footer d-flex align-items-center">
                            {{ $reconciliations->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="modal modal-blur fade" id="deleteReconciliationModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
            <div class="modal-content">
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                <div class="modal-status bg-danger"></div>
                <div class="modal-body text-center py-4">
                    <i class="ti ti-alert-triangle icon mb-2 text-danger" style="font-size: 3rem;"></i>
                    <h3>{{ __('messages.bank_reconciliation_delete_confirm') }}</h3>
                    <div class="text-muted">
                        <span id="deleteReconciliationLabel"></span>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-link link-secondary me-auto" data-bs-dismiss="modal">{{ __('messages.cancel') }}</button>
                    <form id="deleteReconciliationForm" method="POST">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger">{{ __('messages.bank_reconciliation_delete') }}</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const modal = document.getElementById('deleteReconciliationModal');
            if (!modal) return;
            modal.addEventListener('show.bs.modal', function (event) {
                const button = event.relatedTarget;
                if (!button) return;
                document.getElementById('deleteReconciliationForm').action = button.getAttribute('data-delete-url');
                document.getElementById('deleteReconciliationLabel').textContent = button.getAttribute('data-delete-label');
            });
        });
    </script>
@endsection
