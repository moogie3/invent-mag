<!-- Active Filters Display -->
@if (request()->hasAny(['type', 'status', 'date_range', 'search']))
    <div class="card-body border-bottom py-2">
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <span class="text-muted small">Active filters:</span>
            @if (request('type'))
                <span class="badge bg-primary-lt">
                    Type: {{ ucfirst(request('type')) }}
                    <a href="{{ request()->fullUrlWithQuery(['type' => null]) }}" class="btn-close ms-1"
                        style="font-size: 0.75em;"></a>
                </span>
            @endif
            @if (request('status'))
                <span class="badge bg-info-lt">
                    Status: {{ request('status') }}
                    <a href="{{ request()->fullUrlWithQuery(['status' => null]) }}" class="btn-close ms-1"
                        style="font-size: 0.75em;"></a>
                </span>
            @endif
            @if (request('date_range') && request('date_range') != 'all')
                <span class="badge bg-warning-lt">
                    Period: {{ ucfirst(str_replace('_', ' ', request('date_range'))) }}
                    <a href="{{ request()->fullUrlWithQuery(['date_range' => null, 'start_date' => null, 'end_date' => null]) }}"
                        class="btn-close ms-1" style="font-size: 0.75em;"></a>
                </span>
            @endif
            @if (request('search'))
                <span class="badge bg-secondary-lt">
                    Search: "{{ request('search') }}"
                    <a href="{{ request()->fullUrlWithQuery(['search' => null]) }}" class="btn-close ms-1"
                        style="font-size: 0.75em;"></a>
                </span>
            @endif
            <a href="{{ route('admin.transactions') }}" class="btn btn-sm btn-outline-secondary ms-2">
                Clear All
            </a>
        </div>
    </div>
@endif
