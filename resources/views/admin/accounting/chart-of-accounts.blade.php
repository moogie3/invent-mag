@extends('admin.layouts.base')

@section('title', __('messages.chart_of_accounts'))

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
                            <i class="ti ti-list-details"></i>
                        </span>
                        {{ __('messages.chart_of_accounts') }}
                    </h2>
                    <div class="text-muted mt-1">
                        {{ __('messages.chart_of_accounts_summary') }}
                    </div>
                </div>
            </div>
        </div>
        <div class="page-body mt-4">
            <div class="col-12">
                <div class="card">
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
                                        <td colspan="4" class="bg-light">
                                            <strong class="text-uppercase">{{ __('messages.' . $type) }}</strong>
                                        </td>
                                    </tr>
                                    @foreach ($accountGroup as $account)
                                        <tr>
                                            <td>{{ $account->code }}</td>
                                            <td>{{ $account->name }}</td>
                                            <td>{{ ucfirst($account->type) }}</td>
                                            <td class="text-muted">{{ $account->description }}</td>
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
