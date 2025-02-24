@extends('admin.layouts.authbase')

@section('title', 'Login')

@section('content')
    <div class="card card-md">
        <div class="card-body">
            <h2 class="h2 text-center mb-5">Login to your account</h2>

            @if ($errors->any())
                <div class="modal fade" id="errorModal" tabindex="-1" aria-labelledby="errorModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-sm modal-dialog-centered">
                        <div class="modal-content">
                            <button type="button" class="btn-close m-2" data-bs-dismiss="modal"
                                aria-label="Close"></button>
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

            <form action="{{ route('login') }}" method="POST" autocomplete="off" novalidate>
                @csrf
                <div class="mb-3">
                    <label class="form-label">Email address</label>
                    <input type="email" name="email" class="form-control" placeholder="Your email" autocomplete="off"
                        required>
                </div>
                <div class="mb-2">
                    <label class="form-label">
                        Password
                        <span class="form-label-description">
                            <a href="{{ route('password.request') }}">I forgot password</a>
                        </span>
                    </label>
                    <div class="input-group input-group-flat">
                        <input type="password" name="password" class="form-control" placeholder="Your password"
                            autocomplete="off" id="password" required>
                        <span class="input-group-text">
                            <a href="#" class="link-secondary" title="Show password" data-bs-toggle="tooltip"
                                id="toggle-password">
                                <i class="ti ti-eye fs-1"></i>
                            </a>
                        </span>
                    </div>
                </div>
                <div class="form-footer">
                    <button type="submit" class="btn btn-primary w-100">Sign in</button>
                </div>
            </form>
        </div>
    </div>
    <div class="text-center text-secondary mt-3">
        Don't have account yet? <a href="{{ route('register') }}" tabindex="-1">Sign up</a>
    </div>

@endsection
