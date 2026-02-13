@extends('admin.layouts.base')

@section('title', __('messages.chart_of_accounts'))

@section('content')
    <div class="{{ $containerClass ?? "container-xl" }}">
        <div class="page-header d-print-none mt-4">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <div class="page-pretitle">
                        {{ __('messages.accounting') }}
                    </div>
                    <h2 class="page-title">
                        <span class="nav-link-icon d-md-none d-lg-inline-block">
                            <i class="ti ti-building-bank"></i>
                        </span>
                        {{ __('messages.chart_of_accounts') }}
                    </h2>
                    <div class="text-muted mt-1">
                        {{ __('messages.chart_of_accounts_summary') }}
                    </div>
                </div>
                <div class="col-auto ms-auto d-print-none">
                    <div class="dropdown d-inline-block me-2">
                        <button type="button" class="btn btn-secondary dropdown-toggle" data-bs-toggle="dropdown"
                            aria-expanded="false">
                            <i class="ti ti-download fs-4 me-2"></i> {{ __('messages.export') }}
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="#" onclick="exportAccounts('csv')">Export as CSV</a>
                            </li>
                            <li><a class="dropdown-item" href="#" onclick="exportAccounts('pdf')">Export as PDF</a>
                            </li>
                        </ul>
                    </div>
                    <a href="{{ route('admin.accounting.accounts.create') }}" class="btn btn-primary">
                        <i class="ti ti-plus"></i>
                        {{ __('messages.add_account') }}
                    </a>
                </div>
            </div>
        </div>

        <div class="page-body mt-4">
            <div class="card border-0 shadow-sm rounded-3">
                <div class="table-responsive">
                    <table class="table table-vcenter card-table">
                        <thead style="font-size: large">
                            <tr>
                                <th class="fs-4 py-3">{{ __('messages.name') }}</th>
                                <th class="fs-4 py-3">{{ __('messages.code') }}</th>
                                <th class="fs-4 py-3">{{ __('messages.type') }}</th>
                                <th class="fs-4 py-3">{{ __('messages.active') }}</th>
                                <th class="fs-4 py-3 w-1">{{ __('messages.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($accounts as $account)
                                @include('admin.layouts.partials.accounts.account_row', [
                                    'account' => $account,
                                    'level' => 0,
                                ])
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
