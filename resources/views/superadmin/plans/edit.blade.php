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
                                <label class="form-label" for="name">Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $plan->name) }}" required>
                                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="slug">Slug <span class="text-danger">*</span></label>
                                <input type="text" name="slug" id="slug" class="form-control @error('slug') is-invalid @enderror" value="{{ old('slug', $plan->slug) }}" required>
                                @error('slug')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-2">
                                <label class="form-label" for="price">Price <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" name="price" id="price" class="form-control @error('price') is-invalid @enderror" value="{{ old('price', $plan->price) }}" required>
                                @error('price')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-2">
                                <label class="form-label" for="currency_code">Currency</label>
                                <select name="currency_code" id="currency_code" class="form-select @error('currency_code') is-invalid @enderror">
                                    @foreach ($predefinedCurrencies as $currency)
                                        <option value="{{ $currency['code'] }}" {{ old('currency_code', $plan->currency_code ?? 'USD') == $currency['code'] ? 'selected' : '' }}>
                                            {{ $currency['code'] }} ({{ $currency['symbol'] }})
                                        </option>
                                    @endforeach
                                </select>
                                @error('currency_code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-2">
                                <label class="form-label" for="billing_cycle">Billing Cycle <span class="text-danger">*</span></label>
                                <select name="billing_cycle" id="billing_cycle" class="form-select @error('billing_cycle') is-invalid @enderror">
                                    <option value="monthly" {{ old('billing_cycle', $plan->billing_cycle) == 'monthly' ? 'selected' : '' }}>Monthly</option>
                                    <option value="yearly" {{ old('billing_cycle', $plan->billing_cycle) == 'yearly' ? 'selected' : '' }}>Yearly</option>
                                </select>
                                @error('billing_cycle')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="description">Description</label>
                            <textarea name="description" id="description" class="form-control @error('description') is-invalid @enderror" rows="2">{{ old('description', $plan->description) }}</textarea>
                            @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-3">
                                <label class="form-label" for="max_users">Max Users</label>
                                <input type="number" name="max_users" id="max_users" class="form-control @error('max_users') is-invalid @enderror" value="{{ old('max_users', $plan->max_users) }}">
                                @error('max_users')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-3">
                                <label class="form-label" for="max_warehouses">Max Warehouses</label>
                                <input type="number" name="max_warehouses" id="max_warehouses" class="form-control @error('max_warehouses') is-invalid @enderror" value="{{ old('max_warehouses', $plan->max_warehouses) }}">
                                @error('max_warehouses')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-3">
                                <label class="form-label" for="max_products">Max Products</label>
                                <input type="number" name="max_products" id="max_products" class="form-control @error('max_products') is-invalid @enderror" value="{{ old('max_products', $plan->max_products) }}">
                                @error('max_products')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-3">
                                <label class="form-label" for="trial_days">Trial Days</label>
                                <input type="number" name="trial_days" id="trial_days" class="form-control @error('trial_days') is-invalid @enderror" value="{{ old('trial_days', $plan->trial_days) }}">
                                @error('trial_days')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label class="form-label" for="sort_order">Sort Order</label>
                                <input type="number" name="sort_order" id="sort_order" class="form-control @error('sort_order') is-invalid @enderror" value="{{ old('sort_order', $plan->sort_order) }}">
                                @error('sort_order')<div class="invalid-feedback">{{ $message }}</div>@enderror
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
