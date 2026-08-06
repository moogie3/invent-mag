@extends('admin.layouts.base')

@section('title', __('messages.accounting_settings'))

@section('content')
    <meta name="apply-template-warning" content="{{ __('messages.apply_template_warning') }}">
    <div class="page-wrapper">
        <div class="page-body">
                <div class="{{ $containerClass ?? "container-xl" }}">
                    <div class="card border-0 shadow-sm rounded-3">
                        <div class="card-body d-flex justify-content-between align-items-center py-3">
                            <h2 class="mb-0"><i class="ti ti-settings me-2"></i>{{ __('messages.system_settings') }}</h2>
                            <a href="#" class="text-muted trigger-tutorial-modal" data-bs-toggle="tooltip" title="{{ __('messages.tutorial') }}">
                                <i class="ti ti-help-circle fs-3"></i>
                            </a>
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
                                                        <label class="form-label"
                                                            for="sales_revenue_account_id">{{ __('messages.sales_revenue_account') }}</label>
                                                        <select name="sales_revenue_account_id" id="sales_revenue_account_id" class="form-select @error('sales_revenue_account_id') is-invalid @enderror"
                                                            required>
                                                            <option value="">{{ __('messages.select_account') }}
                                                            </option>
                                                            @foreach ($accounts['revenue'] ?? [] as $account)
                                                                <option value="{{ $account->id }}"
                                                                    @if (old('sales_revenue_account_id', $settings['sales_revenue_account_id'] ?? null) == $account->id) selected @endif>
                                                                    {{ __($account->name) }} ({{ $account->code }})
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                        @error('sales_revenue_account_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label"
                                                            for="accounts_receivable_account_id">{{ __('messages.accounts_receivable_account') }}</label>
                                                        <select name="accounts_receivable_account_id" id="accounts_receivable_account_id" class="form-select @error('accounts_receivable_account_id') is-invalid @enderror"
                                                            required>
                                                            <option value="">{{ __('messages.select_account') }}
                                                            </option>
                                                            @foreach ($accounts['asset'] ?? [] as $account)
                                                                <option value="{{ $account->id }}"
                                                                    @if (old('accounts_receivable_account_id', $settings['accounts_receivable_account_id'] ?? null) == $account->id) selected @endif>
                                                                    {{ __($account->name) }} ({{ $account->code }})
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                        @error('accounts_receivable_account_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label"
                                                            for="cost_of_goods_sold_account_id">{{ __('messages.cost_of_goods_sold_account') }}</label>
                                                        <select name="cost_of_goods_sold_account_id" id="cost_of_goods_sold_account_id" class="form-select @error('cost_of_goods_sold_account_id') is-invalid @enderror"
                                                            required>
                                                            <option value="">{{ __('messages.select_account') }}
                                                            </option>
                                                            @foreach ($accounts['expense'] ?? [] as $account)
                                                                <option value="{{ $account->id }}"
                                                                    @if (old('cost_of_goods_sold_account_id', $settings['cost_of_goods_sold_account_id'] ?? null) == $account->id) selected @endif>
                                                                    {{ __($account->name) }} ({{ $account->code }})
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                        @error('cost_of_goods_sold_account_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label"
                                                            for="inventory_account_id">{{ __('messages.inventory_account') }}</label>
                                                        <select name="inventory_account_id" id="inventory_account_id" class="form-select @error('inventory_account_id') is-invalid @enderror" required>
                                                            <option value="">{{ __('messages.select_account') }}
                                                            </option>
                                                            @foreach ($accounts['asset'] ?? [] as $account)
                                                                <option value="{{ $account->id }}"
                                                                    @if (old('inventory_account_id', $settings['inventory_account_id'] ?? null) == $account->id) selected @endif>
                                                                    {{ __($account->name) }} ({{ $account->code }})
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                        @error('inventory_account_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label"
                                                            for="accounts_payable_account_id">{{ __('messages.accounts_payable_account') }}</label>
                                                        <select name="accounts_payable_account_id" id="accounts_payable_account_id" class="form-select @error('accounts_payable_account_id') is-invalid @enderror"
                                                            required>
                                                            <option value="">{{ __('messages.select_account') }}
                                                            </option>
                                                            @foreach ($accounts['liability'] ?? [] as $account)
                                                                <option value="{{ $account->id }}"
                                                                    @if (old('accounts_payable_account_id', $settings['accounts_payable_account_id'] ?? null) == $account->id) selected @endif>
                                                                    {{ __($account->name) }} ({{ $account->code }})
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                        @error('accounts_payable_account_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label"
                                                            for="cash_account_id">{{ __('messages.cash_account') }}</label>
                                                        <select name="cash_account_id" id="cash_account_id" class="form-select @error('cash_account_id') is-invalid @enderror" required>
                                                            <option value="">{{ __('messages.select_account') }}
                                                            </option>
                                                            @foreach ($accounts['asset'] ?? [] as $account)
                                                                <option value="{{ $account->id }}"
                                                                    @if (old('cash_account_id', $settings['cash_account_id'] ?? null) == $account->id) selected @endif>
                                                                    {{ __($account->name) }} ({{ $account->code }})
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                        @error('cash_account_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label" for="ppn_keluaran_account_id">{{ __('messages.ppn_keluaran_account') }}</label>
                                                        <select name="ppn_keluaran_account_id" id="ppn_keluaran_account_id" class="form-select @error('ppn_keluaran_account_id') is-invalid @enderror">
                                                            <option value="">{{ __('messages.select_account') }}
                                                            </option>
                                                            @foreach ($accounts['liability'] ?? [] as $account)
                                                                <option value="{{ $account->id }}"
                                                                    @if (old('ppn_keluaran_account_id', $settings['ppn_keluaran_account_id'] ?? null) == $account->id) selected @endif>
                                                                    {{ __($account->name) }} ({{ $account->code }})
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                        @error('ppn_keluaran_account_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label" for="ppn_masukan_account_id">{{ __('messages.ppn_masukan_account') }}</label>
                                                        <select name="ppn_masukan_account_id" id="ppn_masukan_account_id" class="form-select @error('ppn_masukan_account_id') is-invalid @enderror">
                                                            <option value="">{{ __('messages.select_account') }}
                                                            </option>
                                                            @foreach ($accounts['asset'] ?? [] as $account)
                                                                <option value="{{ $account->id }}"
                                                                    @if (old('ppn_masukan_account_id', $settings['ppn_masukan_account_id'] ?? null) == $account->id) selected @endif>
                                                                    {{ __($account->name) }} ({{ $account->code }})
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                        @error('ppn_masukan_account_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label" for="pph_receivable_account_id">{{ __('messages.pph_receivable_account') }}</label>
                                                        <select name="pph_receivable_account_id" id="pph_receivable_account_id" class="form-select @error('pph_receivable_account_id') is-invalid @enderror">
                                                            <option value="">{{ __('messages.select_account') }}
                                                            </option>
                                                            @foreach ($accounts['asset'] ?? [] as $account)
                                                                <option value="{{ $account->id }}"
                                                                    @if (old('pph_receivable_account_id', $settings['pph_receivable_account_id'] ?? null) == $account->id) selected @endif>
                                                                    {{ __($account->name) }} ({{ $account->code }})
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                        @error('pph_receivable_account_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label" for="pph_payable_account_id">{{ __('messages.pph_payable_account') }}</label>
                                                        <select name="pph_payable_account_id" id="pph_payable_account_id" class="form-select @error('pph_payable_account_id') is-invalid @enderror">
                                                            <option value="">{{ __('messages.select_account') }}
                                                            </option>
                                                            @foreach ($accounts['liability'] ?? [] as $account)
                                                                <option value="{{ $account->id }}"
                                                                    @if (old('pph_payable_account_id', $settings['pph_payable_account_id'] ?? null) == $account->id) selected @endif>
                                                                    {{ __($account->name) }} ({{ $account->code }})
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                        @error('pph_payable_account_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
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
