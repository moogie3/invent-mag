@auth
    <a href="{{ route('admin.dashboard') }}" class="btn btn-primary">
        <i class="ti ti-home me-2"></i>
        Go to Dashboard
    </a>
@else
    <a href="{{ route('admin.login') }}" class="btn btn-primary">
        <i class="ti ti-login me-2"></i>
        Go to Login
    </a>
@endauth
