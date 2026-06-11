@extends('admin.layouts.base')
@section('title', __('messages.quotation_details'))
@section('content')
    <div class="page-wrapper">
        @include('admin.quotations.partials.view.header')
        @include('admin.quotations.partials.view.page-body')
    </div>
    @include('admin.layouts.modals.quotations.quotationsmodals')
@endsection
