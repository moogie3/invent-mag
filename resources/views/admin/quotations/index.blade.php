@extends('admin.layouts.base')
@section('title', __('messages.quotations'))
@section('header')
<meta name="turbo-cache-control" content="no-cache">
@endsection
@section('content')
    <div class="page-wrapper">
        @include('admin.quotations.partials.index.header')
        @include('admin.quotations.partials.index.page-body')
    </div>
    @include('admin.layouts.modals.quotations.quotationsmodals')
    @include('admin.layouts.modals.quotations.quotationsmodals-view')
@endsection

{{-- The filter script that used to live here was DEAD CODE: admin/layouts/base.blade.php
     has no @stack('scripts'), so this never rendered, and the table it targeted had none
     of the IDs it assumed. Search/status/date/entries are now handled declaratively via
     data-server-search + data-onchange (see store-info.blade.php), which is the pattern
     every other list uses. Do not reintroduce an inline handler for these -- a second
     handler would fight the shared server-side search for control of the same rows. --}}
