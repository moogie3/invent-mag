<div class="page-header d-print-none mt-4">
    <div class="{{ $containerClass ?? "container-xl" }}">
        <div class="row g-2 align-items-center">
            <div class="col">
                <div class="page-pretitle">{{ __('messages.accounting') }}</div>
                <h2 class="page-title d-flex align-items-center">
                    <i class="ti ti-notebook me-2"></i>{{ __('messages.manual_journal_entries') }}
                    <a href="#" class="ms-3 text-muted trigger-tutorial-modal" data-bs-toggle="tooltip" title="{{ __('messages.tutorial') }}">
                        <i class="ti ti-help-circle fs-3"></i>
                    </a>
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
