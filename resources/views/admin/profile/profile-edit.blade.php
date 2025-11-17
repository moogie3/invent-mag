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
                                @include('admin.layouts.partials.profile._profile_settings')
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection