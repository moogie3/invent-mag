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
            <form method="POST" action="{{ route('superadmin.features.update') }}">
                @csrf
                <div class="card shadow border-0 rounded-4">
                    <div class="table-responsive">
                        <table class="table table-vcenter card-table feature-toggle-table">
                            <thead>
                                <tr>
                                    <th style="min-width: 200px;">Feature</th>
                                    @foreach($plans as $plan)
                                    <th class="plan-header text-center">
                                        <div>{{ $plan->name }}</div>
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
                                @foreach($allFeatures as $key => $label)
                                <tr>
                                    <td class="fw-medium">{{ $label }}</td>
                                    @foreach($plans as $plan)
                                    <td class="feature-cell">
                                        <label class="form-check form-switch mb-0 d-flex justify-content-center">
                                            <input type="checkbox" name="features[{{ $plan->id }}][]" value="{{ $key }}" class="form-check-input" @checked(in_array($key, $plan->features ?? []))>
                                        </label>
                                    </td>
                                    @endforeach
                                </tr>
                                @endforeach
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