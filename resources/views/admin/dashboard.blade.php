@extends('admin.layouts.base')

@section('title', __('messages.dashboard'))

@section('content')
    <div class="page-wrapper">
        <!-- Page Header -->
        @include('admin.layouts.partials.dashboard.dashboard-header')

        <!-- Page Body -->
        <div class="page-body">
            <div class="{{ $containerClass ?? 'container-xl' }}">
                <div class="row row-deck row-cards">
                    <!-- Key Metrics Cards -->
                    @include('admin.layouts.partials.dashboard.key-metrics')
                    
                    @if(isset($salesForecast) && $salesForecast !== null)
                        <!-- Sales Forecast Chart -->
                        @include('admin.layouts.partials.dashboard.sales-forecast')
                    @endif
                    
                    @php
                        $tenant = app('currentTenant');
                    @endphp
                    
                    @if($tenant && $tenant->hasFeature('accounting'))
                        <!-- Accounting Summary -->
                        @include('admin.layouts.partials.dashboard.accounting-summary')
                    @endif

                    <!-- Main Content Section -->
                    @include('admin.layouts.partials.dashboard.main-content')
                </div>
            </div>
        </div>
    </div>
    @include('admin.layouts.partials.dashboard.dashboard-scripts')
@endsection
