<div class="modal modal-blur fade" id="createUserModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header border-bottom-0 pb-0">
                <h3 class="modal-title fw-semibold">{{ __('messages.user_modal_create_title') }}</h3>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('admin.users.store') }}" method="POST">
                @csrf
                <div class="modal-body pt-2">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">{{ __('messages.table_name') }}</label>
                            <input type="text" name="name" class="form-control" value="{{ old('name') }}"
                                required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">{{ __('messages.email') }}</label>
                            <input type="email" name="email" class="form-control" value="{{ old('email') }}"
                                required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">{{ __('messages.password') }}</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">{{ __('messages.confirm_new_password') }}</label>
                            <input type="password" name="password_confirmation" class="form-control" required>
                        </div>

                    </div>

                    <h3 class="card-title mt-4 fw-semibold">{{ __('messages.user_modal_assign_roles') }}</h3>
                    <div class="mb-3">
                        @foreach ($roles as $role)
                            <label class="form-check form-check-inline">
                                <input class="form-check-input" type="checkbox" name="roles[]"
                                    value="{{ $role->name }}"
                                    {{ in_array($role->name, old('roles', [])) ? 'checked' : '' }}>
                                <span class="form-check-label">{{ $role->name }}</span>
                            </label>
                        @endforeach
                    </div>

                    <h3 class="card-title mt-4 fw-semibold">{{ __('messages.user_modal_assign_direct_permissions') }}
                    </h3>
                    <div class="mb-3">
                        @foreach ($permissions as $permission)
                            <label class="form-check form-check-inline">
                                <input class="form-check-input" type="checkbox" name="permissions[]"
                                    value="{{ $permission->name }}"
                                    {{ in_array($permission->name, old('permissions', [])) ? 'checked' : '' }}>
                                <span class="form-check-label">{{ $permission->name }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>
                <div class="modal-footer border-top-0 pt-0">
                    <button type="button" class="btn btn-link text-muted"
                        data-bs-dismiss="modal">{{ __('messages.cancel') }}</button>
                    <button type="submit"
                        class="btn btn-primary ms-auto">{{ __('messages.user_modal_create_user') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>


<div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-3">
            <div class="modal-header">
                <h5 class="modal-title" id="editUserModalLabel">{{ __('messages.user_modal_edit_user') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editUserForm" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <input type="hidden" id="edit_user_id" name="user_id">
                    <div class="mb-3">
                        <label class="form-label">{{ __('messages.table_name') }}</label>
                        <input type="text" name="name" id="edit_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('messages.email') }}</label>
                        <input type="email" name="email" id="edit_email" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('messages.user_modal_password_leave_empty') }}</label>
                        <input type="password" name="password" id="edit_password" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('messages.confirm_new_password') }}</label>
                        <input type="password" name="password_confirmation" id="edit_password_confirmation"
                            class="form-control">
                    </div>

                    <h3 class="card-title mt-4">{{ __('messages.user_modal_assign_roles') }}</h3>
                    <div class="mb-3" id="edit_roles_container">
                        <!-- Roles will be loaded here via JavaScript -->
                    </div>

                    <h3 class="card-title mt-4">{{ __('messages.user_modal_assign_direct_permissions') }}</h3>
                    <div class="mb-3" id="edit_permissions_container">
                        <!-- Permissions will be loaded here via JavaScript -->
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary"
                        data-bs-dismiss="modal">{{ __('messages.close') }}</button>
                    <button type="submit"
                        class="btn btn-primary">{{ __('messages.user_modal_update_user') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal modal-blur fade" id="deleteUserModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
        <div class="modal-content border-0 shadow-lg rounded-3">
            <div class="modal-header">
                <h5 class="modal-title">{{ __('messages.user_modal_delete_user_title') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-status bg-danger"></div>
            <div class="modal-body text-center py-4">
                <i class="ti ti-alert-triangle text-danger" style="font-size: 3rem;"></i>
                <h3>{{ __('messages.are_you_sure') }}</h3>
                <div class="text-muted">{{ __('messages.user_modal_delete_user_warning') }}</div>
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
                            <button type="button" id="confirmDeleteBtn"
                                class="btn btn-danger w-100">{{ __('messages.delete') }}</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
