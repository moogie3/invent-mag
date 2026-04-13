<div class="page-header d-print-none mt-4">
    <div class="{{ $containerClass ?? "container-xl" }}">
        <div class="row g-2 align-items-center">
            <div class="col">
                <div class="page-pretitle">{{ __('messages.accounting') }}</div>
                <h2 class="page-title d-flex align-items-center">
                    <i class="ti ti-eye me-2"></i>{{ __('messages.journal_entry_details') }}
                    <a href="#" class="ms-3 text-muted trigger-tutorial-modal" data-bs-toggle="tooltip" title="{{ __('Tutorial') }}">
                        <i class="ti ti-help-circle fs-3"></i>
                    </a>
                </h2>
            </div>
            <div class="col-auto ms-auto d-print-none">
                <div class="btn-list">
                    @if($entry->status === 'draft')
                        <a href="{{ route('admin.accounting.journal-entries.edit', $entry) }}" class="btn btn-primary">
                            <i class="ti ti-edit me-2"></i>{{ __('messages.edit') }}
                        </a>
                        <form action="{{ route('admin.accounting.journal-entries.post', $entry) }}" method="POST" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-success">
                                <i class="ti ti-check me-2"></i>{{ __('messages.post_entry') }}
                            </button>
                        </form>
                    @endif
                    @if($entry->status === 'posted')
                        @if(!$entry->reversingEntry)
                            <button type="button" class="btn btn-warning" onclick="reverseEntry('{{ route('admin.accounting.journal-entries.reverse', $entry) }}')">
                                <i class="ti ti-arrow-back-up me-2"></i>{{ __('messages.reverse') }}
                            </button>
                            <button type="button" class="btn btn-danger" onclick="voidEntry('{{ route('admin.accounting.journal-entries.void', $entry) }}')">
                                <i class="ti ti-x me-2"></i>{{ __('messages.void') }}
                            </button>
                        @endif
                    @endif
                    <a href="{{ route('admin.accounting.journal-entries.index') }}" class="btn btn-secondary">
                        <i class="ti ti-arrow-left me-2"></i>{{ __('messages.back') }}
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
