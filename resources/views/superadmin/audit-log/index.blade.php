@extends('superadmin.layouts.base')
@section('title', 'Audit Log')
@section('content')
<div class="page-wrapper">
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <div class="page-pretitle"><i class="ti ti-file-text"></i> System</div>
                    <h2 class="page-title">Audit Log</h2>
                    <div class="text-muted mt-1">System-wide activity log.</div>
                </div>
            </div>
        </div>
    </div>
    <div class="page-body">
        <div class="container-xl">
            <div class="card shadow border-0 rounded-4">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-4">
                            <select name="module" class="form-select">
                                <option value="">All Modules</option>
                                @foreach($modules as $module)
                                <option value="{{ $module }}" @selected(request('module') === $module)>{{ $module }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select name="action" class="form-select">
                                <option value="">All Actions</option>
                                @foreach($actions as $action)
                                <option value="{{ $action }}" @selected(request('action') === $action)>{{ ucfirst($action) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <button type="submit" class="btn btn-primary"><i class="ti ti-filter me-1"></i>Filter</button>
                            <a href="{{ route('superadmin.audit-log.index') }}" class="btn btn-ghost">Reset</a>
                        </div>
                    </form>
                </div>
                <div class="table-responsive">
                    <table class="table table-vcenter card-table">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Module</th>
                                <th>Action</th>
                                <th>Description</th>
                                <th>Date</th>
                                <th class="w-1"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($logs as $log)
                            <tr>
                                <td>{{ $log->user_name ?? 'System' }}</td>
                                <td>{{ $log->module }}</td>
                                <td>
                                    <span class="badge {{ $log->action_badge_class }} text-white">{{ $log->action }}</span>
                                </td>
                                <td class="text-muted">{{ Str::limit($log->description, 60) }}</td>
                                <td class="text-muted">{{ $log->created_at->format('M d, Y H:i') }}</td>
                                <td>
                                    <a href="{{ route('superadmin.audit-log.show', $log) }}" class="btn btn-icon-only btn-ghost" title="View details">
                                        <i class="ti ti-eye"></i>
                                    </a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">No log entries found.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($logs->hasPages())
                <div class="card-footer d-flex justify-content-center border-top">
                    {{ $logs->links() }}
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endSection