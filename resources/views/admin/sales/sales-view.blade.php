@extends('admin.layouts.base')

@section('title', __('messages.sales_order_details'))

@section('content')
    <div class="page-wrapper">
        @include('admin.layouts.partials.sales.view.header')
        @include('admin.layouts.partials.sales.view.page-body')
    </div>
@endsection
