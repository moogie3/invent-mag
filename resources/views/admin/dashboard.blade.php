@extends('admin.layouts.base')

@section('title', __('messages.dashboard'))

@section('content')
    <div class="page-wrapper">
        <!-- Page Header -->
        @include('admin.layouts.partials.dashboard.dashboard-header')

        <!-- Page Body -->
        <div class="page-body">
            <div class="container-xl">
                <!-- Key Metrics Cards -->
                @include('admin.layouts.partials.dashboard.key-metrics')

                <!-- Main Content Section -->
                @include('admin.layouts.partials.dashboard.main-content')
            </div>
        </div>
    </div>
    @include('admin.layouts.partials.dashboard.dashboard-scripts')
@endsection