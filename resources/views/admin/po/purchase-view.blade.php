@extends('admin.layouts.base')

@section('title', __('messages.purchase_order'))

@section('content')
    <div class="page-wrapper">
        @include('admin.layouts.partials.po.view.header')
        @include('admin.layouts.partials.po.view.page-body')
    </div>
@endsection
