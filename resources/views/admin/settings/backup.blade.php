@extends('admin.layouts.base')

@section('title', __('messages.backup'))

@section('content')
<div class="page-wrapper">
    <div class="page-body">
        <div class="{{ $containerClass ?? 'container-xl' }}">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body d-flex justify-content-between align-items-center py-3">
                    <h2 class="mb-0"><i class="ti ti-database me-2"></i>{{ __('messages.backup') }}</h2>
                    <a href="#" class="text-muted trigger-tutorial-modal" data-bs-toggle="tooltip" title="{{ __('messages.tutorial') }}">
                        <i class="ti ti-help-circle fs-3"></i>
                    </a>
                </div>
                <hr class="my-0">
                <div class="row g-0">
                    <div class="col-12 col-md-3 border-end">
                        @include('admin.layouts.menu')
                    </div>
                    <div class="col-12 col-md-9 d-flex flex-column">
                        <div class="card-body">
                            <!-- Tab Navigation -->
                            <ul class="nav nav-tabs card-header-tabs mb-4" id="backupTabs" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link active" id="backup-tab" data-bs-toggle="tab" data-bs-target="#backup-panel" type="button" role="tab" aria-controls="backup-panel" aria-selected="true">
                                        <i class="ti ti-refresh me-1"></i>{{ __('messages.backup') }}
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="password-tab" data-bs-toggle="tab" data-bs-target="#password-panel" type="button" role="tab" aria-controls="password-panel" aria-selected="false">
                                        <i class="ti ti-key me-1"></i>{{ __('messages.backup_password') }}
                                    </button>
                                </li>
                            </ul>

                            <!-- Tab Content -->
                            <div class="tab-content" id="backupTabsContent">
                                <!-- Tab 1: Backup & Restore -->
                                <div class="tab-pane fade show active" id="backup-panel" role="tabpanel" aria-labelledby="backup-tab">
                                    <div class="row g-4">
                                        <!-- Create Backup Section -->
                                        <div class="col-12">
                                            <div class="settings-section">
                                                <div class="settings-section-header">
                                                    <div class="settings-icon-wrapper">
                                                        <i class="ti ti-plus"></i>
                                                    </div>
                                                    <div class="settings-section-title">
                                                        <h3 class="mb-1">{{ __('messages.backup_create') }}</h3>
                                                        <p class="text-muted mb-0 small">
                                                            {{ __('messages.backup_create_desc') ?? 'Create a new backup of your data.' }}
                                                        </p>
                                                    </div>
                                                </div>
                                                <div class="settings-section-content">
                                                    <div class="row g-3 align-items-end">
                                                        <div class="col-md-6">
                                                            <select id="backupType" class="form-select" required>
                                                                <option value="db_only">{{ __('messages.backup_database_only') }}</option>
                                                                <option value="full">{{ __('messages.backup_database_files') }}</option>
                                                            </select>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <button type="button" class="btn btn-primary w-100" id="backupButton" data-url="{{ route('admin.setting.backup.run') }}">
                                                                <i class="ti ti-device-floppy me-1"></i>{{ __('messages.backup_create') }}
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Backup List Section -->
                                        <div class="col-12">
                                            <div class="settings-section">
                                                <div class="settings-section-header">
                                                    <div class="settings-icon-wrapper">
                                                        <i class="ti ti-file-zip"></i>
                                                    </div>
                                                    <div class="settings-section-title">
                                                        <h3 class="mb-1">{{ __('messages.backup_list') ?? 'Backup Files' }}</h3>
                                                        <p class="text-muted mb-0 small">
                                                            @if($latestBackup)
                                                                {{ __('messages.backup_last_backup') }}: {{ $latestBackup['last_modified']->format('F d, Y H:i') }}
                                                            @else
                                                                {{ __('messages.backup_no_backups') }}
                                                            @endif
                                                        </p>
                                                    </div>
                                                </div>
                                                <div class="settings-section-content">
                                                    @if(count($files) > 0)
                                                        <div class="table-responsive">
                                                            <table class="table table-hover mb-0">
                                                                <thead>
                                                                    <tr>
                                                                        <th>{{ __('messages.table_date') }}</th>
                                                                        <th>{{ __('messages.table_size') }}</th>
                                                                        <th>{{ __('messages.table_action') }}</th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody>
                                                                    @foreach($files as $file)
                                                                    <tr>
                                                                        <td>{{ $file['last_modified']->format('F d, Y H:i') }}</td>
                                                                        <td>{{ number_format($file['size'] / 1024 / 1024, 2) }} MB</td>
                                                                        <td>
                                                                            <div class="btn-list">
                                                                                <a href="{{ route('admin.setting.backup.download', $file['filename']) }}" class="btn btn-sm btn-icon btn-ghost-primary" title="{{ __('messages.backup_download') }}">
                                                                                    <i class="ti ti-download"></i>
                                                                                </a>
                                                                                <form action="{{ route('admin.setting.backup.delete', $file['filename']) }}" method="POST" class="d-inline">
                                                                                    @csrf
                                                                                    @method('DELETE')
                                                                                    <button type="submit" class="btn btn-sm btn-icon btn-ghost-danger" title="{{ __('messages.backup_delete') }}" onclick="return confirm('{{ __('messages.backup_delete_confirm') ?? 'Are you sure?' }}')">
                                                                                        <i class="ti ti-trash"></i>
                                                                                    </button>
                                                                                </form>
                                                                            </div>
                                                                        </td>
                                                                    </tr>
                                                                    @endforeach
                                                                </tbody>
                                                            </table>
                                                        </div>
                                                    @else
                                                        <div class="text-center py-4">
                                                            <i class="ti ti-file-off text-muted fs-1"></i>
                                                            <p class="text-muted mt-2">{{ __('messages.backup_no_backups') }}</p>
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>

<!-- Restore Section -->
                                        <div class="col-12">
                                            <div class="settings-section">
                                                <div class="settings-section-header">
                                                    <div class="settings-icon-wrapper">
                                                        <i class="ti ti-upload"></i>
                                                    </div>
                                                    <div class="settings-section-title">
                                                        <h3 class="mb-1">{{ __('messages.backup_restore') }}</h3>
                                                        <p class="text-muted mb-0 small">
                                                            {{ __('messages.backup_restore_desc') ?? 'Upload a backup file from your computer to restore.' }}
                                                        </p>
                                                    </div>
                                                </div>
                                                <div class="settings-section-content">
                                                    <form action="{{ route('admin.setting.backup.restore') }}" method="POST" enctype="multipart/form-data">
                                                        @csrf
                                                        <div class="row g-3 align-items-end">
                                                            <div class="col-md-5">
                                                                <label class="form-label">{{ __('messages.backup_file') }}</label>
                                                                <input type="file" name="backup_file" class="form-control" accept=".zip" required>
                                                                <small class="text-muted">{{ __('messages.backup_file_format') ?? 'Upload .zip backup file' }}</small>
                                                            </div>
                                                            <div class="col-md-4">
                                                                <label class="form-label">{{ __('messages.backup_restore_password') }}</label>
                                                                <input type="password" name="password" class="form-control">
                                                                <small class="text-muted">{{ !$tenant?->backup_password ? __('tutorial.backup_password_first_time') ?? 'Optional - leave blank if no password set' : '' }}</small>
                                                            </div>
                                                            <div class="col-md-3">
                                                                <button type="submit" class="btn btn-warning w-100">
                                                                    <i class="ti ti-upload me-1"></i>{{ __('messages.backup_restore_button') }}
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Tab 2: Password Settings -->
                                <div class="tab-pane fade" id="password-panel" role="tabpanel" aria-labelledby="password-tab">
                                    <div class="row g-4">
                                        <div class="col-12">
                                            <!-- Warning Banner -->
                                            <div class="alert alert-warning border-0 rounded-4 mb-4">
                                                <div class="d-flex align-items-center">
                                                    <i class="ti ti-alert-triangle fs-4 me-2"></i>
                                                    <div>
                                                        <strong>{{ __('messages.backup_warning_password_change') }}</strong>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Password Form -->
                                            <div class="settings-section">
                                                <form action="{{ route('admin.setting.backup.password') }}" method="POST" id="passwordForm">
                                                    @csrf
                                                    <div class="settings-section-content">
                                                        <div class="row g-3">
                                                            <div class="col-md-12">
                                                                <label class="form-label">{{ __('messages.backup_password_current') }}</label>
                                                                <input type="password" name="current_password" class="form-control" required>
                                                            </div>
                                                            <div class="col-md-12">
                                                                <label class="form-label">{{ __('messages.backup_password_new') }}</label>
                                                                <input type="password" name="new_password" class="form-control" required minlength="16">
                                                                <small class="text-muted">{{ __('messages.backup_password_min') }} - {{ __('messages.backup_password_strength') }}</small>
                                                            </div>
                                                            <div class="col-md-12">
                                                                <label class="form-label">{{ __('messages.backup_password_confirm') }}</label>
                                                                <input type="password" name="new_password_confirmation" class="form-control" required>
                                                            </div>
                                                            <div class="col-md-12">
                                                                <div class="form-check">
                                                                    <input type="checkbox" name="encryption_enabled" class="form-check-input" id="encryptionEnabled" value="1" {{ ($tenant->backup_encrypted ?? false) ? 'checked' : '' }}>
                                                                    <label class="form-check-label" for="encryptionEnabled">
                                                                        {{ __('messages.backup_encryption_enable') }}
                                                                    </label>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="card-footer bg-transparent mt-4">
                                                        <div class="btn-list justify-content-end">
                                                            <button type="submit" class="btn btn-primary">
                                                                <i class="ti ti-device-floppy me-1"></i>{{ __('messages.save') }}
                                                            </button>
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
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
@endsection

@section('page-footer')
@endsection
