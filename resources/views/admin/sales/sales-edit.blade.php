@extends('admin.layouts.base')

@section('title', __('messages.edit_sales_order'))

@section('content')
    <div class="page-wrapper">
        @include('admin.layouts.partials.sales.edit.header')
        @include('admin.layouts.partials.sales.edit.page-body')
    </div>
    @include('admin.layouts.modals.salesmodals')
@endsection
