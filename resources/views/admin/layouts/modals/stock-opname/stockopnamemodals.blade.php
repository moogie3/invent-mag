<div class="modal modal-blur fade" id="cancelOpnameModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
        <div class="modal-content border-0 shadow-lg rounded-3">
            <div class="modal-header">
                <h5 class="modal-title">{{ __('messages.cancel_opname') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-status bg-danger"></div>
            <div class="modal-body text-center py-4">
                <i class="ti ti-alert-triangle text-danger" style="font-size: 3rem;"></i>
                <h3>{{ __('messages.are_you_sure') }}</h3>
                <div class="text-muted">{{ __('messages.cancel_opname_warning') }}</div>
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
                            <form id="cancelOpnameForm" action="" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-danger w-100">{{ __('messages.confirm') }}</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal modal-blur fade" id="completeOpnameModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content border-0 shadow-lg rounded-3">
            <div class="modal-header">
                <h5 class="modal-title">{{ __('messages.complete_post_adjustments') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-status bg-success"></div>
            <div class="modal-body text-center py-4">
                <i class="ti ti-check-circle text-success" style="font-size: 3rem;"></i>
                <h3>{{ __('messages.are_you_sure') }}</h3>
                <div class="text-muted">{{ __('messages.complete_opname_warning') }}</div>
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
                            <form id="completeOpnameForm" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-success w-100">{{ __('messages.confirm') }}</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>