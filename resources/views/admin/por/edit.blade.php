@extends('admin.layouts.base')

@section('title', __('messages.edit_purchase_return'))

@section('content')
    <div class="page-wrapper">
        @include('admin.layouts.partials.por.edit.header')
        @include('admin.layouts.partials.por.edit.page-body')
    </div>

    @include('admin.layouts.modals.po.pormodals')
@endsection
