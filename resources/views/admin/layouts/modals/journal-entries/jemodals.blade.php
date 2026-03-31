{{-- DELETE MODAL --}}
<div class="modal modal-blur fade" id="deleteModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
        <div class="modal-content border-0 shadow-lg rounded-3">
            <div class="modal-header">
                <h5 class="modal-title">{{ __('messages.delete_entry') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-status bg-danger"></div>
            <div class="modal-body text-center py-4">
                <i class="ti ti-alert-triangle text-danger" style="font-size: 3rem;"></i>
                <h3>{{ __('messages.are_you_sure') }}</h3>
                <div class="text-muted">{{ __('messages.delete_confirmation') }}</div>
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
                            <form id="deleteForm" method="POST">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger w-100">{{ __('messages.delete') }}</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- REVERSE MODAL --}}
<div class="modal modal-blur fade" id="reverseModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content border-0 shadow-lg rounded-3">
            <div class="modal-header">
                <h5 class="modal-title">{{ __('messages.reverse_entry') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-status bg-warning"></div>
            <form id="reverseForm" method="POST">
                @csrf
                <div class="modal-body py-4">
                    <div class="text-center mb-3">
                        <i class="ti ti-arrow-back-up text-warning" style="font-size: 3rem;"></i>
                        <h3 class="mt-3">{{ __('messages.reverse_confirmation') }}</h3>
                    </div>
                    <div class="mb-3">
                        <label class="form-label required">{{ __('messages.reason') }}</label>
                        <textarea class="form-control" name="notes" rows="3" required placeholder="{{ __('messages.enter_reverse_reason') }}"></textarea>
                    </div>
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
                                <button type="submit" class="btn btn-warning w-100">
                                    <i class="ti ti-arrow-back-up me-1"></i>{{ __('messages.reverse') }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- VOID MODAL --}}
<div class="modal modal-blur fade" id="voidModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content border-0 shadow-lg rounded-3">
            <div class="modal-header">
                <h5 class="modal-title">{{ __('messages.void_entry') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-status bg-danger"></div>
            <form id="voidForm" method="POST">
                @csrf
                <div class="modal-body py-4">
                    <div class="text-center mb-3">
                        <i class="ti ti-x text-danger" style="font-size: 3rem;"></i>
                        <h3 class="mt-3">{{ __('messages.void_confirmation') }}</h3>
                    </div>
                    <div class="mb-3">
                        <label class="form-label required">{{ __('messages.reason') }}</label>
                        <textarea class="form-control" name="reason" rows="3" required placeholder="{{ __('messages.enter_void_reason') }}"></textarea>
                    </div>
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
                                <button type="submit" class="btn btn-danger w-100">
                                    <i class="ti ti-x me-1"></i>{{ __('messages.void') }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
