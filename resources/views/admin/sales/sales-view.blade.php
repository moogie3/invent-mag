@extends('admin.layouts.base')

@section('title', 'Sales Order')

@section('content')
    <div class="page-wrapper">
        @include('admin.layouts.partials.sales.view.header')
        @include('admin.layouts.partials.sales.view.page-body')
    </div>
@endsection
