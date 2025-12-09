@extends('admin.layouts.base')

@section('title', __('messages.purchase_return_details'))

@section('content')
    <div class="page-wrapper">
        @include('admin.layouts.partials.purchase-returns.show.header')
        @include('admin.layouts.partials.purchase-returns.show.page-body')
    </div>
@endsection