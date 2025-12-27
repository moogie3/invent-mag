@extends('admin.layouts.base')

@section('title', __('messages.purchase_order'))

@section('content')
    <div class="page-wrapper">
        @include('admin.layouts.partials.po.index.header')
        @include('admin.layouts.partials.po.index.page-body')
    </div>
    @include('admin.layouts.modals.po.pomodals')
@endsection
