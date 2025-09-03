@extends('admin.layouts.base')

@section('title', 'Profile Settings')

@section('content')
    <div class="page-wrapper">
        <div class="page-wrapper">
            <div class="page-body">
                <div class="container-xl">
                    <div class="card">
                        <div class="card-body">
                            <h2><i class="ti ti-user-cog me-2"></i>ACCOUNT SETTINGS</h2>
                        </div>
                        <hr class="my-0">
                        <div class="row g-0">
                            <div class="col-12 col-md-3 border-end">
                                @include('admin.layouts.menu')
                            </div>
                            <div class="col-12 col-md-9 d-flex flex-column">
                                <div class="card-body">
                                    <form id="profileForm" action="{{ route('admin.setting.profile.update') }}"
                                        method="POST" enctype="multipart/form-data">
                                        @method('PUT')
                                        @csrf

                                        <!-- Profile Picture Section -->
                                        <div class="settings-section mb-5">
                                            <div class="settings-section-header">
                                                <div class="settings-icon-wrapper">
                                                    <i class="ti ti-photo"></i>
                                                </div>
                                                <div class="settings-section-title">
                                                    <h3 class="mb-1">Profile Picture</h3>
                                                    <p class="text-muted mb-0 small">Upload and manage your profile image
                                                    </p>
                                                </div>
                                            </div>
                                            <div class="settings-section-content">
                                                <div class="d-flex align-items-center gap-3">
                                                    @if (auth()->user()->avatar)
                                                        <span class="avatar avatar-xl"
                                                            style="background-image: url('{{ asset('storage/' . auth()->user()->avatar) }}'); width: 100px; height: 100px;">
                                                        </span>
                                                    @else
                                                        <span class="avatar avatar-xl avatar-initial rounded-circle"
                                                            style="width: 100px; height: 100px;">
                                                            <i class="ti ti-person" style="font-size: 4rem;"></i>
                                                        </span>
                                                    @endif
                                                    <div class="d-flex flex-column gap-2">
                                                        <input type="file" name="avatar" class="form-control">
                                                        @if (auth()->user()->avatar)
                                                            <button type="button" class="btn btn-sm btn-outline-danger"
                                                                data-bs-toggle="modal" data-bs-target="#deleteAvatarModal">
                                                                <i class="ti ti-trash me-1"></i>Remove current picture
                                                            </button>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Business Profile Section -->
                                        <div class="settings-section mb-5">
                                            <div class="settings-section-header">
                                                <div class="settings-icon-wrapper">
                                                    <i class="ti ti-building-store"></i>
                                                </div>
                                                <div class="settings-section-title">
                                                    <h3 class="mb-1">Business Profile</h3>
                                                    <p class="text-muted mb-0 small">Manage your business information and
                                                        contact details</p>
                                                </div>
                                            </div>
                                            <div class="settings-section-content">
                                                <div class="row g-3">
                                                    <div class="col-md-4">
                                                        <div class="form-label">User Name</div>
                                                        <input type="text" name="name" class="form-control"
                                                            value="{{ auth()->user()->name }}" required>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <div class="form-label">Business Name</div>
                                                        <input type="text" name="shopname" class="form-control"
                                                            value="{{ auth()->user()->shopname }}" required>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <div class="form-label">Address</div>
                                                        <input type="text" name="address" class="form-control"
                                                            value="{{ auth()->user()->address }}" required>
                                                    </div>
                                                </div>
                                                <div class="row g-3 mt-2">
                                                    <div class="col-md-6">
                                                        <div class="form-label">Email</div>
                                                        <input type="email" name="email" class="form-control"
                                                            value="{{ auth()->user()->email }}" required>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="form-label">Timezone</div>
                                                        <select name="timezone" class="form-control" required>
                                                            @foreach (timezone_identifiers_list() as $tz)
                                                                <option value="{{ $tz }}"
                                                                    {{ auth()->user()->timezone === $tz ? 'selected' : '' }}>
                                                                    {{ $tz }}
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Security & Password Section -->
                                        <div class="settings-section mb-4">
                                            <div class="settings-section-header">
                                                <div class="settings-icon-wrapper">
                                                    <i class="ti ti-lock"></i>
                                                </div>
                                                <div class="settings-section-title">
                                                    <h3 class="mb-1">Security & Password</h3>
                                                    <p class="text-muted mb-0 small">Update your password to keep your
                                                        account secure</p>
                                                </div>
                                            </div>
                                            <div class="settings-section-content">
                                                <div class="row g-3">
                                                    <div class="col-md-6">
                                                        <div class="form-label">New Password</div>
                                                        <input type="password" name="password" id="new_password"
                                                            class="form-control"
                                                            placeholder="Enter new password (leave empty if not changing)"
                                                            oninput="togglePasswordModal()">
                                                        <small class="text-muted">Leave empty if you don't want to change
                                                            your password</small>
                                                    </div>
                                                    <div class="col-md-6" id="confirmPasswordContainer"
                                                        style="display: none;">
                                                        <div class="form-label">Confirm New Password</div>
                                                        <input type="password" name="password_confirmation"
                                                            id="confirm_new_password" class="form-control"
                                                            placeholder="Re-enter new password">
                                                    </div>
                                                </div>
                                                <input type="hidden" name="current_password" id="current_password">
                                            </div>
                                        </div>

                                    </form>
                                </div>
                                <div class="card-footer bg-transparent mt-auto">
                                    <div class="btn-list justify-content-end">
                                        <button type="button" class="btn btn-primary"
                                            onclick="showPasswordModal()">Update Profile</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @include('admin.layouts.modals.profilemodals')
@endsection
