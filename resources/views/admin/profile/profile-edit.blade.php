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
                                        <div class="row g-3 mt-2">
                                            <div class="col-md-4">
                                                <div class="form-label">Email</div>
                                                <input type="email" name="email" class="form-control w-auto"
                                                    value="{{ auth()->user()->email }}" required>
                                            </div>
                                            <div class="col-md-3">
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
                                <div class="card-footer bg-transparent mt-auto">
                                    <div class="btn-list justify-content-end">
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
    @include('admin.layouts.modals.profilemodals')
@endsection
