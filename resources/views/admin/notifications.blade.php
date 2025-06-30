@extends('admin.layouts.base')

@section('title', 'My Notifications')

@section('content')
    <div class="page-wrapper">
        @include('admin.layouts.partials.notification.header')
        @include('admin.layouts.partials.notification.page-body')
    </div>
@endsection
