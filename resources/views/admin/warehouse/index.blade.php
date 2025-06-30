@extends('admin.layouts.base')

@section('title', 'Warehouse')

@section('content')
    <div class="page-wrapper">
        @include('admin.layouts.partials.warehouse.index.header')
        @include('admin.layouts.partials.warehouse.index.page-body')
    </div>
    @include('admin.layouts.modals.waremodals')
@endsection
