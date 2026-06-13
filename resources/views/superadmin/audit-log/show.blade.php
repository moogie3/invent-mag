@extends('superadmin.layouts.base')
@section('title', 'Audit Log Details')
@section('content')
<div class="page-wrapper">
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <div class="d-flex align-items-center">
                        <a href="{{ route('superadmin.audit-log.index') }}" class="btn btn-ghost btn-sm me-2">
                            <i class="ti ti-arrow-left"></i>
                        </a>
                        <div>
                            <div class="page-pretitle"><i class="ti ti-file-text"></i> System / Audit Log</div>
                            <h2 class="page-title mb-0">
                                Log Entry #{{ $log->id }}
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
                <div class="card-header">
                    <h3 class="card-title"><i class="ti ti-info-circle me-2 text-muted"></i>Activity Details</h3>
                </div>
                <div class="card-body">
                    <dl class="row">
                        <dt class="col-3">User</dt>
                        <dd class="col-9">{{ $log->user_name ?? 'System' }}</dd>

                        <dt class="col-3">User Role</dt>
                        <dd class="col-9">{{ $log->user_role ?? 'N/A' }}</dd>

                        <dt class="col-3">Module</dt>
                        <dd class="col-9">{{ $log->module }}</dd>

                        <dt class="col-3">Action</dt>
                        <dd class="col-9"><span class="badge {{ $log->action_badge_class }} text-white">{{ $log->action }}</span></dd>

                        <dt class="col-3">Entity Type</dt>
                        <dd class="col-9">{{ $log->entity_type ?? 'N/A' }}</dd>

                        <dt class="col-3">Entity Label</dt>
                        <dd class="col-9">{{ $log->entity_label ?? 'N/A' }}</dd>

                        <dt class="col-3">Description</dt>
                        <dd class="col-9">{{ $log->description }}</dd>

                        <dt class="col-3">IP Address</dt>
                        <dd class="col-9"><code>{{ $log->ip_address ?? 'N/A' }}</code></dd>

                        <dt class="col-3">Date</dt>
                        <dd class="col-9">{{ $log->created_at->format('M d, Y H:i:s') }}</dd>
                    </dl>
                </div>
            </div>

            @if($log->old_values || $log->new_values)
            <div class="row row-deck row-cards mt-3">
                @if($log->old_values)
                <div class="col-md-6">
                    <div class="card shadow border-0 rounded-4">
                        <div class="card-header">
                            <h3 class="card-title text-danger"><i class="ti ti-square-minus me-2"></i>Old Values</h3>
                        </div>
                        <div class="card-body">
                            <pre class="m-0" style="max-height: 300px; overflow: auto;">{{ json_encode($log->old_values, JSON_PRETTY_PRINT) }}</pre>
                        </div>
                    </div>
                </div>
                @endif
                @if($log->new_values)
                <div class="col-md-6">
                    <div class="card shadow border-0 rounded-4">
                        <div class="card-header">
                            <h3 class="card-title text-success"><i class="ti ti-square-plus me-2"></i>New Values</h3>
                        </div>
                        <div class="card-body">
                            <pre class="m-0" style="max-height: 300px; overflow: auto;">{{ json_encode($log->new_values, JSON_PRETTY_PRINT) }}</pre>
                        </div>
                    </div>
                </div>
                @endif
            </div>
            @endif
        </div>
    </div>
</div>
@endSection