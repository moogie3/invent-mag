@extends('admin.layouts.base')

@section('title', __('messages.edit_purchase_return'))

@section('content')
    <div class="page-wrapper">
        @include('admin.layouts.partials.purchase-returns.edit.header')
        @include('admin.layouts.partials.purchase-returns.edit.page-body')
    </div>
@endsection
