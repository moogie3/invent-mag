<div class="modal modal-blur fade" id="deleteAvatarModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ __('profile_modal_delete_avatar_title') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-status bg-danger"></div>
            <div class="modal-body text-center py-4">
                <i class="ti ti-alert-triangle text-danger" style="font-size: 3rem;"></i>
                <h3>{{ __('are_you_sure') }}</h3>
                <div class="text-muted">{{ __('profile_modal_delete_avatar_warning') }}</div>
            </div>
            <div class="modal-footer">
                <div class="w-100">
                    <div class="row">
                        <div class="col">
                            <button type="button" class="btn w-100" data-bs-dismiss="modal">
                                {{ __('cancel') }}
                            </button>
                        </div>
                        <div class="col">
                            <form id="delete-avatar-form" action="{{ route('admin.setting.profile.delete-avatar') }}" method="POST">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger w-100">{{ __('delete') }}</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="passwordModal" tabindex="-1" aria-labelledby="passwordModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="passwordModalLabel">{{ __('profile_modal_confirm_password_title') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="col-md-12 mb-1">
                    <label class="mb-2">{{ __('profile_modal_enter_current_password') }}</label>
                    <input type="password" id="modal_current_password" class="form-control"
                        placeholder="{{ __('profile_modal_enter_current_password_placeholder') }}">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('cancel') }}</button>
                <button type="button" class="btn btn-primary" onclick="submitProfileForm()">{{ __('confirm') }}</button>
            </div>
        </div>
    </div>
</div>
