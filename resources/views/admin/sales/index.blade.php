@extends('admin.layouts.base')

@section('title', __('messages.model_sales'))

@section('content')
    <div class="page-wrapper">
        @include('admin.layouts.partials.sales.index.header')
        @include('admin.layouts.partials.sales.index.page-body')
    </div>
    @include('admin.layouts.modals.salesmodals')
@endsection
