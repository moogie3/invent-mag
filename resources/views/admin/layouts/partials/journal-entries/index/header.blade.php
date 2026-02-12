<div class="page-header d-print-none mt-4">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                <div class="page-pretitle">{{ __('messages.accounting') }}</div>
                <h2 class="page-title">
                    <i class="ti ti-notebook me-2"></i>{{ __('messages.manual_journal_entries') }}
                </h2>
                <div class="text-muted mt-1">
                    {{ __('messages.manual_journal_entries_description') }}
                </div>
            </div>
            <div class="col-auto ms-auto d-print-none">
                <a href="{{ route('admin.accounting.journal-entries.create') }}" class="btn btn-primary">
                    <i class="ti ti-plus me-2"></i>{{ __('messages.create_journal_entry') }}
                </a>
            </div>
        </div>
    </div>
</div>
