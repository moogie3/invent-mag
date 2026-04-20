<div class="page-header d-print-none mt-4">
    <div class="{{ $containerClass ?? "container-xl" }}">
        <div class="row g-2 align-items-center">
            <div class="col">
                <div class="page-pretitle">
                    <a href="{{ route('admin.accounting.journal-entries.index') }}" class="text-muted text-decoration-none">
                        <i class="ti ti-arrow-left"></i> {{ __('messages.back') }}
                    </a>
                </div>
                <h2 class="page-title d-flex align-items-center">
                    <i class="ti ti-edit me-2"></i>{{ __('messages.edit_journal_entry') }}
                    <a href="#" class="ms-3 text-muted trigger-tutorial-modal" data-bs-toggle="tooltip" title="{{ __('messages.tutorial') }}">
                        <i class="ti ti-help-circle fs-3"></i>
                    </a>
                </h2>
            </div>
        </div>
    </div>
</div>
