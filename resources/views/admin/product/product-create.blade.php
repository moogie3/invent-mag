@extends('admin.layouts.base')

@section('title', 'Create New Product')

@section('content')
    <div class="page-wrapper">
        @include('admin.layouts.partials.product.create.header')
        @include('admin.layouts.partials.product.create.page-body')
    </div>
    @include('admin.layouts.modals.productmodals')
@endsection
