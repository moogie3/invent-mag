@extends('admin.layouts.base')

@section('title', 'POS')

@section('content')
    <div class="page-wrapper">
        @include('admin.layouts.partials.pos.index.header')
        @include('admin.layouts.partials.pos.index.page-body')
    </div>
    @include('admin.layouts.modals.posmodals')
@endsection
