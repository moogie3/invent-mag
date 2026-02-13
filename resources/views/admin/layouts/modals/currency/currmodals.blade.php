<div class="modal fade" id="confirmModal" tabindex="-1" aria-labelledby="confirmModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-3">
            <div class="modal-header">
                <h5 class="modal-title" id="confirmModalLabel">{{ __('messages.currency_modal_confirm_changes_title') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                {{ __('messages.currency_modal_confirm_changes_message') }}
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary-lt" data-bs-dismiss="modal">{{ __('messages.cancel') }}</button>
                <button type="button" class="btn btn-primary" id="confirmSubmit">{{ __('messages.save') }}</button>
            </div>
        </div>
    </div>
</div>
