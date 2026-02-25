@extends('admin.layouts.base')

@section('title', __('messages.model_purchase_return'))

@section('content')
    <div class="page-wrapper">
        @include('admin.layouts.partials.por.index.header')
        @include('admin.layouts.partials.por.index.page-body')
    </div>
    @include('admin.layouts.modals.po.pormodals')
@endsection
