@if(session('impersonating_tenant_id'))
<div class="alert alert-warning alert-dismissible mb-0 shadow-sm border-0 border-bottom d-flex align-items-center justify-content-between" role="alert" style="border-radius: 0;">
    <div class="{{ $containerClass ?? 'container-xl' }} d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center">
            <i class="ti ti-eye text-warning fs-3 me-2"></i>
            <span>
                <strong>Impersonating:</strong>
                {{ session('impersonating_tenant_name') }}
                <span class="text-muted mx-1">—</span>
                logged in as <strong>{{ session('impersonating_user_name') }}</strong>
            </span>
        </div>
        <div class="d-flex align-items-center gap-2">
            <form method="POST" action="{{ route('admin.stop-impersonating') }}" class="mb-0" data-turbo="false">
                @csrf
                <button type="submit" class="btn btn-sm btn-warning text-white rounded-pill px-3 shadow-sm">
                    <i class="ti ti-logout me-1"></i>Stop Impersonating
                </button>
            </form>
        </div>
    </div>
</div>
@endif
