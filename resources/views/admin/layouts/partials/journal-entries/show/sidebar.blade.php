<div class="card">
    <div class="card-header">
        <h4 class="card-title">{{ __('messages.audit_log') }}</h4>
    </div>
    <div class="card-body">
        @if($auditLogs->isEmpty())
            <p class="text-muted">{{ __('messages.no_audit_logs') }}</p>
        @else
            <div class="timeline">
                @foreach($auditLogs as $log)
                    <div class="timeline-item">
                        <div class="timeline-badge bg-{{ $log->action === 'create' ? 'success' : ($log->action === 'delete' ? 'danger' : 'primary') }}"></div>
                        <div class="timeline-content">
                            <div class="text-muted small">{{ $log->created_at->format('d/m/Y H:i') }}</div>
                            <div>{{ $log->description }}</div>
                            <div class="d-flex align-items-center gap-2 mt-1">
                                <span class="text-muted small">by</span>
                                <span class="text-body fw-medium">{{ $log->user_name }}</span>
                                @if($log->user && $log->user->roles->isNotEmpty())
                                    @foreach($log->user->roles as $role)
                                        @if($role->name === 'superuser')
                                            <span class="badge bg-purple-lt">{{ $role->name }}</span>
                                        @elseif($role->name === 'staff')
                                            <span class="badge bg-blue-lt">{{ $role->name }}</span>
                                        @else
                                            <span class="badge bg-green-lt">{{ $role->name }}</span>
                                        @endif
                                    @endforeach
                                @else
                                    <span class="badge bg-secondary">{{ __('messages.no_role') }}</span>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>

@if($entry->reversedEntry || $entry->reversingEntry)
    <div class="card mt-3">
        <div class="card-header">
            <h4 class="card-title">{{ __('messages.related_entries') }}</h4>
        </div>
        <div class="card-body">
            @if($entry->reversedEntry)
                <div class="mb-2">
                    <strong>{{ __('messages.reversed_entry') }}:</strong>
                    <a href="{{ route('admin.accounting.journal-entries.show', $entry->reversedEntry) }}">
                        #{{ $entry->reversedEntry->id }} - {{ $entry->reversedEntry->description }}
                    </a>
                </div>
            @endif
            @if($entry->reversingEntry)
                <div>
                    <strong>{{ __('messages.reversing_entry') }}:</strong>
                    <a href="{{ route('admin.accounting.journal-entries.show', $entry->reversingEntry) }}">
                        #{{ $entry->reversingEntry->id }} - {{ $entry->reversingEntry->description }}
                    </a>
                </div>
            @endif
        </div>
    </div>
@endif
