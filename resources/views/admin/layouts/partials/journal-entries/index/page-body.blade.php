<div class="page-body mt-4">
    <div class="{{ $containerClass ?? "container-xl" }}">
        <div class="card">
            <div class="card-header">
                <ul class="nav nav-tabs card-header-tabs" data-bs-toggle="tabs">
                    <li class="nav-item">
                        <a href="#draft-entries" class="nav-link {{ $tab == 'draft' ? 'active' : '' }}" data-bs-toggle="tab">
                            <i class="ti ti-edit me-1"></i>{{ __('messages.draft_entries') }}
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="#posted-entries" class="nav-link {{ $tab == 'posted' ? 'active' : '' }}" data-bs-toggle="tab">
                            <i class="ti ti-check me-1"></i>{{ __('messages.posted_entries') }}
                        </a>
                    </li>
                </ul>
            </div>
            <div class="card-body">
                <div class="tab-content">
                    <!-- Draft Entries Tab -->
                    <div class="tab-pane {{ $tab == 'draft' ? 'active' : '' }}" id="draft-entries">
                        @include('admin.layouts.partials.journal-entries.index.entry-list', ['entries' => $draftEntries])
                    </div>
                    <!-- Posted Entries Tab -->
                    <div class="tab-pane {{ $tab == 'posted' ? 'active' : '' }}" id="posted-entries">
                        @include('admin.layouts.partials.journal-entries.index.entry-list', ['entries' => $postedEntries])
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
