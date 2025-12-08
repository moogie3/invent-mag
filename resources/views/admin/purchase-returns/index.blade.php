@extends('admin.layouts.base')

@section('title', __('messages.model_purchase_return'))

@section('content')
    <div class="page-wrapper">
        @include('admin.layouts.partials.purchase-returns.index.header')
        @include('admin.layouts.partials.purchase-returns.index.page-body')
    </div>
    @include('admin.layouts.modals.purchase-return-modals')
@endsection
