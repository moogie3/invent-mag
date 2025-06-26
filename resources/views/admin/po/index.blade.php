@extends('admin.layouts.base')

@section('title', 'Purchase Order')

@section('content')
    <div class="page-wrapper">
        @include('admin.layouts.partials.po.index.header')
        @include('admin.layouts.partials.po.index.page-body')
    </div>
    @include('admin.layouts.modals.pomodals')
@endsection
