@extends('admin.layouts.base')

@section('title', __('messages.profile_settings'))

@section('content')
    <div class="page-wrapper">
        <div class="page-wrapper">
            <div class="page-body">
                <div class="container-xl">
                    <div class="card">
                        <div class="card-body">
                            <h2><i class="ti ti-user-cog me-2"></i>{{ __('messages.account_settings') }}</h2>
                        </div>
                        <hr class="my-0">
                        <div class="row g-0">
                            <div class="col-12 col-md-3 border-end">
                                @include('admin.layouts.menu')
                            </div>
                            <div class="col-12 col-md-9 d-flex flex-column">
                                <div class="card-body">
                                    @if (session('status'))
                                        <div class="alert alert-info mb-3">
                                            {{ session('status') }}
                                        </div>
                                    @endif
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
                                                    <h3 class="mb-1">{{ __('messages.profile_picture') }}</h3>
                                                    <p class="text-muted mb-0 small">
                                                        {{ __('messages.upload_and_manage_your_profile_image') }}
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
                                                                <i
                                                                    class="ti ti-trash me-1"></i>{{ __('messages.remove_current_picture') }}
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
                                                    <h3 class="mb-1">{{ __('messages.business_profile') }}</h3>
                                                    <p class="text-muted mb-0 small">
                                                        {{ __('messages.manage_your_business_information_and_contact_details') }}
                                                    </p>
                                                </div>
                                            </div>
                                            <div class="settings-section-content">
                                                <div class="row g-3">
                                                    <div class="col-md-4">
                                                        <div class="form-label">{{ __('messages.user_name') }}</div>
                                                        <input type="text" name="name" class="form-control"
                                                            value="{{ auth()->user()->name }}" required>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <div class="form-label">{{ __('messages.business_name') }}</div>
                                                        <input type="text" name="shopname" class="form-control"
                                                            value="{{ auth()->user()->shopname }}" required>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <div class="form-label">{{ __('messages.address') }}</div>
                                                        <input type="text" name="address" class="form-control"
                                                            value="{{ auth()->user()->address }}" required>
                                                    </div>
                                                </div>
                                                <div class="row g-3 mt-2">
                                                    <div class="col-md-6">
                                                        <div class="form-label">{{ __('messages.email') }}</div>
                                                        <div class="input-group">
                                                            <input type="email" name="email" class="form-control" value="{{ auth()->user()->email }}" required @if (auth()->user()->hasVerifiedEmail()) disabled @endif>
                                                            <span class="input-group-text">
                                                                @if (auth()->user()->hasVerifiedEmail())
                                                                    <i class="ti ti-circle-check text-success" title="{{ __('messages.email_verified_message') }}"></i>
                                                                @else
                                                                    <i class="ti ti-circle-x text-danger" title="{{ __('messages.email_not_verified_message') }}"></i>
                                                                @endif
                                                            </span>
                                                        </div>
                                                        @if (!auth()->user()->hasVerifiedEmail())
                                                            <small class="form-text text-muted mt-1">
                                                                {{ __('messages.email_not_verified_action') }}
                                                                <a href="#" onclick="event.preventDefault(); document.getElementById('resendVerificationForm').submit();" class="btn-link">
                                                                    {{ __('messages.resend_verification_email') }}
                                                                </a>
                                                            </small>
                                                        @else
                                                            <small class="form-text text-muted mt-1">
                                                                {{ __('messages.email_change_info_verified') }}
                                                            </small>
                                                        @endif
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="form-label">{{ __('messages.timezone') }}</div>
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
                                                    <h3 class="mb-1">{{ __('messages.security_password') }}</h3>
                                                    <p class="text-muted mb-0 small">
                                                        {{ __('messages.update_your_password_to_keep_your_account_secure') }}
                                                    </p>
                                                </div>
                                            </div>
                                            <div class="settings-section-content">
                                                <div class="row g-3">
                                                    <div class="col-md-6">
                                                        <div class="form-label">{{ __('messages.new_password') }}</div>
                                                        <input type="password" name="password" id=".new_password"
                                                            class="form-control"
                                                            placeholder="{{ __('messages.enter_new_password_leave_empty_if_not_changing') }}"
                                                            oninput="togglePasswordModal()">
                                                        <small
                                                            class="text-muted">{{ __('messages.leave_empty_if_you_dont_want_to_change_your_password') }}</small>
                                                    </div>
                                                    <div class="col-md-6" id="confirmPasswordContainer"
                                                        style="display: none;">
                                                        <div class="form-label">{{ __('messages.confirm_new_password') }}
                                                        </div>
                                                        <input type="password" name="password_confirmation"
                                                            id="confirm_new_password" class="form-control"
                                                            placeholder="{{ __('messages.re_enter_new_password') }}">
                                                    </div>
                                                </div>
                                                <input type="hidden" name="current_password" id="current_password">
                                            </div>
                                        </div>

                                    </form>

                                    {{-- The form for resending verification email, triggered by JS --}}
                                    @if (!auth()->user()->hasVerifiedEmail())
                                    <form id="resendVerificationForm" class="d-none" method="POST" action="{{ route('verification.send') }}">
                                        @csrf
                                    </form>
                                    @endif
                                </div>
                                <div class="card-footer bg-transparent mt-auto">
                                    <div class="btn-list justify-content-end">
                                        <button type="button" class="btn btn-primary"
                                            onclick="showPasswordModal()">{{ __('messages.update_profile') }}</button>
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
