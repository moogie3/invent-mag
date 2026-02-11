@extends('admin.layouts.base')

@section('title',__('messages.manual_journal_entries'))

@section('content')
<div class="container-xl">
    <div class="page-header d-print-none mt-4">
        <div class="row g-2 align-items-center">
            <div class="col">
                <div class="page-pretitle">{{ __('messages.accounting') }}</div>
                <h2 class="page-title">
                    <span class="nav-link-icon d-md-none d-lg-inline-block">
                        <i class="ti ti-notebook"></i>
                    </span>
                    {{ __('messages.manual_journal_entries') }}
                </h2>
                <div class="text-muted mt-1">
                    {{ __('messages.manual_journal_entries_description') }}
                </div>
            </div>
            <div class="col-auto ms-auto d-print-none">
                <a href="{{ route('admin.accounting.journal-entries.create') }}" class="btn btn-primary">
                    <i class="ti ti-plus"></i>{{ __('messages.create_journal_entry') }}
                </a>
            </div>
        </div>
    </div>

    <div class="page-body mt-4">
        <div class="card">
            <div class="card-header">
                <ul class="nav nav-tabs card-header-tabs" data-bs-toggle="tabs">
                    <li class="nav-item">
                        <a href="#draft-entries" class="nav-link {{ $tab == 'draft' ? 'active' : '' }}" data-bs-toggle="tab">
                            <i class="ti ti-edit me-1"></i>
                            {{ __('messages.draft_entries') }}
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="#posted-entries" class="nav-link {{ $tab == 'posted' ? 'active' : '' }}" data-bs-toggle="tab">
                            <i class="ti ti-check me-1"></i>
                            {{ __('messages.posted_entries') }}
                        </a>
                    </li>
                </ul>
            </div>
            <div class="card-body">
                <div class="tab-content">
                    <!-- Draft Entries Tab -->
                    <div class="tab-pane {{ $tab == 'draft' ? 'active' : '' }}" id="draft-entries">
                        @include('admin.accounting.journal-entries.partials.entry-list', ['entries' => $draftEntries])
                    </div>
                    <!-- Posted Entries Tab -->
                    <div class="tab-pane {{ $tab == 'posted' ? 'active' : '' }}" id="posted-entries">
                        @include('admin.accounting.journal-entries.partials.entry-list', ['entries' => $postedEntries])
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
