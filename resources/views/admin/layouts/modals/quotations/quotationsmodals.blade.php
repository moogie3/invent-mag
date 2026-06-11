{{-- Delete Single Quotation Modal --}}
<div class="modal modal-blur fade" id="deleteModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
        <div class="modal-content border-0 shadow-lg rounded-3">
            <div class="modal-header">
                <h5 class="modal-title">{{ __('messages.delete_quotation') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-status bg-danger"></div>
            <div class="modal-body text-center py-4">
                <i class="ti ti-alert-triangle icon text-danger mb-2" style="font-size: 3rem;"></i>
                <h3>{{ __('messages.are_you_sure') }}</h3>
                <p class="text-muted">{{ __('messages.delete_quotation_confirmation') }}</p>
            </div>
            <div class="modal-footer">
                <form id="deleteForm" method="POST">
                    @csrf
                    @method('DELETE')
                    <div class="w-100 d-flex gap-2 justify-content-center">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            {{ __('messages.cancel') }}
                        </button>
                        <button type="submit" class="btn btn-danger">
                            <i class="ti ti-trash me-1"></i> {{ __('messages.delete') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

{{-- View Quotation Modal --}}
<div class="modal modal-blur fade" id="viewQuotationModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ __('messages.quotation_details') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="viewQuotationModalContent">
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">{{ __('messages.loading') }}</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    {{ __('messages.close') }}
                </button>
            </div>
        </div>
    </div>
</div>
