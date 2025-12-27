@extends('admin.layouts.base')

@section('title', __('messages.create_sales_order'))

@section('content')
    <div class="page-wrapper">
        @include('admin.layouts.partials.sales.create.header')
        @include('admin.layouts.partials.sales.create.page-body')
    </div>
@endsection
