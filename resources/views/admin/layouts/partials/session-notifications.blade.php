@php
    $isAuthPage = request()->is(
        'admin/login',
        'admin/register',
        'forgot-password',
        'admin/login/post',
        'admin/register/post',
        'password/email',
    );
@endphp

@if (session('success'))
    @if ($isAuthPage)
        <div class="modal modal-blur fade" id="statusModal" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
                <div class="modal-content">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    <div class="modal-status bg-success"></div>
                    <div class="modal-body text-center py-4">
                        <i class="ti ti-check mb-2" style="font-size: 2rem;"></i>
                        <h3>Success</h3>
                        <div class="text-muted">{{ session('success') }}</div>
                    </div>
                    <div class="modal-footer">
                        <div class="w-100">
                            <button type="button" class="btn btn-primary w-100" data-bs-dismiss="modal">OK</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                var statusModal = new bootstrap.Modal(document.getElementById('statusModal'));
                statusModal.show();
            });
        </script>
    @else
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                window.showToast('Success', '{{ session('success') }}', 'success');
            });
        </script>
    @endif
@endif

@if ($errors->any())
    @if ($isAuthPage)
        <div class="modal modal-blur fade" id="statusModal" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
                <div class="modal-content">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    <div class="modal-status bg-danger"></div>
                    <div class="modal-body text-center py-4">
                        <i class="ti ti-alert-circle text-danger mb-2" style="font-size: 2rem;"></i>
                        <h3>Error</h3>
                        <div class="text-muted">{{ $errors->first() }}</div>
                    </div>
                    <div class="modal-footer">
                        <div class="w-100">
                            <button type="button" class="btn btn-primary w-100" data-bs-dismiss="modal">OK</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                var statusModal = new bootstrap.Modal(document.getElementById('statusModal'));
                statusModal.show();
            });
        </script>
    @else
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                window.showToast('Error', '{{ $errors->first() }}', 'error');
            });
        </script>
    @endif
@endif
