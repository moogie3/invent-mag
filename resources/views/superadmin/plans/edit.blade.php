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
                            <h2 class="page-title mb-0">Edit Plan: {{ $plan->name }}</h2>
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
                                    <input type="checkbox" name="is_active" class="form-check-input" @checked($plan->is_active)>
                                    <span class="form-check-label">Active</span>
                                </label>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Features</label>
                            <div class="row">
                                @php
                                    $featureOptions = [
                                        'purchase_order' => 'Purchase Order',
                                        'purchase_return' => 'Purchase Return',
                                        'sales_order' => 'Sales Order',
                                        'sales_return' => 'Sales Return',
                                        'pos' => 'Point of Sale',
                                        'product' => 'Product Management',
                                        'customer' => 'Customer Management',
                                        'supplier' => 'Supplier Management',
                                        'warehouse' => 'Warehouse Management',
                                        'stock_opname' => 'Stock Opname',
                                        'stock_transfer' => 'Stock Transfer',
                                        'stock_adjustment' => 'Stock Adjustment',
                                        'category' => 'Category Management',
                                        'unit' => 'Unit Management',
                                        'tax' => 'Tax Management',
                                        'multi_currency' => 'Multi-Currency',
                                        'financial_reports' => 'Financial Reports',
                                        'crm' => 'CRM & Sales Pipeline',
                                        'srm' => 'Supplier Relationship Mgmt',
                                        'hr' => 'HR & Payroll',
                                        'accounting' => 'Accounting',
                                        'audit_compliance' => 'Audit & Compliance',
                                        'backup' => 'Backup & Restore',
                                    ];
                                @endphp
                                @foreach($featureOptions as $key => $label)
                                <div class="col-md-3">
                                    <label class="form-check">
                                        <input type="checkbox" name="features[]" value="{{ $key }}" class="form-check-input" @checked(in_array($key, $plan->features ?? []))>
                                        <span class="form-check-label">{{ $label }}</span>
                                    </label>
                                </div>
                                @endforeach
                            </div>
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