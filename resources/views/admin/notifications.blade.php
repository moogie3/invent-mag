@extends('admin.layouts.base')

@section('title', __('messages.my_notifications'))

@section('content')
    <div class="page-wrapper">
        @include('admin.layouts.partials.notification.page-body')
    </div>
@endsection
