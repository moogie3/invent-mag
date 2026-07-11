@extends('superadmin.layouts.base')
@section('title', 'Edit Plan')
@section('content')
<div class="page-wrapper">
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <div class="d-flex align-items-center">
                        <a href="{{ route('superadmin.plans.index') }}" class="btn btn-ghost btn-sm me-2">
                            <i class="ti ti-arrow-left"></i>
                        </a>
                        <div>
                            <div class="page-pretitle"><i class="ti ti-packages"></i> Management / Plans</div>
                            <h2 class="page-title mb-0">
                                Edit Plan: {{ $plan->name }}
                                <a href="#" class="ms-2 text-muted" data-bs-toggle="modal" data-bs-target="#how-it-works-modal" title="How It Works">
                                    <i class="ti ti-help-circle fs-5"></i>
                                </a>
                            </h2>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="page-body">
        <div class="container-xl">
            <div class="card shadow border-0 rounded-4">
                <div class="card-body">
                    <form method="POST" action="{{ route('superadmin.plans.update', $plan) }}">
                        @csrf @method('PUT')
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label class="form-label">Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control" value="{{ old('name', $plan->name) }}" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Slug <span class="text-danger">*</span></label>
                                <input type="text" name="slug" class="form-control" value="{{ old('slug', $plan->slug) }}" required>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Price <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" name="price" class="form-control" value="{{ old('price', $plan->price) }}" required>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Currency</label>
                                <select name="currency_code" class="form-select">
                                    @foreach ($predefinedCurrencies as $currency)
                                        <option value="{{ $currency['code'] }}" {{ old('currency_code', $plan->currency_code ?? 'USD') == $currency['code'] ? 'selected' : '' }}>
                                            {{ $currency['code'] }} ({{ $currency['symbol'] }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Billing Cycle <span class="text-danger">*</span></label>
                                <select name="billing_cycle" class="form-select">
                                    <option value="monthly" @selected($plan->billing_cycle === 'monthly')>Monthly</option>
                                    <option value="yearly" @selected($plan->billing_cycle === 'yearly')>Yearly</option>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="2">{{ old('description', $plan->description) }}</textarea>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-3">
                                <label class="form-label">Max Users</label>
                                <input type="number" name="max_users" class="form-control" value="{{ old('max_users', $plan->max_users) }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Max Warehouses</label>
                                <input type="number" name="max_warehouses" class="form-control" value="{{ old('max_warehouses', $plan->max_warehouses) }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Max Products</label>
                                <input type="number" name="max_products" class="form-control" value="{{ old('max_products', $plan->max_products) }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Trial Days</label>
                                <input type="number" name="trial_days" class="form-control" value="{{ old('trial_days', $plan->trial_days) }}">
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label class="form-label">Sort Order</label>
                                <input type="number" name="sort_order" class="form-control" value="{{ old('sort_order', $plan->sort_order) }}">
                            </div>
                            <div class="col-md-4 d-flex align-items-end">
                                <label class="form-check">
                                    <input type="checkbox" name="is_active" value="1" class="form-check-input" @checked($plan->is_active)>
                                    <span class="form-check-label">Active</span>
                                </label>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Features</label>
                            @php
                                $sections = [
                                    'Core Features' => ['products', 'inventory', 'sales', 'purchases', 'invoices', 'dashboard', 'basic_reports', 'customers', 'suppliers', 'pos', 'sales_returns', 'purchase_returns'],
                                    'Professional Features' => ['stock_opname', 'accounting', 'crm', 'sales_pipeline', 'financial_reports', 'multi_currency', 'hr', 'cash_flow', 'quotations'],
                                    'Enterprise Features' => ['sales_forecasting', 'api_access', 'audit_compliance'],
                                ];
                            @endphp
                            @foreach($sections as $sectionLabel => $sectionKeys)
                            <div class="mb-3">
                                <div class="text-muted text-uppercase small fw-semibold mb-2" style="font-size: 0.7rem; letter-spacing: 0.08em;">{{ $sectionLabel }}</div>
                                <div class="row">
                                    @foreach($sectionKeys as $key)
                                        @if(isset($allFeatures[$key]))
                                        <div class="col-md-3 mb-1">
                                            <label class="form-check">
                                                <input type="checkbox" name="features[]" value="{{ $key }}" class="form-check-input" @checked(in_array($key, $plan->features ?? []))>
                                                <span class="form-check-label">{{ $allFeatures[$key] }}</span>
                                            </label>
                                        </div>
                                        @endif
                                    @endforeach
                                </div>
                            </div>
                            @endforeach
                        </div>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary px-4">Update Plan</button>
                            <a href="{{ route('superadmin.plans.index') }}" class="btn btn-ghost px-4">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endSection
