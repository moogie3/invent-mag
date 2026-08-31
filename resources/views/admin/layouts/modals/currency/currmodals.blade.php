<div class="modal modal-blur fade" id="confirmModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
        <div class="modal-content border-0 shadow-lg rounded-3">
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            <div class="modal-status bg-primary"></div>
            <div class="modal-body text-center py-4">
                <i class="ti ti-info-circle text-primary mb-3" style="font-size: 3rem;"></i>
                <h3>{{ __('messages.currency_modal_confirm_changes_title') }}</h3>
                <div class="text-muted">{{ __('messages.currency_modal_confirm_changes_message') }}</div>
            </div>
            <div class="modal-footer">
                <div class="w-100">
                    <div class="row">
                        <div class="col">
                            <button type="button" class="btn w-100" data-bs-dismiss="modal">
                                {{ __('messages.cancel') }}
                            </button>
                        </div>
                        <div class="col">
                            <button type="button" class="btn btn-primary w-100" id="confirmSubmit">
                                {{ __('messages.save') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
