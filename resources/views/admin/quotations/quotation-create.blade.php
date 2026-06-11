@extends('admin.layouts.base')
@section('title', __('messages.create_quotation'))
@section('content')
    <div class="page-wrapper">
        @include('admin.quotations.partials.create.header')
        @include('admin.quotations.partials.create.page-body')
    </div>
    @include('admin.layouts.modals.quotations.quotationsmodals')
@endsection


