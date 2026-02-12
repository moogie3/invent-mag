<div class="page-header d-print-none mt-4">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                <div class="page-pretitle">{{ __('messages.accounting') }}</div>
                <h2 class="page-title">
                    <i class="ti ti-edit me-2"></i>{{ __('messages.edit_journal_entry') }}
                </h2>
            </div>
            <div class="col-auto ms-auto d-print-none">
                <a href="{{ route('admin.accounting.journal-entries.show', $entry) }}" class="btn btn-secondary">
                    <i class="ti ti-arrow-left me-2"></i>{{ __('messages.back') }}
                </a>
            </div>
        </div>
    </div>
</div>
