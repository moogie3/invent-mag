@extends('admin.layouts.base')

@section('title', 'Profile Settings')

@section('content')
    <div class="page-wrapper">
        <div class="page-header">
            <div class="container-xl">
                <div class="row align-items-center">
                    <div class="col">
                        <div class="page-pretitle">
                            Overview
                        </div>
                        <h2 class="page-title">
                            Account Settings
                        </h2>
                    </div>
                </div>
            </div>
        </div>

        <div class="page-wrapper">
            <div class="page-body">
                <div class="container-xl">
                    <div class="card">
                        <div class="row g-0">
                            <div class="col-12 col-md-3 border-end">
                                @include('admin.layouts.menu') <!-- Include the new menu component -->
                            </div>
                            <div class="col-12 col-md-9 d-flex flex-column">
                                <div class="card-body">
                                    <h2 class="mb-4">My Account</h2>
                                    <div class="d-flex justify-content-between align-items-center mb-4">
                                        <h3 class="card-title">Profile Details</h3>
                                        <button type="button" class="btn btn-ghost-danger" data-bs-toggle="modal"
                                            data-bs-target="#deleteAvatarModal">
                                            Delete avatar
                                        </button>
                                    </div>
                                    <form id="profileForm" action="{{ route('admin.setting.profile.update') }}"
                                        method="POST" enctype="multipart/form-data">
                                        @method('PUT')
                                        @csrf

                                        <div class="row align-items-center">
                                            <span class="avatar avatar-xl"
                                                style="background-image: url('{{ auth()->user()->avatar ? asset('storage/' . auth()->user()->avatar) : asset('default-avatar.png') }}');">
                                            </span>
                                            <div class="col-auto"><input type="file" name="avatar" class="form-control">
                                            </div>
                                        </div>
                                        <h3 class="card-title mt-4">Business Profile</h3>
                                        <div class="row g-3">
                                            <div class="col-md">
                                                <div class="form-label">User Name</div>
                                                <input type="text" name="name" class="form-control"
                                                    value="{{ auth()->user()->name }}" required>
                                            </div>
                                            <div class="col-md">
                                                <div class="form-label">Business Name</div>
                                                <input type="text" name="shopname" class="form-control"
                                                    value="{{ auth()->user()->shopname }}" required>
                                            </div>
                                            <div class="col-md">
                                                <div class="form-label">Address</div>
                                                <input type="text" name="address" class="form-control"
                                                    value="{{ auth()->user()->address }}" required>
                                            </div>
                                        </div>
                                        <h3 class="card-title mt-4">Email</h3>
                                        <div class="row g-2">
                                            <div class="col-auto">
                                                <input type="email" name="email" class="form-control w-auto"
                                                    value="{{ auth()->user()->email }}" required>
                                            </div>
                                        </div>
                                        <h3 class="card-title mt-4">Password</h3>
                                        <p class="card-subtitle">You can set a new password if you want to change it. leave
                                            empty if not changing</p>
                                        <div class="col-md-12 mb-3">
                                            <input type="password" name="password" id="new_password" class="form-control"
                                                placeholder="Enter new password" oninput="togglePasswordModal()">
                                        </div>
                                        <div class="col-md-12 mb-3" id="confirmPasswordContainer" style="display: none;">
                                            <label>Re-enter New Password</label>
                                            <input type="password" name="password_confirmation" id="confirm_new_password"
                                                class="form-control" placeholder="Re-enter new password">
                                        </div>
                                        <input type="hidden" name="current_password" id="current_password">
                                    </form>
                                </div>

                                <!-- Delete Avatar Modal -->
                                <div class="modal fade" id="deleteAvatarModal" tabindex="-1"
                                    aria-labelledby="deleteAvatarModalLabel" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title" id="deleteAvatarModalLabel">Confirm Avatar
                                                    Deletion</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"
                                                    aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">
                                                Are you sure you want to delete your avatar?
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary"
                                                    data-bs-dismiss="modal">Cancel</button>
                                                <form id="delete-avatar-form"
                                                    action="{{ route('admin.setting.profile.delete-avatar') }}"
                                                    method="POST">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-danger">Delete</button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="card-footer bg-transparent mt-auto">
                                    <div class="btn-list justify-content-end">
                                        <a href="#" class="btn">Cancel</a>
                                        <button type="button" class="btn btn-primary"
                                            onclick="showPasswordModal()">Update</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Password Confirmation Modal -->
    <div class="modal fade" id="passwordModal" tabindex="-1" aria-labelledby="passwordModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="passwordModalLabel">Confirm Current Password</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="col-md-12 mb-1">
                        <label class="mb-2">Enter Current Password</label>
                        <input type="password" id="modal_current_password" class="form-control"
                            placeholder="Enter your current password">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="submitProfileForm()">Confirm</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        function togglePasswordModal() {
            let newPassword = document.getElementById('new_password').value;
            let confirmContainer = document.getElementById('confirmPasswordContainer');
            confirmContainer.style.display = newPassword ? 'block' : 'none';
        }

        function showPasswordModal() {
            let newPassword = document.getElementById('new_password').value;
            if (newPassword) {
                let modal = new bootstrap.Modal(document.getElementById('passwordModal'));
                modal.show();
            } else {
                document.getElementById('profileForm').submit();
            }
        }

        function submitProfileForm() {
            let currentPasswordInput = document.getElementById('modal_current_password').value;
            document.getElementById('current_password').value = currentPasswordInput;
            document.getElementById('profileForm').submit();
        }
    </script>
@endsection
