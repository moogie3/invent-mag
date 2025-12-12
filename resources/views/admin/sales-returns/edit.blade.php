@extends('admin.layouts.base')

@section('title', __('messages.edit_sales_return'))

@section('content')
    <div class="page-wrapper">
        @include('admin.layouts.partials.sales-returns.edit.header')
        @include('admin.layouts.partials.sales-returns.edit.page-body')
    </div>
@endsection
