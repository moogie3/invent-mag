@extends('admin.layouts.base')

@section('title', __('messages.accounting_settings'))

@section('content')
    <meta name="apply-template-warning" content="{{ __('messages.apply_template_warning') }}">
    <div class="page-wrapper">
        <div class="page-wrapper">
            <div class="page-body">
                <div class="{{ $containerClass ?? "container-xl" }}">
                    <div class="card">
                        <div class="card-body">
                            <h2><i class="ti ti-settings me-2"></i>{{ __('messages.system_settings') }}</h2>
                        </div>
                        <hr class="my-0">
                        <div class="row g-0">
                            <div class="col-12 col-md-3 border-end">
                                @include('admin.layouts.menu')
                            </div>
                            <div
                                class="@if ((auth()->user()->system_settings['navigation_type'] ?? 'sidebar') === 'navbar') col-9 @else col-12 col-md-9 @endif d-flex flex-column">
                                <div class="card-body">
                                    <form action="{{ route('admin.setting.accounting.update') }}" method="POST">
                                        @csrf
                                        <!-- Reset COA Section -->
                                        <div class="settings-section mb-5">
                                            <div class="settings-section-header">
                                                <div class="settings-icon-wrapper text-warning bg-warning-lt">
                                                    <i class="ti ti-alert-triangle"></i>
                                                </div>
                                                <div class="settings-section-title">
                                                    <h3 class="mb-1 text-warning">{{ __('messages.reset_chart_of_accounts') }}</h3>
                                                    <p class="text-muted mb-0 small">
                                                        {{ __('messages.reset_coa_description') }}
                                                    </p>
                                                </div>
                                            </div>
                                            <div class="settings-section-content">
                                                <div class="alert alert-warning" role="alert">
                                                    <h4 class="alert-title">{{ __('messages.warning') }}</h4>
                                                    <div class="text-muted">
                                                        {{ __('messages.reset_coa_warning_text') }}
                                                    </div>
                                                </div>
                                                <div class="d-flex justify-content-end">
                                                    <button type="button" data-bs-toggle="modal" data-bs-target="#coaResetConfirmationModal"
                                                        class="btn btn-warning">
                                                        <i class="ti ti-refresh me-2"></i>
                                                        {{ __('messages.reset_to_default') }}
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                        <!-- Accounting Integration Settings -->
                                        <div class="settings-section mb-5">
                                            <div class="settings-section-header">
                                                <div class="settings-icon-wrapper">
                                                    <i class="ti ti-calculator"></i>
                                                </div>
                                                <div class="settings-section-title">
                                                    <h3 class="mb-1">{{ __('messages.accounting_integration') }}</h3>
                                                    <p class="text-muted mb-0 small">
                                                        {{ __('messages.map_business_actions_to_your_chart_of_accounts') }}
                                                    </p>
                                                </div>
                                            </div>
                                            <div class="settings-section-content">
                                                <div class="row g-3">
                                                    <div class="col-md-6">
                                                        <label
                                                            class="form-label">{{ __('messages.sales_revenue_account') }}</label>
                                                        <select name="sales_revenue_account_id" class="form-select"
                                                            required>
                                                            <option value="">{{ __('messages.select_account') }}
                                                            </option>
                                                            @foreach ($accounts['revenue'] ?? [] as $account)
                                                                <option value="{{ $account->id }}"
                                                                    @if (($settings['sales_revenue_account_id'] ?? null) == $account->id) selected @endif>
                                                                    {{ __($account->name) }} ({{ $account->code }})
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label
                                                            class="form-label">{{ __('messages.accounts_receivable_account') }}</label>
                                                        <select name="accounts_receivable_account_id" class="form-select"
                                                            required>
                                                            <option value="">{{ __('messages.select_account') }}
                                                            </option>
                                                            @foreach ($accounts['asset'] ?? [] as $account)
                                                                <option value="{{ $account->id }}"
                                                                    @if (($settings['accounts_receivable_account_id'] ?? null) == $account->id) selected @endif>
                                                                    {{ __($account->name) }} ({{ $account->code }})
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label
                                                            class="form-label">{{ __('messages.cost_of_goods_sold_account') }}</label>
                                                        <select name="cost_of_goods_sold_account_id" class="form-select"
                                                            required>
                                                            <option value="">{{ __('messages.select_account') }}
                                                            </option>
                                                            @foreach ($accounts['expense'] ?? [] as $account)
                                                                <option value="{{ $account->id }}"
                                                                    @if (($settings['cost_of_goods_sold_account_id'] ?? null) == $account->id) selected @endif>
                                                                    {{ __($account->name) }} ({{ $account->code }})
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label
                                                            class="form-label">{{ __('messages.inventory_account') }}</label>
                                                        <select name="inventory_account_id" class="form-select" required>
                                                            <option value="">{{ __('messages.select_account') }}
                                                            </option>
                                                            @foreach ($accounts['asset'] ?? [] as $account)
                                                                <option value="{{ $account->id }}"
                                                                    @if (($settings['inventory_account_id'] ?? null) == $account->id) selected @endif>
                                                                    {{ __($account->name) }} ({{ $account->code }})
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label
                                                            class="form-label">{{ __('messages.accounts_payable_account') }}</label>
                                                        <select name="accounts_payable_account_id" class="form-select"
                                                            required>
                                                            <option value="">{{ __('messages.select_account') }}
                                                            </option>
                                                            @foreach ($accounts['liability'] ?? [] as $account)
                                                                <option value="{{ $account->id }}"
                                                                    @if (($settings['accounts_payable_account_id'] ?? null) == $account->id) selected @endif>
                                                                    {{ __($account->name) }} ({{ $account->code }})
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label">{{ __('messages.cash_account') }}</label>
                                                        <select name="cash_account_id" class="form-select" required>
                                                            <option value="">{{ __('messages.select_account') }}
                                                            </option>
                                                            @foreach ($accounts['asset'] ?? [] as $account)
                                                                <option value="{{ $account->id }}"
                                                                    @if (($settings['cash_account_id'] ?? null) == $account->id) selected @endif>
                                                                    {{ __($account->name) }} ({{ $account->code }})
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                    </div>

                                                </div>
                                            </div>
                                        </div>
                                        <div class="card-footer bg-transparent mt-auto">
                                            <div class="btn-list justify-content-end">
                                                <button type="submit"
                                                    class="btn btn-primary">{{ __('messages.save_settings') }}</button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- COA Reset Confirmation Modal --}}
    <div class="modal modal-blur fade" id="coaResetConfirmationModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
            <div class="modal-content">
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                <div class="modal-status bg-danger"></div>
                <div class="modal-body text-center py-4">
                    <i class="ti ti-alert-triangle icon mb-2 text-danger icon-lg"></i>
                    <h3>{{ __('messages.are_you_sure') }}</h3>
                    <div class="text-muted">{{ __('messages.reset_coa_warning_message') }}</div>
                </div>
                <div class="modal-footer">
                    <div class="w-100">
                        <div class="row">
                            <div class="col">
                                <a href="#" class="btn w-100" data-bs-dismiss="modal">
                                    {{ __('messages.cancel') }}
                                </a>
                            </div>
                            <div class="col">
                                <form action="{{ route('admin.setting.reset-coa-default') }}" method="POST">
                                    @csrf
                                    <button type="submit" class="btn btn-danger w-100">
                                        {{ __('messages.reset') }}
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
