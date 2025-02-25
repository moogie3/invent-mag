@extends('admin.layouts.authbase')

@section('title', 'Register')

@section('content')
    <div class="card card-md">
        <div class="card-body">
            <h2 class="h2 text-center mb-4">Create New Account</h2>

            {{-- ERROR MODAL --}}
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
                            backdrop: "static",
                            keyboard: false
                        });

                        setTimeout(() => {
                            errorModal.show();
                            document.body.insertAdjacentHTML("beforeend",
                                '<div class="modal-backdrop fade show modal-backdrop-custom"></div>');
                        }, 5);

                        setTimeout(() => {
                            errorModal.hide();
                            setTimeout(() => {
                                document.querySelector(".modal-backdrop-custom")?.remove();
                            }, 5);
                        }, 1800);
                    });
                </script>
            @endif

            {{-- FORM REGISTER --}}
            <form action="{{ route('register') }}" method="POST" autocomplete="off" novalidate>
                @csrf
                <div class="mb-3">
                    <label class="form-label">Name</label>
                    <input type="text" name="name" class="form-control" placeholder="Enter name" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Email address</label>
                    <input type="email" name="email" class="form-control" placeholder="Enter email" required
                        value="{{ old('email') }}">
                </div>
                <div class="mb-3">
                    <label class="form-label">Password</label>
                    <div class="input-group input-group-flat">
                        <input type="password" name="password" class="form-control" placeholder="Password"
                            autocomplete="off" id="password" required>
                        <span class="input-group-text">
                            <a href="#" class="link-secondary" title="Show password" data-bs-toggle="tooltip"
                                id="toggle-password">
                                <i class="ti ti-eye fs-1"></i>
                            </a>
                        </span>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Confirm Password</label>
                    <input type="password" name="password_confirmation" class="form-control" placeholder="Confirm password"
                        required>
                </div>
                <div class="form-footer">
                    <button type="submit" class="btn btn-primary w-100">Create New Account</button>
                </div>
            </form>
        </div>
    </div>
    <div class="text-center text-secondary mt-3">
        Already have an account? <a href="{{ route('login') }}" tabindex="-1">Sign in</a>
    </div>
@endsection
