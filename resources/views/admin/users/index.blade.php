@extends('admin.layouts.base')

@section('title', __('messages.user_page_title'))

@section('content')
    <div class="page-wrapper">
        <div class="page-body">
            <div class="container-xl">
                <div class="card">
                    <div class="card-body">
                        <h2><i class="ti ti-users me-2"></i>{{ __('messages.user_management_title') }}</h2>
                    </div>
                    <hr class="my-0">
                    <div class="row g-0">
                        <div class="col-12 col-md-3 border-end">
                            @include('admin.layouts.menu')
                        </div>
                        <div class="col-12 col-md-9 d-flex flex-column">
                            <div class="row row-deck row-cards">
                                <div class="col-md-12">
                                    <div class="card card-primary">
                                        <div class="card-body border-bottom py-3">
                                            <div class="d-flex justify-content-between align-items-center mb-3">
                                                <h2 class="mb-0">
                                                    <i class="ti ti-users fs-2"></i>
                                                    {{ __('messages.user_total_users') }}
                                                    <strong>{{ $users->count() }}</strong>
                                                </h2>
                                                <div class="btn-list">
                                                    <a href="#" class="btn btn-primary d-none d-sm-inline-block"
                                                        data-bs-toggle="modal" data-bs-target="#createUserModal">
                                                        <i class="ti ti-plus fs-4"></i> {{ __('messages.user_new_user') }}
                                                    </a>
                                                </div>
                                            </div>
                                            <hr class="my-0">
                                            <!-- Replace your role statistics section with this -->

                                            {{-- User Statistics --}}
                                            <div class="row">
                                                <div class="col-lg-4">
                                                    <div class="card card-sm border-0 shadow-sm">
                                                        <div class="card-body">
                                                            <div class="row align-items-center">
                                                                <div class="col-auto">
                                                                    <span class="bg-red text-white avatar avatar-lg">
                                                                        <i class="ti ti-crown fs-3"></i>
                                                                    </span>
                                                                </div>
                                                                <div class="col">
                                                                    <div class="font-weight-bold text-lg mb-1">
                                                                        {{ __('messages.user_role_superuser') }}
                                                                    </div>
                                                                    <div class="h2 mb-0 text-red">
                                                                        {{ $users->filter(function ($user) {return $user->hasRole('superuser');})->count() }}
                                                                    </div>
                                                                    <div class="text-muted small">
                                                                        {{ __('messages.user_role_superuser_description') }}
                                                                    </div>
                                                                </div>
                                                                <div class="col-auto">
                                                                    <div class="bg-red-lt rounded-circle p-2">
                                                                        <i class="ti ti-shield-check text-red"></i>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-lg-4">
                                                    <div class="card card-sm border-0 shadow-sm">
                                                        <div class="card-body">
                                                            <div class="row align-items-center">
                                                                <div class="col-auto">
                                                                    <span class="bg-blue text-white avatar avatar-lg">
                                                                        <i class="ti ti-user-shield fs-3"></i>
                                                                    </span>
                                                                </div>
                                                                <div class="col">
                                                                    <div class="font-weight-bold text-lg mb-1">
                                                                        {{ __('messages.user_role_staff') }}
                                                                    </div>
                                                                    <div class="h2 mb-0 text-blue">
                                                                        {{ $users->filter(function ($user) {return $user->hasRole('staff');})->count() }}
                                                                    </div>
                                                                    <div class="text-muted small">
                                                                        {{ __('messages.user_role_staff_description') }}
                                                                    </div>
                                                                </div>
                                                                <div class="col-auto">
                                                                    <div class="bg-blue-lt rounded-circle p-2">
                                                                        <i class="ti ti-briefcase text-blue"></i>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-lg-4">
                                                    <div class="card card-sm border-0 shadow-sm">
                                                        <div class="card-body">
                                                            <div class="row align-items-center">
                                                                <div class="col-auto">
                                                                    <span class="bg-green text-white avatar avatar-lg">
                                                                        <i class="ti ti-device-desktop-analytics fs-3"></i>
                                                                    </span>
                                                                </div>
                                                                <div class="col">
                                                                    <div class="font-weight-bold text-lg mb-1">
                                                                        {{ __('messages.user_role_pos') }}
                                                                    </div>
                                                                    <div class="h2 mb-0 text-green">
                                                                        {{ $users->filter(function ($user) {return $user->hasRole('pos');})->count() }}
                                                                    </div>
                                                                    <div class="text-muted small">
                                                                        {{ __('messages.user_role_pos_description') }}
                                                                    </div>
                                                                </div>
                                                                <div class="col-auto">
                                                                    <div class="bg-green-lt rounded-circle p-2">
                                                                        <i class="ti ti-cash text-green"></i>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        {{-- TABLE --}}
                                        <div class="table-responsive">
                                            <table class="table card-table table-vcenter">
                                                <thead style="font-size: large">
                                                    <tr>
                                                        <th><button class="table-sort fs-4 py-3"
                                                                data-sort="sort-name">{{ __('messages.table_name') }}</button>
                                                        </th>
                                                        <th><button class="table-sort fs-4 py-3"
                                                                data-sort="sort-email">{{ __('messages.table_email') }}</button>
                                                        </th>
                                                        <th><button class="table-sort fs-4 py-3"
                                                                data-sort="sort-roles">{{ __('messages.table_roles') }}</button>
                                                        </th>
                                                        <th><button class="table-sort fs-4 py-3"
                                                                data-sort="sort-permissions">{{ __('messages.table_permissions') }}</button>
                                                        </th>
                                                        <th style="width:180px;text-align:center" class="fs-4 py-3">
                                                            {{ __('messages.table_action') }}
                                                        </th>
                                                    </tr>
                                                </thead>
                                                <tbody class="table-tbody">
                                                    @foreach ($users as $user)
                                                        <tr>
                                                            <td class="sort-name">{{ $user->name }}</td>
                                                            <td class="sort-email">{{ $user->email }}</td>
                                                            <td class="sort-roles">
                                                                @foreach ($user->getRoleNames() as $role)
                                                                    <span
                                                                        class="badge bg-blue-lt">{{ $role }}</span>
                                                                @endforeach
                                                            </td>
                                                            <td class="sort-permissions">
                                                                @foreach ($user->getAllPermissions() as $permission)
                                                                    <span
                                                                        class="badge bg-green-lt">{{ $permission->name }}</span>
                                                                @endforeach
                                                            </td>
                                                            <td style="text-align:center">
                                                                <div class="dropdown">
                                                                    <button class="btn dropdown-toggle align-text-top"
                                                                        data-bs-toggle="dropdown"
                                                                        data-bs-boundary="viewport">
                                                                        {{ __('messages.table_action') }}
                                                                    </button>
                                                                    <div class="dropdown-menu">
                                                                        <a href="#"
                                                                            class="dropdown-item edit-user-btn"
                                                                            data-bs-toggle="modal"
                                                                            data-bs-target="#editUserModal"
                                                                            data-user-id="{{ $user->id }}">
                                                                            <i class="ti ti-edit me-2"></i>
                                                                            {{ __('messages.edit') }}
                                                                        </a>
                                                                        <button type="button"
                                                                            class="dropdown-item text-danger delete-user-btn"
                                                                            data-bs-toggle="modal"
                                                                            data-bs-target="#deleteUserModal"
                                                                            data-user-id="{{ $user->id }}">
                                                                            <i class="ti ti-trash me-2"></i>
                                                                            {{ __('messages.delete') }}
                                                                        </button>
                                                                    </div>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>

                                        {{-- PAGINATION (if you have pagination for users) --}}
                                        @if (method_exists($users, 'links'))
                                            <div class="card-footer d-flex align-items-center">
                                                <p class="m-0 text-secondary">
                                                    {{ __('messages.pagination_showing_entries', [
                                                        'first' => $users->firstItem(),
                                                        'last' => $users->lastItem(),
                                                        'total' => $users->total(),
                                                    ]) }}
                                                    entries
                                                </p>
                                                <div class="ms-auto">
                                                    {{ $users->appends(request()->query())->links('vendor.pagination.tabler') }}
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const deleteUserModal = new bootstrap.Modal(document.getElementById('deleteUserModal'));
            const deleteUserForm = document.getElementById('deleteUserForm');
            const deleteButton = deleteUserForm.querySelector('button[type="submit"]');

            document.querySelectorAll('.delete-user-btn').forEach(button => {
                button.addEventListener('click', function() {
                    const userId = this.getAttribute('data-user-id');
                    let actionTemplate =
                        "{{ route('admin.users.destroy', ['user' => 'USER_ID_PLACEHOLDER']) }}";
                    const action = actionTemplate.replace('USER_ID_PLACEHOLDER', userId);
                    deleteUserForm.setAttribute('action', action);
                });
            });

            deleteButton.addEventListener('click', function(e) {
                e.preventDefault();
                const form = deleteUserForm;
                const action = form.getAttribute('action');

                fetch(action, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            deleteUserModal.hide();
                            // Show success toast
                            toastr.success(data.message);
                            // Refresh page after a short delay
                            setTimeout(() => {
                                location.reload();
                            }, 1000);
                        } else {
                            // Handle errors if needed
                            toastr.error('An error occurred.');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        toastr.error('An error occurred.');
                    });
            });
        });
    </script>
@endpush

@include('admin.layouts.modals.users.usersmodals')
