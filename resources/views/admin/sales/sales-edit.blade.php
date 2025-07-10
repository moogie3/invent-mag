@extends('admin.layouts.base')

@section('title', 'Sales Order')

@section('content')
    <div class="page-wrapper">
        @include('admin.layouts.partials.sales.edit.header')
        @include('admin.layouts.partials.sales.edit.page-body')
    </div>
@endsection
