@extends('superadmin.layouts.base')
@section('title', 'Backups')
@section('content')
<div class="page-wrapper">
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <div class="page-pretitle"><i class="ti ti-database"></i> System</div>
                    <h2 class="page-title">
                        Backups
                        <a href="#" class="ms-3 text-muted" data-bs-toggle="modal" data-bs-target="#how-it-works-modal" title="How It Works">
                            <i class="ti ti-help-circle fs-3"></i>
                        </a>
                    </h2>
                    <div class="text-muted mt-1">System and per-tenant backup management.</div>
                </div>
                <div class="col-auto ms-auto d-print-none">
                    <form method="POST" action="{{ route('superadmin.backups.create') }}" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-primary">
                            <i class="ti ti-plus me-1"></i>Create System Backup
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <div class="page-body">
        <div class="container-xl">
            <div class="row row-deck row-cards mb-4">
                <div class="col-sm-6 col-lg-3">
                    <div class="card shadow border-0 rounded-4 stat-card card-hover">
                        <div class="card-body text-center">
                            <div class="subheader">Total Backup Files</div>
                            <div class="h1 mb-0">{{ $stats['total_files'] }}</div>
                        </div>
                        <i class="ti ti-files stat-icon"></i>
                    </div>
                </div>
                <div class="col-sm-6 col-lg-3">
                    <div class="card shadow border-0 rounded-4 stat-card card-hover">
                        <div class="card-body text-center">
                            <div class="subheader">System / Tenant</div>
                            <div class="h1 mb-0">{{ $stats['system_count'] }} / {{ $stats['tenant_count'] }}</div>
                        </div>
                        <i class="ti ti-database stat-icon text-primary"></i>
                    </div>
                </div>
                <div class="col-sm-6 col-lg-3">
                    <div class="card shadow border-0 rounded-4 stat-card card-hover">
                        <div class="card-body text-center">
                            <div class="subheader">Total Storage Used</div>
                            <div class="h1 mb-0">
                                @if($stats['total_size'] > 1073741824)
                                    {{ number_format($stats['total_size'] / 1073741824, 2) }} GB
                                @elseif($stats['total_size'] > 1048576)
                                    {{ number_format($stats['total_size'] / 1048576, 2) }} MB
                                @else
                                    {{ number_format($stats['total_size'] / 1024, 2) }} KB
                                @endif
                            </div>
                        </div>
                        <i class="ti ti-cloud stat-icon text-warning"></i>
                    </div>
                </div>
                <div class="col-sm-6 col-lg-3">
                    <div class="card shadow border-0 rounded-4 stat-card card-hover">
                        <div class="card-body text-center">
                            <div class="subheader">Latest Backup</div>
                            <div class="h5 mb-0 text-truncate" title="{{ $stats['latest'] }}">{{ $stats['latest'] !== 'None' ? \Illuminate\Support\Str::limit($stats['latest'], 20) : 'None' }}</div>
                        </div>
                        <i class="ti ti-clock stat-icon text-success"></i>
                    </div>
                </div>
            </div>

            <div class="card shadow border-0 rounded-4 mb-4">
                <div class="card-header">
                    <ul class="nav nav-tabs card-header-tabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="system-tab" data-bs-toggle="tab" data-bs-target="#system-backups" type="button" role="tab">
                                <i class="ti ti-server me-1"></i>System Backups
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="tenant-tab" data-bs-toggle="tab" data-bs-target="#tenant-backups" type="button" role="tab">
                                <i class="ti ti-building-community me-1"></i>Tenant Backups
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="create-tab" data-bs-toggle="tab" data-bs-target="#create-backup" type="button" role="tab">
                                <i class="ti ti-plus me-1"></i>Create Tenant Backup
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="restore-tab" data-bs-toggle="tab" data-bs-target="#restore-backup" type="button" role="tab">
                                <i class="ti ti-rotate-clockwise me-1"></i>Restore Tenant
                            </button>
                        </li>
                    </ul>
                </div>
                <div class="card-body">
                    <div class="tab-content">
                        <div class="tab-pane active" id="system-backups" role="tabpanel">
                            <div class="table-responsive">
                                <table class="table table-vcenter card-table">
                                    <thead>
                                        <tr>
                                            <th>File Name</th>
                                            <th>Size</th>
                                            <th>Last Modified</th>
                                            <th class="w-1"></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($systemBackups as $backup)
                                        <tr>
                                            <td><i class="ti ti-file-zip me-2 text-muted"></i>{{ $backup['name'] }}</td>
                                            <td>{{ number_format($backup['size'] / 1024, 2) }} KB</td>
                                            <td class="text-muted">{{ \Carbon\Carbon::createFromTimestamp($backup['modified'])->format('M d, Y H:i') }}</td>
                                            <td>
                                                <div class="dropdown">
                                                    <button class="btn btn-icon-only btn-ghost" data-bs-toggle="dropdown">
                                                        <i class="ti ti-dots-vertical"></i>
                                                    </button>
                                                    <div class="dropdown-menu dropdown-menu-end">
                                                        <a href="{{ route('superadmin.backups.download', $backup['name']) }}" class="dropdown-item">
                                                            <i class="ti ti-download me-2"></i>Download
                                                        </a>
                                                        <form method="POST" action="{{ route('superadmin.backups.destroy', $backup['name']) }}" onsubmit="return confirm('Delete backup {{ $backup['name'] }}?')">
                                                            @csrf @method('DELETE')
                                                            <button type="submit" class="dropdown-item text-danger">
                                                                <i class="ti ti-trash me-2"></i>Delete
                                                            </button>
                                                        </form>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                        @empty
                                        <tr>
                                            <td colspan="4" class="text-center py-4 text-muted">No system backups found.</td>
                                        </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="tab-pane" id="tenant-backups" role="tabpanel">
                            <div class="table-responsive">
                                <table class="table table-vcenter card-table">
                                    <thead>
                                        <tr>
                                            <th>File Name</th>
                                            <th>Tenant</th>
                                            <th>Size</th>
                                            <th>Last Modified</th>
                                            <th class="w-1"></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($tenantBackups as $backup)
                                        <tr>
                                            <td><i class="ti ti-file-zip me-2 text-muted"></i>{{ $backup['filename'] }}</td>
                                            <td>
                                                <span class="badge bg-info-lt text-info">{{ $backup['tenant_name'] }}</span>
                                            </td>
                                            <td>{{ number_format($backup['size'] / 1024, 2) }} KB</td>
                                            <td class="text-muted">{{ $backup['last_modified']->format('M d, Y H:i') }}</td>
                                            <td>
                                                <div class="dropdown">
                                                    <button class="btn btn-icon-only btn-ghost" data-bs-toggle="dropdown">
                                                        <i class="ti ti-dots-vertical"></i>
                                                    </button>
                                                    <div class="dropdown-menu dropdown-menu-end">
                                                        <a href="{{ route('superadmin.backups.download', $backup['filename']) }}" class="dropdown-item">
                                                            <i class="ti ti-download me-2"></i>Download
                                                        </a>
                                                        <form method="POST" action="{{ route('superadmin.backups.destroy', $backup['filename']) }}" onsubmit="return confirm('Delete backup {{ $backup['filename'] }}?')">
                                                            @csrf @method('DELETE')
                                                            <button type="submit" class="dropdown-item text-danger">
                                                                <i class="ti ti-trash me-2"></i>Delete
                                                            </button>
                                                        </form>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                        @empty
                                        <tr>
                                            <td colspan="5" class="text-center py-4 text-muted">No tenant backups found.</td>
                                        </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="tab-pane" id="create-backup" role="tabpanel">
                            <form method="POST" action="{{ route('superadmin.backups.create-tenant') }}">
                                @csrf
                                <div class="row g-3">
                                    <div class="col-md-5">
                                        <label class="form-label">Tenant</label>
                                        <select name="tenant_id" class="form-select" required>
                                            <option value="">-- Select Tenant --</option>
                                            @foreach($tenants as $t)
                                            <option value="{{ $t->id }}">
                                                {{ $t->name }} ({{ $t->domain }}) {{ $t->plan_status === 'terminated' ? '[Terminated]' : '' }}
                                            </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Backup Type</label>
                                        <select name="type" class="form-select" required>
                                            <option value="db_only">Database Only</option>
                                            <option value="full">Full (DB + Files)</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3 d-flex align-items-end">
                                        <button type="submit" class="btn btn-primary w-100">
                                            <i class="ti ti-plus me-1"></i>Create Backup
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>

                        <div class="tab-pane" id="restore-backup" role="tabpanel">
                            <form method="POST" action="{{ route('superadmin.backups.restore') }}" enctype="multipart/form-data" id="restore-form" onsubmit="return confirm('WARNING: This will REPLACE all tenant data with the backup. This action cannot be undone. Continue?')">
                                @csrf
                                <div class="row g-3">
                                    <div class="col-md-5">
                                        <label class="form-label">Tenant to Restore</label>
                                        <select name="tenant_id" class="form-select" required>
                                            <option value="">-- Select Tenant --</option>
                                            @foreach($tenants as $t)
                                            <option value="{{ $t->id }}">
                                                {{ $t->name }} ({{ $t->domain }})
                                                @if($t->backup_encrypted)
                                                    <i class="ti ti-lock ms-1 text-warning" title="Encrypted backups"></i>
                                                @endif
                                            </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Backup File (ZIP)</label>
                                        <input type="file" name="backup_file" class="form-control" accept=".zip" required>
                                    </div>
                                    <div class="col-md-3 d-flex align-items-end">
                                        <button type="submit" class="btn btn-warning w-100" id="restore-btn">
                                            <span class="btn-text"><i class="ti ti-rotate-clockwise me-1"></i>Restore</span>
                                            <span class="btn-loading d-none"><span class="spinner-border spinner-border-sm me-1"></span> Restoring...</span>
                                        </button>
                                    </div>
                                </div>
                                <div class="row g-3 mt-2">
                                    <div class="col-md-5">
                                        <label class="form-label">Backup Password <span class="text-muted small">(required if encrypted)</span></label>
                                        <input type="password" name="password" class="form-control" placeholder="Leave blank if not encrypted">
                                    </div>
                                </div>
                                <div class="alert alert-warning mt-3 mb-0">
                                    <i class="ti ti-alert-triangle me-1"></i>
                                    <strong>Warning:</strong> Restoring will overwrite all existing data for the selected tenant. Make sure you have a current backup before proceeding.
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.getElementById('restore-form')?.addEventListener('submit', function(e) {
    if (e.defaultPrevented) return;
    const btn = document.getElementById('restore-btn');
    if (btn) {
        btn.disabled = true;
        btn.querySelector('.btn-text').classList.add('d-none');
        btn.querySelector('.btn-loading').classList.remove('d-none');
    }
});
</script>
@endpush
@endSection
