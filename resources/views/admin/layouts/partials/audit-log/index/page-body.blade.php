<div class="page-body">
    <div class="{{ $containerClass ?? 'container-xl' }}">

        <div class="row row-deck row-cards mb-3">
            <div class="col-sm-6 col-lg-3">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="subheader">{{ __('messages.today') }}</div>
                        </div>
                        <div class="h1 mb-0">{{ number_format($today) }}</div>
                        <div class="text-muted">{{ __('messages.events_today') }}</div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card">
                    <div class="card-body">
                        <div class="subheader">{{ __('messages.this_week') }}</div>
                        <div class="h1 mb-0">{{ number_format($thisWeek) }}</div>
                        <div class="text-muted">{{ __('messages.events_this_week') }}</div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card">
                    <div class="card-body">
                        <div class="subheader">{{ __('messages.this_month') }}</div>
                        <div class="h1 mb-0">{{ number_format($thisMonth) }}</div>
                        <div class="text-muted">{{ __('messages.events_this_month') }}</div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card">
                    <div class="card-body">
                        <div class="subheader">{{ __('messages.total') }}</div>
                        <div class="h1 mb-0">{{ number_format($totalEvents) }}</div>
                        <div class="text-muted">{{ __('messages.total_events_logged') }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-body">
                <form method="GET" action="{{ route('admin.audit-log.index') }}" class="row g-2 align-items-end">
                    <div class="col-12 col-md-3">
                        <label class="form-label">{{ __('messages.search') }}</label>
                        <input type="text" name="search" class="form-control"
                               placeholder="{{ __('messages.search_logs_placeholder') }}"
                               value="{{ $filters['search'] ?? '' }}">
                    </div>

                    <div class="col-6 col-md-2">
                        <label class="form-label">{{ __('messages.user') }}</label>
                        <select name="user_id" class="form-select">
                            <option value="">{{ __('messages.all_users') }}</option>
                            @foreach ($users as $u)
                                <option value="{{ $u->user_id }}"
                                    {{ ($filters['user_id'] ?? '') == $u->user_id ? 'selected' : '' }}>
                                    {{ $u->user_name }}
                                    @if ($u->user_role)
                                        ({{ $u->user_role }})
                                    @endif
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-6 col-md-2">
                        <label class="form-label">{{ __('messages.module') }}</label>
                        <select name="module" class="form-select">
                            <option value="">{{ __('messages.all_modules') }}</option>
                            @foreach ($modules as $mod)
                                <option value="{{ $mod }}"
                                    {{ ($filters['module'] ?? '') === $mod ? 'selected' : '' }}>
                                    {{ $mod }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-6 col-md-2">
                        <label class="form-label">{{ __('messages.action') }}</label>
                        <select name="action" class="form-select">
                            <option value="">{{ __('messages.all_actions') }}</option>
                            @foreach ($actions as $act)
                                <option value="{{ $act }}"
                                    {{ ($filters['action'] ?? '') === $act ? 'selected' : '' }}>
                                    {{ ucfirst($act) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-6 col-md-2">
                        <label class="form-label">{{ __('messages.date_from') }}</label>
                        <input type="date" name="date_from" class="form-control"
                               value="{{ $filters['date_from'] ?? '' }}">
                    </div>

                    <div class="col-6 col-md-2">
                        <label class="form-label">{{ __('messages.date_to') }}</label>
                        <input type="date" name="date_to" class="form-control"
                               value="{{ $filters['date_to'] ?? '' }}">
                    </div>

                    <div class="col-6 col-md-1">
                        <label class="form-label">{{ __('messages.per_page') }}</label>
                        <select name="entries" class="form-select">
                            @foreach ([10, 25, 50, 100] as $n)
                                <option value="{{ $n }}" {{ $perPage == $n ? 'selected' : '' }}>{{ $n }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-12 col-md-auto d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="ti ti-filter me-1"></i>{{ __('messages.filter') }}
                        </button>
                        <a href="{{ route('admin.audit-log.index') }}" class="btn btn-secondary">
                            <i class="ti ti-x me-1"></i>{{ __('messages.clear') }}
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-header">
                <h3 class="card-title">{{ __('messages.activity_log') }}</h3>
                <div class="ms-auto text-muted small">
                    {{ __('messages.showing_of_total', ['count' => $logs->count(), 'total' => $logs->total()]) }}
                </div>
            </div>

            <div class="table-responsive">
                <table class="table card-table table-vcenter text-nowrap">
                    <thead style="font-size: large">
                        <tr>
                            <th class="fs-5 py-3">{{ __('messages.date') }}</th>
                            <th class="fs-5 py-3">{{ __('messages.user') }}</th>
                            <th class="fs-5 py-3">{{ __('messages.role') }}</th>
                            <th class="fs-5 py-3">{{ __('messages.module') }}</th>
                            <th class="fs-5 py-3">{{ __('messages.action') }}</th>
                            <th class="fs-5 py-3">{{ __('messages.description') }}</th>
                            <th class="fs-5 py-3">{{ __('messages.ip_address') }}</th>
                            <th class="fs-5 py-3">{{ __('messages.table_details') }}</th>
                        </tr>
                    </thead>
                    <tbody class="table-tbody">
                        @forelse ($logs as $log)
                            <tr>
                                <td class="text-muted" style="min-width: 150px;">
                                    {{ $log->created_at->translatedFormat('d M Y') }}<br>
                                    <small>{{ $log->created_at->format('H:i:s') }}</small>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="avatar avatar-xs"
                                            style="background-image: url('https://ui-avatars.com/api/?name={{ urlencode($log->user_name) }}&background=random&size=32');"></span>
                                        <span>{{ $log->user_name }}</span>
                                    </div>
                                </td>
                                <td>
                                    @if ($log->user_role)
                                        <span class="badge bg-secondary-lt">{{ $log->user_role }}</span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge bg-azure-lt">{{ $log->module }}</span>
                                </td>
                                <td>
                                    <span class="badge {{ $log->action_badge_class }}">
                                        {{ ucfirst($log->action) }}
                                    </span>
                                </td>
                                <td class="text-wrap" style="min-width: 280px; max-width: 400px; white-space: normal;">
                                    {{ $log->description }}
                                </td>
                                <td class="text-muted small">{{ $log->ip_address ?? '-' }}</td>
                                <td>
                                    @if ($log->old_values || $log->new_values)
                                        <button type="button"
                                                class="btn btn-sm btn-ghost-secondary btn-view-detail"
                                                data-log-id="{{ $log->id }}"
                                                title="{{ __('messages.view_details') }}">
                                            <i class="ti ti-eye"></i>
                                        </button>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-5 text-muted">
                                    <div class="empty">
                                        <div class="empty-img">
                                            <i class="ti ti-shield-check" style="font-size: 3rem;"></i>
                                        </div>
                                        <p class="empty-title">{{ __('messages.no_activity_logs_found') }}</p>
                                        <p class="empty-subtitle text-muted">
                                            {{ __('messages.no_activity_logs_found_message') }}
                                        </p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($logs->hasPages())
                <div class="card-footer d-flex align-items-center">
                    <p class="m-0 text-secondary">
                        {{ __('messages.pagination_showing_entries', [
                            'first'  => $logs->firstItem(),
                            'last'   => $logs->lastItem(),
                            'total'  => $logs->total(),
                        ]) }}
                    </p>
                    <div class="ms-auto">
                        {{ $logs->appends(request()->query())->links('vendor.pagination.tabler') }}
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
