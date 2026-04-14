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
                    <h2 class="page-title d-flex align-items-center">
                        <i class="ti ti-list-details me-2"></i>{{ __('messages.chart_of_accounts') }}
                        <a href="#" class="ms-3 text-muted trigger-tutorial-modal" data-bs-toggle="tooltip" title="{{ __('Tutorial') }}">
                            <i class="ti ti-help-circle fs-3"></i>
                        </a>
                    </h2>
                    <div class="text-muted mt-1">
                        {{ __('messages.chart_of_accounts_summary') }}
                    </div>
                </div>
            </div>
        </div>
        <div class="page-body mt-4">
            <div class="col-12">
                <div class="card border-0 shadow-sm rounded-3">
                    <div class="card-header">
                        <h3 class="card-title">{{ __('messages.all_accounts') }}</h3>
                    </div>
                    <div class="table-responsive">
                        <table class="table card-table table-vcenter text-nowrap datatable">
                            <thead>
                                <tr>
                                    <th>{{ __('messages.code') }}</th>
                                    <th>{{ __('messages.name') }}</th>
                                    <th>{{ __('messages.type') }}</th>
                                    <th>{{ __('messages.description') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($accounts as $type => $accountGroup)
                                    <tr>
                                        <td colspan="4" class="bg-body-secondary">
                                            <strong class="text-uppercase">{{ __('messages.' . $type) }}</strong>
                                        </td>
                                    </tr>
                                    @foreach ($accountGroup as $account)
                                        <tr>
                                            <td>{{ $account->code }}</td>
                                            <td>{{ __($account->name) }}</td>
                                            <td>{{ __(ucfirst($account->type)) }}</td>
                                            <td class="text-muted">{{ __($account->description) }}</td>
                                        </tr>
                                    @endforeach
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
