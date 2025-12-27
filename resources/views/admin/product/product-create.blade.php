@extends('admin.layouts.base')

@section('title', __('messages.create_new_product'))

@section('content')
    <div class="page-wrapper">
        @include('admin.layouts.partials.product.create.header')
        @include('admin.layouts.partials.product.create.page-body')
    </div>
    @include('admin.layouts.modals.product.productmodals')
@endsection
