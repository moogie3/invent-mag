@extends('admin.layouts.base')

@section('title', __('messages.hr_payroll') . ' - ' . $run->period->year . '-' . str_pad($run->period->month, 2, '0', STR_PAD_LEFT))

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
                            {{ __('messages.hr_payroll') }} - {{ $run->period->year }}-{{ str_pad($run->period->month, 2, '0', STR_PAD_LEFT) }}
                        </h2>
                        <div class="btn-list">
                            <a href="{{ route('admin.hr.payroll.index') }}" class="btn btn-secondary">
                                <i class="ti ti-arrow-left"></i> {{ __('messages.back') }}
                            </a>
                            @if($run->status === 'draft')
                                <form method="POST" action="{{ route('admin.hr.payroll.pay', $run) }}" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-success">
                                        <i class="ti ti-check"></i> {{ __('messages.hr_mark_paid') }}
                                    </button>
                                </form>
                            @endif
                        </div>
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
                                                                    <button class="btn btn-icon btn-ghost-primary"
                                                                        data-bs-toggle="modal"
                                                                        data-bs-target="#editItemModal{{ $item->id }}"
                                                                        title="{{ __('messages.edit') }}">
                                                                        <i class="ti ti-edit"></i>
                                                                    </button>
                                                                </div>
                                                            </td>
                                                        </tr>

                                                        <div class="modal modal-blur" id="editItemModal{{ $item->id }}" tabindex="-1">
                                                            <div class="modal-dialog modal-lg modal-dialog-centered">
                                                                <div class="modal-content">
                                                                    <div class="modal-header">
                                                                        <h5 class="modal-title">{{ __('messages.edit') }} {{ $item->employee->name }}</h5>
                                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                                    </div>
                                                                    <form method="POST" action="{{ route('admin.hr.payroll.item.update', $item) }}">
                                                                        @csrf
                                                                        @method('PUT')
                                                                        <div class="modal-body">
                                                                            <h6>{{ __('messages.hr_allowances') }}</h6>
                                                                            <div id="allowances-container{{ $item->id }}">
                                                                                @foreach($item->allowances ?? [] as $idx => $allowance)
                                                                                    <div class="row mb-2">
                                                                                        <div class="col-6">
                                                                                            <input type="text" name="allowances[{{ $idx }}][label]" class="form-control" value="{{ $allowance['label'] }}">
                                                                                        </div>
                                                                                        <div class="col-4">
                                                                                            <input type="number" name="allowances[{{ $idx }}][amount]" class="form-control" value="{{ $allowance['amount'] }}" step="0.01">
                                                                                        </div>
                                                                                        <div class="col-2">
                                                                                            <button type="button" class="btn btn-danger btn-remove-row"><i class="ti ti-trash"></i></button>
                                                                                        </div>
                                                                                    </div>
                                                                                @endforeach
                                                                            </div>
                                                                            <button type="button" class="btn btn-sm btn-outline-secondary mb-3" onclick="addAllowance({{ $item->id }})">
                                                                                <i class="ti ti-plus"></i> {{ __('messages.add') }}
                                                                            </button>

                                                                            <hr>

                                                                            <h6>{{ __('messages.hr_deductions') }}</h6>
                                                                            <div id="deductions-container{{ $item->id }}">
                                                                                @foreach($item->deductions ?? [] as $idx => $deduction)
                                                                                    <div class="row mb-2">
                                                                                        <div class="col-6">
                                                                                            <input type="text" name="deductions[{{ $idx }}][label]" class="form-control" value="{{ $deduction['label'] }}">
                                                                                        </div>
                                                                                        <div class="col-4">
                                                                                            <input type="number" name="deductions[{{ $idx }}][amount]" class="form-control" value="{{ $deduction['amount'] }}" step="0.01">
                                                                                        </div>
                                                                                        <div class="col-2">
                                                                                            <button type="button" class="btn btn-danger btn-remove-row"><i class="ti ti-trash"></i></button>
                                                                                        </div>
                                                                                    </div>
                                                                                @endforeach
                                                                            </div>
                                                                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="addDeduction({{ $item->id }})">
                                                                                <i class="ti ti-plus"></i> {{ __('messages.add') }}
                                                                            </button>
                                                                        </div>
                                                                        <div class="modal-footer">
                                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('messages.cancel') }}</button>
                                                                            <button type="submit" class="btn btn-primary">{{ __('messages.save') }}</button>
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
@endsection