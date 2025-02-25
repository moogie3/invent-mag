@extends('admin.layouts.authbase')

@section('title', 'Forgot Password')

@section('content')

    {{-- ERROR MODAL --}}
    @if ($errors->any())
        <div class="modal fade" id="errorModal" tabindex="-1" aria-labelledby="errorModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-sm modal-dialog-centered">
                <div class="modal-content">
                    <button type="button" class="btn-close m-2" data-bs-dismiss="modal" aria-label="Close"></button>
                    <div class="modal-body text-center py-4">
                        <i class="ti ti-alert-triangle icon text-danger icon-lg mb-4"></i>
                        <h3 class="mb-3">Error!</h3>
                        <div class="text-secondary">
                            <div class="text-danger text-start text-center">
                                @foreach ($errors->all() as $error)
                                    {{ $error }}<br>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-danger w-100" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>

        <script>
            document.addEventListener("DOMContentLoaded", function() {
                var errorModalElement = document.getElementById("errorModal");
                var errorModal = new bootstrap.Modal(errorModalElement);

                setTimeout(() => {
                    errorModal.show();
                    document.body.insertAdjacentHTML("beforeend",
                        '<div class="modal-backdrop fade show modal-backdrop-custom"></div>');
                }, 5);

                setTimeout(() => {
                    errorModal.hide();
                    setTimeout(() => {
                        document.querySelector(".modal-backdrop-custom")?.remove();
                    }, 300);
                }, 1800);
            });
        </script>
    @endif

    {{-- SUCCESS MODAL --}}
    @if (session('status'))
        <div class="modal fade" id="successModal" tabindex="-1" aria-labelledby="successModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-sm modal-dialog-centered">
                <div class="modal-content">
                    <button type="button" class="btn-close m-2" data-bs-dismiss="modal" aria-label="Close"></button>
                    <div class="modal-body text-center py-4">
                        <i class="ti ti-circle-check icon text-success icon-lg mb-4"></i>
                        <h3 class="mb-3">Success!</h3>
                        <div class="text-secondary">
                            <div class="text-success text-start text-center">
                                {{ session('status') }}
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-success w-100" data-bs-dismiss="modal">OK</button>
                    </div>
                </div>
            </div>
        </div>

        <script>
            document.addEventListener("DOMContentLoaded", function() {
                var successModalElement = document.getElementById("successModal");
                var successModal = new bootstrap.Modal(successModalElement);

                setTimeout(() => {
                    successModal.show();
                    document.body.insertAdjacentHTML("beforeend",
                        '<div class="modal-backdrop fade show modal-backdrop-custom"></div>');
                }, 5);

                setTimeout(() => {
                    successModal.hide();
                    setTimeout(() => {
                        document.querySelector(".modal-backdrop-custom")?.remove();
                        window.location.href = "{{ route('login') }}";
                    }, 300);
                }, 1800);
            });
        </script>
    @endif

    {{-- FORM FORGOT PASSWORD --}}
    <form class="card card-md" action="{{ route('password.email') }}" method="POST" autocomplete="off" novalidate>
        @csrf
        <div class="card-body">
            <h2 class="card-title text-center mb-4">Forgot password</h2>
            <p class="text-secondary mb-4">Enter your email address and password reset form will be emailed to you.</p>
            <div class="mb-3">
                <label class="form-label">Email address</label>
                <input type="email" class="form-control" name="email" placeholder="Enter email" required>
            </div>
            <div class="form-footer">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="ti ti-mail fs-2 me-2"></i>
                    Send me new password
                </button>
            </div>
        </div>
    </form>

    <div class="text-center text-secondary mt-3">
        <a href="{{ route('login') }}">Send me back</a> to the sign-in screen.
    </div>

@endsection
