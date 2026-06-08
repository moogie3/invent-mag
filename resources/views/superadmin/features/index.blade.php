@extends('superadmin.layouts.base')
@section('title', 'Features')
@section('content')
<div class="page-wrapper">
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <div class="page-pretitle"><i class="ti ti-flag"></i> Configuration</div>
                    <h2 class="page-title">Features</h2>
                    <div class="text-muted mt-1">Toggle feature availability across plans. Checked = enabled.</div>
                </div>
            </div>
        </div>
    </div>
    <div class="page-body">
        <div class="container-xl">
            @if(session('success'))
            <div class="alert alert-success alert-dismissible mb-3" role="alert">
                <i class="ti ti-circle-check me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            @endif
            <form method="POST" action="{{ route('superadmin.features.update') }}">
                @csrf
                <div class="card shadow border-0 rounded-4">
                    <div class="table-responsive">
                        <table class="table table-vcenter card-table feature-toggle-table">
                            <thead>
                                <tr>
                                    <th style="min-width: 220px;">Feature</th>
                                    @foreach($plans as $plan)
                                    <th class="plan-header text-center" style="min-width: 130px;">
                                        <div class="fw-bold">{{ $plan->name }}</div>
                                        @if($plan->is_active)
                                        <span class="badge bg-success text-white mt-1">Active</span>
                                        @else
                                        <span class="badge bg-secondary text-white mt-1">Inactive</span>
                                        @endif
                                    </th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $sections = [
                                        'Core Features' => [
                                            'products', 'inventory', 'sales', 'purchases',
                                            'invoices', 'dashboard', 'basic_reports',
                                            'customers', 'suppliers', 'pos',
                                            'sales_returns', 'purchase_returns',
                                        ],
                                        'Professional Features' => [
                                            'stock_opname', 'accounting', 'crm',
                                            'sales_pipeline', 'financial_reports',
                                            'multi_currency', 'hr', 'cash_flow',
                                        ],
                                        'Enterprise Features' => [
                                            'sales_forecasting', 'api_access',
                                            'audit_compliance', 'custom_roles', 'dedicated_support',
                                        ],
                                    ];
                                    $sectionColors = [
                                        'Core Features' => 'bg-teal-lt',
                                        'Professional Features' => 'bg-blue-lt',
                                        'Enterprise Features' => 'bg-purple-lt',
                                    ];
                                @endphp

                                @foreach($sections as $sectionLabel => $sectionKeys)
                                {{-- Section header row --}}
                                <tr class="table-active">
                                    <td colspan="{{ count($plans) + 1 }}" class="py-2 px-3">
                                        <span class="fw-semibold text-uppercase" style="font-size: 0.7rem; letter-spacing: 0.08em; color: #6c757d;">
                                            {{ $sectionLabel }}
                                        </span>
                                    </td>
                                </tr>
                                @foreach($sectionKeys as $key)
                                    @if(isset($allFeatures[$key]))
                                    <tr>
                                        <td class="fw-medium ps-4">{{ $allFeatures[$key] }}</td>
                                        @foreach($plans as $plan)
                                        <td class="feature-cell">
                                            <label class="form-check form-switch mb-0 d-flex justify-content-center">
                                                <input type="checkbox" name="features[{{ $plan->id }}][]" value="{{ $key }}" class="form-check-input" @checked(in_array($key, $plan->features ?? []))>
                                            </label>
                                        </td>
                                        @endforeach
                                    </tr>
                                    @endif
                                @endforeach
                                @endforeach

                                {{-- Any remaining features not in sections --}}
                                @php
                                    $allSectionKeys = collect($sections)->flatten()->toArray();
                                    $remainingFeatures = array_diff_key($allFeatures, array_flip($allSectionKeys));
                                @endphp
                                @if(count($remainingFeatures) > 0)
                                <tr class="table-active">
                                    <td colspan="{{ count($plans) + 1 }}" class="py-2 px-3">
                                        <span class="fw-semibold text-uppercase" style="font-size: 0.7rem; letter-spacing: 0.08em; color: #6c757d;">Other</span>
                                    </td>
                                </tr>
                                @foreach($remainingFeatures as $key => $label)
                                <tr>
                                    <td class="fw-medium ps-4">{{ $label }}</td>
                                    @foreach($plans as $plan)
                                    <td class="feature-cell">
                                        <label class="form-check form-switch mb-0 d-flex justify-content-center">
                                            <input type="checkbox" name="features[{{ $plan->id }}][]" value="{{ $key }}" class="form-check-input" @checked(in_array($key, $plan->features ?? []))>
                                        </label>
                                    </td>
                                    @endforeach
                                </tr>
                                @endforeach
                                @endif
                            </tbody>
                        </table>
                    </div>
                    <div class="card-footer d-flex justify-content-end border-top">
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="ti ti-device-floppy me-1"></i>Save Feature Assignments
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endSection
