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
                            <i class="ti ti-building-bank"></i>
                        </span>
                        {{ __('messages.chart_of_accounts') }}
                    </h2>
                    <div class="text-muted mt-1">
                        {{ __('messages.chart_of_accounts_summary') }}
                    </div>
                </div>
                <div class="col-auto ms-auto d-print-none">
                    <a href="{{ route('admin.accounting.accounts.create') }}" class="btn btn-primary">
                        <i class="ti ti-plus"></i>
                        {{ __('messages.add_account') }}
                    </a>
                </div>
            </div>
        </div>

        <div class="page-body mt-4">
            <div class="card">
                <div class="table-responsive">
                    <table class="table table-vcenter card-table">
                        <thead>
                            <tr>
                                <th>{{ __('messages.name') }}</th>
                                <th>{{ __('messages.code') }}</th>
                                <th>{{ __('messages.type') }}</th>
                                <th>{{ __('messages.active') }}</th>
                                <th class="w-1"></th>
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
