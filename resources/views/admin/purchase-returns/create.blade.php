@extends('admin.layouts.base')

@section('title', __('messages.new_purchase_return'))

@section('content')
    <div class="page-wrapper">
        @include('admin.layouts.partials.purchase-returns.create.header')
        @include('admin.layouts.partials.purchase-returns.create.page-body')
    </div>
@endsection
