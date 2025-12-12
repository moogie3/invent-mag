@extends('admin.layouts.base')

@section('title', __('messages.sales_return_details'))

@section('content')
    <div class="page-wrapper">
        @include('admin.layouts.partials.sales-returns.show.header')
        @include('admin.layouts.partials.sales-returns.show.page-body')
    </div>
@endsection