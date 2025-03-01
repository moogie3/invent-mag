@extends('admin.layouts.base')

@section('title', 'Currency Settings')

@section('content')
    <div class="page-wrapper">
        <div class="page-header">
            <div class="container-xl">
                <div class="row align-items-center">
                    <div class="col">
                        <div class="page-pretitle">
                            Overview
                        </div>
                        <h2 class="page-title">
                            Settings
                        </h2>
                    </div>
                </div>
            </div>
        </div>

        <div class="page-body">
            <div class="container-xl">
                <div class="row row-deck row-cards">
                    <div class="col-md-12">
                        <div class="card card-primary">
                            <div class="card-header">
                                <h2>Profile Settings</h2>
                            </div>
                            <div class="card-body">
                                <form action="{{ route('admin.profile.update') }}" method="POST"
                                    enctype="multipart/form-data">
                                    @method('PUT')
                                    @csrf
                                    <div class="mb-3">
                                        <label>Avatar</label><br>
                                        <img src="{{ auth()->user()->avatar ? asset('storage/' . auth()->user()->avatar) : asset('default-avatar.png') }}"
                                            alt="Avatar" class="img-thumbnail mb-2" width="100">
                                        <input type="file" name="avatar" class="form-control">
                                    </div>
                                    <div class="mb-3">
                                        <label>Name</label>
                                        <input type="text" name="name" class="form-control"
                                            value="{{ auth()->user()->name }}" required>
                                    </div>
                                    <div class="mb-3">
                                        <label>Email</label>
                                        <input type="email" name="email" class="form-control"
                                            value="{{ auth()->user()->email }}" required>
                                    </div>
                                    <div class="mb-3">
                                        <label>Store Name</label>
                                        <input type="shopname" name="shopname" class="form-control"
                                            value="{{ auth()->user()->shopname }}" required>
                                    </div>
                                    <div class="mb-3">
                                        <label>Address</label>
                                        <input type="address" name="address" class="form-control"
                                            value="{{ auth()->user()->address }}" required>
                                    </div>
                                    <div class="mb-3">
                                        <label>New Password</label>
                                        <input type="password" name="password" class="form-control"
                                            placeholder="Enter new password">
                                    </div>
                                    <div class="mb-3">
                                        <label>Confirm Password</label>
                                        <input type="password" name="password_confirmation" class="form-control"
                                            placeholder="Confirm new password">
                                    </div>
                                    <button type="submit" class="btn btn-primary">Update Profile</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
s
