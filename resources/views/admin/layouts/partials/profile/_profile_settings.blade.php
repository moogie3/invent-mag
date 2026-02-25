<div class="card-body">
    <form id="profileForm" action="{{ route('admin.setting.profile.update') }}" method="POST"
        enctype="multipart/form-data">
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
                            <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal"
                                data-bs-target="#deleteAvatarModal">
                                <i class="ti ti-trash me-1"></i>{{ __('messages.remove_current_picture') }}
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
                        <input type="text" name="name" class="form-control" value="{{ auth()->user()->name }}"
                            required>
                    </div>
                    <div class="col-md-4">
                        <div class="form-label">{{ __('messages.business_name') }}</div>
                        <input type="text" name="shopname" class="form-control"
                            value="{{ auth()->user()->shopname }}" required>
                    </div>
                    <div class="col-md-4">
                        <div class="form-label">{{ __('messages.address') }}</div>
                        <input type="text" name="address" class="form-control" value="{{ auth()->user()->address }}"
                            required>
                    </div>
                </div>
                <div class="row g-3 mt-2">
                    <div class="col-md-6">
                        <div class="form-label">{{ __('messages.email') }}</div>
                        <div class="input-group">
                            <input type="email" name="email" class="form-control"
                                value="{{ auth()->user()->email }}" required
                                @if (auth()->user()->hasVerifiedEmail()) disabled @endif>
                            <span class="input-group-text">
                                @if (auth()->user()->hasVerifiedEmail())
                                    <i class="ti ti-circle-check text-success"
                                        title="{{ __('messages.email_verified_message') }}"></i>
                                @else
                                    <i class="ti ti-circle-x text-danger"
                                        title="{{ __('messages.email_not_verified_message') }}"></i>
                                @endif
                            </span>
                        </div>
                        @if (!auth()->user()->hasVerifiedEmail())
                            <small class="form-text text-muted mt-1">
                                {{ __('messages.email_not_verified_action') }}
                                <a href="#"
                                    onclick="event.preventDefault(); document.getElementById('resendVerificationForm').submit();"
                                    class="btn-link">
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

        <div class="card-footer bg-transparent mt-auto">
            <div class="btn-list justify-content-end">
                <button type="submit" class="btn btn-primary">{{ __('messages.update_profile') }}</button>
            </div>
        </div>
    </form>

    <form id="passwordForm" action="{{ route('admin.setting.profile.update-password') }}" method="POST">
        @method('PUT')
        @csrf

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
                        <input type="password" name="password" id="password" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <div class="form-label">{{ __('messages.confirm_new_password') }}</div>
                        <input type="password" name="password_confirmation" id="password_confirmation"
                            class="form-control" required>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-footer bg-transparent mt-auto">
            <div class="btn-list justify-content-end">
                <button type="submit" class="btn btn-secondary">{{ __('messages.update_password') }}</button>
            </div>
        </div>
    </form>

    <form id="apiTokenForm" action="{{ route('admin.setting.profile.token') }}" method="POST">
        @csrf
        <!-- API Token Section -->
        <div class="settings-section mt-4">
            <div class="settings-section-header">
                <div class="settings-icon-wrapper">
                    <i class="ti ti-key"></i>
                </div>
                <div class="settings-section-title">
                    <h3 class="mb-1">{{ __('messages.api_token') }}</h3>
                    <p class="text-muted mb-0 small">
                        {{ __('messages.generate_and_manage_your_api_token') }}
                    </p>
                </div>
            </div>
            <div class="settings-section-content">
                @if (session('api_token'))
                    <div class="alert alert-info" role="alert">
                        <div class="d-flex">
                            <div>
                                <i class="ti ti-info-circle me-2"></i>
                            </div>
                            <div>
                                <h4 class="alert-title">{{ __('messages.your_new_api_token') }}</h4>
                                <div class="text-muted">{{ __('messages.copy_your_new_api_token_now') }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="input-group mb-3">
                        <input type="text" class="form-control" id="apiTokenValue"
                            value="{{ session('api_token') }}" readonly>
                        <button class="btn" type="button"
                            onclick="copyToken()">{{ __('messages.copy') }}</button>
                    </div>
                @elseif ($hasApiToken)
                    <div class="alert alert-success" role="alert">
                        <div class="d-flex">
                            <div>
                                <i class="ti ti-check me-2"></i>
                            </div>
                            <div>
                                <h4 class="alert-title">{{ __('messages.api_token_exists') }}</h4>
                                <div class="text-muted">{{ __('messages.api_token_exists_info') }}</div>
                            </div>
                        </div>
                    </div>
                @else
                    <p class="text-muted">{{ __('messages.no_api_token_generated_yet') }}</p>
                @endif
            </div>
        </div>
        <div class="card-footer bg-transparent mt-auto">
            <div class="btn-list justify-content-end">
                <button type="submit" class="btn btn-success">{{ __('messages.generate_new_token') }}</button>
            </div>
        </div>
    </form>

    <script>
        function copyToken() {
            const tokenInput = document.getElementById('apiTokenValue');
            tokenInput.select();
            tokenInput.setSelectionRange(0, 99999); // For mobile devices
            document.execCommand('copy');

            // Optional: Provide feedback to the user
            const originalButtonText = document.querySelector('#apiTokenForm .btn-list button').innerText;
            const copyButton = document.querySelector('#apiTokenValue + .btn');
            copyButton.innerText = '{{ __('messages.copied') }}';
            setTimeout(() => {
                copyButton.innerText = '{{ __('messages.copy') }}';
            }, 2000);
        }
    </script>
</div>
