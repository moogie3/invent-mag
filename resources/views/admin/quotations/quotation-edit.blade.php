@extends('admin.layouts.base')
@section('title', __('messages.edit_quotation'))
@section('content')
    <div class="page-wrapper">
        @include('admin.quotations.partials.edit.header')
        @include('admin.quotations.partials.edit.page-body')
    </div>
    @include('admin.layouts.modals.quotations.quotationsmodals')
@endsection


