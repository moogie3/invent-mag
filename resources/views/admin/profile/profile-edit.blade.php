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
                            Settings
                        </h2>
                    </div>
                </div>
            </div>
        </div>

        <div class="page-body">
            <div class="container-xl">
                <div class="row row-deck row-cards">
                    <div class="col-md-12">
                        <div class="card card-primary">
                            <div class="card-header">
                                <h2>Profile Settings</h2>
                            </div>
                            <div class="card-body">
                                <form id="profileForm" action="{{ route('admin.profile.update') }}" method="POST"
                                    enctype="multipart/form-data">
                                    @method('PUT')
                                    @csrf
                                    <div class="col-md-12 mb-3">
                                        <label>Avatar</label><br>
                                        <img src="{{ auth()->user()->avatar ? asset('storage/' . auth()->user()->avatar) : asset('default-avatar.png') }}"
                                            alt="Avatar" class="img-thumbnail mb-2" width="100">
                                        <input type="file" name="avatar" class="form-control">
                                    </div>
                                    <div class="col-md-12 mb-3">
                                        <label>Name</label>
                                        <input type="text" name="name" class="form-control"
                                            value="{{ auth()->user()->name }}" required>
                                    </div>
                                    <div class="col-md-12 mb-3">
                                        <label>Email</label>
                                        <input type="email" name="email" class="form-control"
                                            value="{{ auth()->user()->email }}" required>
                                    </div>
                                    <div class="col-md-12 mb-3">
                                        <label>Store Name</label>
                                        <input type="text" name="shopname" class="form-control"
                                            value="{{ auth()->user()->shopname }}" required>
                                    </div>
                                    <div class="col-md-12 mb-3">
                                        <label>Address</label>
                                        <input type="text" name="address" class="form-control"
                                            value="{{ auth()->user()->address }}" required>
                                    </div>

                                    <!-- New Password Field -->
                                    <div class="col-md-12 mb-3">
                                        <label>New Password (Leave empty if not changing)</label>
                                        <input type="password" name="password" id="new_password" class="form-control"
                                            placeholder="Enter new password" oninput="togglePasswordModal()">
                                    </div>

                                    <!-- Confirm New Password Field -->
                                    <div class="col-md-12 mb-3" id="confirmPasswordContainer" style="display: none;">
                                        <label>Re-enter New Password</label>
                                        <input type="password" name="password_confirmation" id="confirm_new_password"
                                            class="form-control" placeholder="Re-enter new password">
                                    </div>

                                    <!-- Hidden input for current password -->
                                    <input type="hidden" name="current_password" id="current_password">

                                    <!-- Button to trigger modal only if changing password -->
                                    <button type="submit" id="updateButton" class="btn btn-primary">Update Profile</button>
                                </form>

                                <!-- Password Confirmation Modal -->
                                <div class="modal fade" id="passwordModal" tabindex="-1"
                                    aria-labelledby="passwordModalLabel" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered"> <!-- Added 'modal-dialog-centered' -->
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title" id="passwordModalLabel">Confirm Current Password
                                                </h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"
                                                    aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="col-md-12 mb-3">
                                                    <label>Enter Current Password</label>
                                                    <input type="password" id="modal_current_password" class="form-control"
                                                        placeholder="Enter your current password">
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary"
                                                    data-bs-dismiss="modal">Cancel</button>
                                                <button type="button" class="btn btn-primary"
                                                    onclick="submitProfileForm()">Confirm</button>
                                            </div>
                                        </div>
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
