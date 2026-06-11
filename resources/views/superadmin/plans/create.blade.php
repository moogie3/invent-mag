@extends('superadmin.layouts.base')
@section('title', 'Create Plan')
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
                            <h2 class="page-title mb-0">Create Plan</h2>
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
                    <form method="POST" action="{{ route('superadmin.plans.store') }}">
                        @csrf
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label class="form-label">Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Slug <span class="text-danger">*</span></label>
                                <input type="text" name="slug" class="form-control" value="{{ old('slug') }}" required>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Price <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" name="price" class="form-control" value="{{ old('price') }}" required>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Billing Cycle <span class="text-danger">*</span></label>
                                <select name="billing_cycle" class="form-select">
                                    <option value="monthly">Monthly</option>
                                    <option value="yearly">Yearly</option>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="2">{{ old('description') }}</textarea>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-3">
                                <label class="form-label">Max Users</label>
                                <input type="number" name="max_users" class="form-control" value="{{ old('max_users', -1) }}">
                                <small class="text-muted">-1 for unlimited</small>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Max Warehouses</label>
                                <input type="number" name="max_warehouses" class="form-control" value="{{ old('max_warehouses', -1) }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Max Products</label>
                                <input type="number" name="max_products" class="form-control" value="{{ old('max_products', -1) }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Trial Days</label>
                                <input type="number" name="trial_days" class="form-control" value="{{ old('trial_days', 0) }}">
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label class="form-label">Sort Order</label>
                                <input type="number" name="sort_order" class="form-control" value="{{ old('sort_order', 0) }}">
                            </div>
                            <div class="col-md-4 d-flex align-items-end">
                                <label class="form-check">
                                    <input type="checkbox" name="is_active" class="form-check-input" checked>
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
                                    'Enterprise Features' => ['sales_forecasting', 'api_access', 'audit_compliance', 'custom_roles', 'dedicated_support'],
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
                                                <input type="checkbox" name="features[]" value="{{ $key }}" class="form-check-input">
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
                            <button type="submit" class="btn btn-primary px-4">Create Plan</button>
                            <a href="{{ route('superadmin.plans.index') }}" class="btn btn-ghost px-4">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endSection
