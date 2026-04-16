@extends('admin.layouts.base')

@section('title', __('messages.profile_settings'))

@section('content')
    <div class="page-wrapper">
        <div class="page-body">
                <div class="{{ $containerClass ?? "container-xl" }}">
                <div class="card border-0 shadow-sm rounded-3">
                    <div class="card-body d-flex justify-content-between align-items-center py-3">
                        <h2 class="mb-0"><i class="ti ti-user-cog me-2"></i>{{ __('messages.account_settings') }}</h2>
                        <a href="#" class="text-muted trigger-tutorial-modal" data-bs-toggle="tooltip" title="{{ __('messages.tutorial') }}">
                            <i class="ti ti-help-circle fs-3"></i>
                        </a>
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