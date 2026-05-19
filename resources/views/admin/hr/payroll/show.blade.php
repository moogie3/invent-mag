@extends('admin.layouts.base')

@section('title', __('messages.hr_payroll') . ' - ' . \Carbon\Carbon::createFromDate($run->period->year, $run->period->month, 1)->translatedFormat('F Y'))

@section('header')
<meta name="turbo-cache-control" content="no-cache">
@endsection

@section('content')
    <div class="page-wrapper">
        <div class="page-body">
            <div class="{{ $containerClass ?? 'container-xl' }}">
                <div class="card border-0 shadow-sm rounded-3">
                    <div class="card-body d-flex justify-content-between align-items-center py-3">
                        <h2 class="mb-0">
                            <i class="ti ti-wallet me-2"></i>
                            {{ __('messages.hr_payroll') }}
                        </h2>
                        <a href="#" class="text-muted trigger-tutorial-modal" data-bs-toggle="tooltip" title="{{ __('messages.tutorial') }}">
                            <i class="ti ti-help-circle fs-3"></i>
                        </a>
                    </div>
                    <hr class="my-0">
                    <div class="row g-0">
                        <div class="col-12 col-md-3 border-end">
                            @include('admin.layouts.menu')
                        </div>
                        <div class="col-12 col-md-9 d-flex flex-column">
                            <div class="row row-deck row-cards">
                                <div class="col-md-12">
                                    <div class="card border-0 shadow-none rounded-3">
                                        <div class="card-body border-bottom py-3">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div class="d-flex align-items-center">
                                                    <i class="ti ti-wallet fs-1 me-3 text-primary"></i>
                                                    <div>
                                                        <h2 class="mb-1">{{ __('messages.hr_payroll') }} - {{ \Carbon\Carbon::createFromDate($run->period->year, $run->period->month, 1)->translatedFormat('F Y') }}</h2>
                                                    </div>
                                                </div>
                                                <div class="btn-list">
                                                    <a href="{{ route('admin.hr.payroll.index') }}" class="btn btn-secondary">
                                                        <i class="ti ti-arrow-left"></i> {{ __('messages.back') }}
                                                    </a>
                                                    <div class="dropdown d-inline-block">
                                                        <button class="btn btn-secondary dropdown-toggle" type="button"
                                                            id="exportShowDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                                            <i class="ti ti-download me-2"></i> {{ __('messages.export') }}
                                                        </button>
                                                        <ul class="dropdown-menu" aria-labelledby="exportShowDropdown">
                                                            <li>
                                                                <a class="dropdown-item" href="#" data-onclick="exportPayrollRun({{ $run->id }}, 'pdf'); return false;">
                                                                    {{ __('messages.export_as_pdf') }}
                                                                </a>
                                                            </li>
                                                            <li>
                                                                <a class="dropdown-item" href="#" data-onclick="exportPayrollRun({{ $run->id }}, 'csv'); return false;">
                                                                    {{ __('messages.export_as_csv') }}
                                                                </a>
                                                            </li>
                                                        </ul>
                                                    </div>
                                                    @if($run->status === 'draft')
                                                        <button class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#deletePayrollModal">
                                                            <i class="ti ti-trash"></i> {{ __('messages.delete') }}
                                                        </button>
                                                        <form method="POST" action="{{ route('admin.hr.payroll.approve', $run) }}" class="d-inline">
                                                            @csrf
                                                            <button type="submit" class="btn btn-info">
                                                                <i class="ti ti-check"></i> {{ __('messages.hr_approve_payroll') }}
                                                            </button>
                                                        </form>
                                                    @elseif($run->status === 'processed')
                                                        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#recordPaymentModal">
                                                            <i class="ti ti-cash me-2"></i> {{ __('messages.hr_record_payment') }}
                                                        </button>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                        <div class="table-responsive">
                                            <table class="table table-vcenter table-striped">
                                                <thead>
                                                    <tr>
                                                        <th>{{ __('messages.hr_employee') }}</th>
                                                        <th class="text-end">{{ __('messages.hr_base_salary') }}</th>
                                                        <th class="text-end">{{ __('messages.hr_allowances') }}</th>
                                                        <th class="text-end">{{ __('messages.hr_deductions') }}</th>
                                                        <th class="text-end">{{ __('messages.overtime') }}</th>
                                                        <th class="text-end">{{ __('messages.hr_gross_pay') }}</th>
                                                        <th class="text-end">{{ __('messages.hr_net_pay') }}</th>
                                                        <th class="text-end">{{ __('messages.days') }}</th>
                                                        <th style="width:180px;text-align:center" class="fs-4 py-3">{{ __('messages.table_action') }}</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @forelse($items as $item)
                                                        <tr>
                                                            <td>
                                                                <div class="fw-semibold">{{ $item->employee->name }}</div>
                                                                <div class="text-muted small">{{ $item->employee->employee_code }}</div>
                                                            </td>
                                                            <td class="text-end">{{ number_format($item->base_salary, 2) }}</td>
                                                            <td class="text-end">
                                                                @php $allowanceTotal = collect($item->allowances ?? [])->sum('amount'); @endphp
                                                                {{ number_format($allowanceTotal, 2) }}
                                                            </td>
                                                            <td class="text-end">
                                                                @php $deductionTotal = collect($item->deductions ?? [])->sum('amount'); @endphp
                                                                {{ number_format($deductionTotal, 2) }}
                                                            </td>
                                                            <td class="text-end">{{ number_format($item->overtime_pay, 2) }}</td>
                                                            <td class="text-end fw-semibold">{{ number_format($item->gross_pay, 2) }}</td>
                                                            <td class="text-end fw-semibold text-success">{{ number_format($item->net_pay, 2) }}</td>
                                                            <td class="text-end">{{ $item->present_days }}/{{ $item->working_days }}</td>
                                                            <td style="text-align:center">
                                                                <div class="btn-list flex-nowrap justify-content-center">
                                                                    @if($run->status === 'draft')
                                                                        <button class="btn btn-icon btn-ghost-primary"
                                                                            data-bs-toggle="modal"
                                                                            data-bs-target="#editItemModal{{ $item->id }}"
                                                                            title="{{ __('messages.edit') }}">
                                                                            <i class="ti ti-edit"></i>
                                                                        </button>
                                                                    @else
                                                                        <button class="btn btn-icon btn-ghost-secondary"
                                                                            data-bs-toggle="modal"
                                                                            data-bs-target="#editItemModal{{ $item->id }}"
                                                                            title="{{ __('messages.view') }}">
                                                                            <i class="ti ti-eye"></i>
                                                                        </button>
                                                                    @endif

                                                                    @if($run->status === 'paid')
                                                                        @if(!$item->pdf_path)
                                                                            <form method="POST" action="{{ route('admin.hr.payslip.generate', $item) }}" class="d-inline">
                                                                                @csrf
                                                                                <button type="submit" class="btn btn-icon btn-ghost-warning" title="Generate Payslip">
                                                                                    <i class="ti ti-file-type-pdf"></i>
                                                                                </button>
                                                                            </form>
                                                                        @else
                                                                            <a href="{{ route('admin.hr.payslip.download', $item) }}" class="btn btn-icon btn-ghost-success" title="Download Payslip">
                                                                                <i class="ti ti-download"></i>
                                                                            </a>
                                                                        @endif
                                                                    @endif
                                                                </div>
                                                            </td>
                                                        </tr>

                                                        <div class="modal modal-blur fade" id="editItemModal{{ $item->id }}" tabindex="-1">
                                                            <div class="modal-dialog modal-lg modal-dialog-centered">
                                                                <div class="modal-content border-0 shadow-lg rounded-3">
                                                                    <div class="modal-header">
                                                                        <h5 class="modal-title">{{ $run->status === 'draft' ? __('messages.edit') : __('messages.view') }} {{ $item->employee->name }}</h5>
                                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                                    </div>
                                                                     <form method="POST" action="{{ route('admin.hr.payroll.item.update', $item) }}">
                                                                        @csrf
                                                                        @method('PUT')
                                                                        <div class="modal-body py-3">
                                                                            <div class="card card-sm mb-3">
                                                                                <div class="card-header py-2">
                                                                                    <h6 class="card-title mb-0 d-flex align-items-center gap-2">
                                                                                        <i class="ti ti-plus text-success"></i>
                                                                                        {{ __('messages.hr_allowances') }}
                                                                                    </h6>
                                                                                </div>
                                                                                <div class="card-body py-2" id="allowances-container{{ $item->id }}">
                                                                                    @foreach($item->allowances ?? [] as $idx => $allowance)
                                                                                        <div class="row g-1 mb-1 align-items-center">
                                                                                            <div class="col-6">
                                                                                                <input type="text" name="allowances[{{ $idx }}][label]" class="form-control form-control-sm" value="{{ $allowance['label'] }}" {{ $run->status !== 'draft' ? 'readonly' : '' }}>
                                                                                            </div>
                                                                                            <div class="col-4">
                                                                                                <input type="number" name="allowances[{{ $idx }}][amount]" class="form-control form-control-sm" value="{{ $allowance['amount'] }}" step="0.01" {{ $run->status !== 'draft' ? 'readonly' : '' }}>
                                                                                            </div>
                                                                                            @if($run->status === 'draft')
                                                                                            <div class="col-2">
                                                                                                <button type="button" class="btn btn-danger btn-icon btn-sm btn-remove-row"><i class="ti ti-trash"></i></button>
                                                                                            </div>
                                                                                            @endif
                                                                                        </div>
                                                                                    @endforeach
                                                                                    @if($run->status === 'draft')
                                                                                    <button type="button" class="btn btn-sm btn-outline-secondary mt-1" data-onclick="addAllowance({{ $item->id }})">
                                                                                        <i class="ti ti-plus"></i> {{ __('messages.add') }}
                                                                                    </button>
                                                                                    @endif
                                                                                </div>
                                                                            </div>

                                                                            <div class="card card-sm">
                                                                                <div class="card-header py-2">
                                                                                    <h6 class="card-title mb-0 d-flex align-items-center gap-2">
                                                                                        <i class="ti ti-minus text-danger"></i>
                                                                                        {{ __('messages.hr_deductions') }}
                                                                                    </h6>
                                                                                </div>
                                                                                <div class="card-body py-2" id="deductions-container{{ $item->id }}">
                                                                                    @foreach($item->deductions ?? [] as $idx => $deduction)
                                                                                        <div class="row g-1 mb-1 align-items-center">
                                                                                            <div class="col-6">
                                                                                                <input type="text" name="deductions[{{ $idx }}][label]" class="form-control form-control-sm" value="{{ $deduction['label'] }}" {{ $run->status !== 'draft' ? 'readonly' : '' }}>
                                                                                            </div>
                                                                                            <div class="col-4">
                                                                                                <input type="number" name="deductions[{{ $idx }}][amount]" class="form-control form-control-sm" value="{{ $deduction['amount'] }}" step="0.01" {{ $run->status !== 'draft' ? 'readonly' : '' }}>
                                                                                            </div>
                                                                                            @if($run->status === 'draft')
                                                                                            <div class="col-2">
                                                                                                <button type="button" class="btn btn-danger btn-icon btn-sm btn-remove-row"><i class="ti ti-trash"></i></button>
                                                                                            </div>
                                                                                            @endif
                                                                                        </div>
                                                                                    @endforeach
                                                                                    @if($run->status === 'draft')
                                                                                    <button type="button" class="btn btn-sm btn-outline-secondary mt-1" data-onclick="addDeduction({{ $item->id }})">
                                                                                        <i class="ti ti-plus"></i> {{ __('messages.add') }}
                                                                                    </button>
                                                                                    @endif
                                                                                </div>
                                                                            </div>

                                                                            @if($run->status !== 'draft')
                                                                            <div class="row mt-3 g-3">
                                                                                <div class="col-6">
                                                                                    <div class="p-2 bg-success-light rounded-2 text-center">
                                                                                        <small class="text-muted d-block">{{ __('messages.hr_allowances') }}</small>
                                                                                        <strong class="text-success">{{ number_format(collect($item->allowances ?? [])->sum('amount'), 2) }}</strong>
                                                                                    </div>
                                                                                </div>
                                                                                <div class="col-6">
                                                                                    <div class="p-2 bg-danger-light rounded-2 text-center">
                                                                                        <small class="text-muted d-block">{{ __('messages.hr_deductions') }}</small>
                                                                                        <strong class="text-danger">{{ number_format(collect($item->deductions ?? [])->sum('amount'), 2) }}</strong>
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                            @endif
                                                                        </div>
                                                                        <div class="modal-footer">
                                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ $run->status === 'draft' ? __('messages.cancel') : __('messages.close') }}</button>
                                                                            @if($run->status === 'draft')
                                                                            <button type="submit" class="btn btn-primary">{{ __('messages.save') }}</button>
                                                                            @endif
                                                                        </div>
                                                                    </form>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    @empty
                                                        <tr>
                                                            <td colspan="9" class="text-center text-muted">
                                                                {{ __('messages.no_data') }}
                                                            </td>
                                                        </tr>
                                                    @endforelse
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if($run->status === 'processed')
    <div class="modal modal-blur fade" id="recordPaymentModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-3">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('messages.hr_record_payment') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="{{ route('admin.hr.payroll.pay', $run) }}">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Payment Source Account</label>
                            <select name="account_id" class="form-select" required>
                                <option value="" disabled selected>-- Select an account --</option>
                                @foreach($accounts as $account)
                                    <option value="{{ $account->id }}">{{ $account->code }} - {{ $account->name }}</option>
                                @endforeach
                            </select>
                            <div class="form-hint mt-2">The total net pay will be credited from this account and debited to the Salary Payable account.</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('messages.cancel') }}</button>
                        <button type="submit" class="btn btn-success"><i class="ti ti-cash me-2"></i>Record Payment</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif

    @if($run->status === 'draft')
    <div class="modal modal-blur fade" id="deletePayrollModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content border-0 shadow-lg rounded-3">
                <div class="modal-header">
                    <h5 class="modal-title">Delete Payroll</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-status bg-danger"></div>
                <div class="modal-body text-center py-4">
                    <i class="ti ti-alert-triangle text-danger" style="font-size: 3rem;"></i>
                    <h3>Are you sure?</h3>
                    <div class="text-muted">Do you really want to delete this draft payroll run? You can regenerate it later.</div>
                </div>
                <div class="modal-footer">
                    <div class="w-100">
                        <div class="row">
                            <div class="col">
                                <button type="button" class="btn w-100" data-bs-dismiss="modal">{{ __('messages.cancel') }}</button>
                            </div>
                            <div class="col">
                                <form method="POST" action="{{ route('admin.hr.payroll.destroy', $run) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger w-100">{{ __('messages.delete') }}</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
@endsection