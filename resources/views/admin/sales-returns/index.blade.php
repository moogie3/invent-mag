@extends('admin.layouts.base')

@section('title', __('messages.model_sales_return'))

@section('content')
    <div class="page-wrapper">
        @include('admin.layouts.partials.sales-returns.index.header')
        @include('admin.layouts.partials.sales-returns.index.page-body')
    </div>
    @include('admin.layouts.modals.sales-return-modals')
@endsection
