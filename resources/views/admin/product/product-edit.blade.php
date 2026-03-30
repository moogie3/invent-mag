@extends('admin.layouts.base')

@section('title', __('messages.edit_product'))

@section('header')
<meta name="turbo-cache-control" content="no-cache">
@endsection

@section('content')
    <div class="page-wrapper">
        @include('admin.layouts.partials.product.edit.header')
        @include('admin.layouts.partials.product.edit.page-body')
    </div>
    @include('admin.layouts.modals.product.productmodals')
@endsection
