@extends('admin.layouts.authbase')

@section('title', 'Forgot Password')

@section('content')

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
                var errorModal = new bootstrap.Modal(errorModalElement, {
                    backdrop: "static", // Prevents clicking outside to close immediately
                    keyboard: false
                });

                // Show modal with a slight delay for smoothness
                setTimeout(() => {
                    errorModal.show();
                    document.body.insertAdjacentHTML("beforeend",
                        '<div class="modal-backdrop fade show modal-backdrop-custom"></div>');
                }, 5);

                // Hide modal after 1.8 seconds with fade effect
                setTimeout(() => {
                    errorModal.hide();
                    setTimeout(() => {
                        document.querySelector(".modal-backdrop-custom")?.remove();
                    }, 5); // Allow fade-out effect
                }, 1800);
            });
        </script>
    @endif

    <form class="card card-md" action="{{ route('password.email') }}" method="POST" autocomplete="off" novalidate>
        @csrf
        <div class="card-body">
            <h2 class="card-title text-center mb-4">Forgot password</h2>
            <p class="text-secondary mb-4">Enter your email address and password reset form will be emailed
                to you.</p>
            <div class="mb-3">
                <label class="form-label">Email address</label>
                <input type="email" class="form-control" placeholder="Enter email">
            </div>
            <div class="form-footer">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="ti ti-mail fs-2 me-2"></i>
                    Send me new password
            </div>
        </div>
    </form>
    <div class="text-center text-secondary mt-3">
        <a href="{{ route('login') }}">Send me back</a> to the sign in screen.
    </div>
@endsection
