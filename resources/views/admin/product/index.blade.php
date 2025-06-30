@extends('admin.layouts.base')

@section('title', 'Product')

@section('content')
    <div class="page-wrapper">
        @include('admin.layouts.partials.product.index.header')
        @include('admin.layouts.partials.product.index.page-body')
    </div>
    @include('admin.layouts.modals.productmodals')
@endsection
