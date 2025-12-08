@extends('admin.layouts.base')

@section('title', __('messages.new_sales_return'))

@section('content')
    <div class="page-wrapper">
        @include('admin.layouts.partials.sales-returns.create.header')
        @include('admin.layouts.partials.sales-returns.create.page-body')
    </div>
@endsection
