<div class="page-header no-print">
    <div class="{{ $containerClass ?? "container-xl" }}">
        <div class="row align-items-center">
            <div class="col">
                <div class="page-pretitle">
                    <a href="{{ route('admin.quotations') }}" class="text-muted text-decoration-none">
                        <i class="ti ti-arrow-left"></i> {{ __('messages.back') }}
                    </a>
                </div>
                <h2 class="page-title no-print fw-bold d-flex align-items-center">
                    <i class="ti ti-file-description me-2"></i>{{ __('messages.quotation_details') }}
                    <a href="#" class="ms-3 text-muted trigger-tutorial-modal" data-bs-toggle="tooltip" title="{{ __('messages.tutorial') }}">
                        <i class="ti ti-help-circle fs-3"></i>
                    </a>
                </h2>
            </div>
            <div class="col-auto">
                @php
                    $canEdit = $quotation->status === 'draft' || $quotation->status === 'sent';
                @endphp

                @if($canEdit)
                <a href="{{ route('admin.quotations.edit', $quotation->id) }}" class="btn btn-primary me-2">
                    <i class="ti ti-edit me-1"></i> {{ __('messages.edit') }}
                </a>
                @endif

                <a href="{{ route('admin.quotations.print', $quotation->id) }}" target="_blank" class="btn btn-secondary me-2">
                    <i class="ti ti-printer me-1"></i> {{ __('messages.print_quotation') }}
                </a>
            </div>
        </div>


    </div>
</div>
