@extends('admin.layouts.base')

@section('title', __('messages.warehouse_page_title'))

@section('content')
    <meta name="turbo-cache-control" content="no-cache">
    <div class="page-wrapper">
        @include('admin.layouts.partials.warehouse.index.header')
        @include('admin.layouts.partials.warehouse.index.page-body')
    </div>
    @include('admin.layouts.modals.warehouse.waremodals')
@endsection
