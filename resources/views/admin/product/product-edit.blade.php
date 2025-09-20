@extends('admin.layouts.base')

@section('title', __('messages.edit_product'))

@section('content')
    <div class="page-wrapper">
        @include('admin.layouts.partials.product.edit.header')
        @include('admin.layouts.partials.product.edit.page-body')
    </div>
    @include('admin.layouts.modals.productmodals')
@endsection
