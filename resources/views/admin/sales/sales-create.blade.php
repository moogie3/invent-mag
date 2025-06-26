@extends('admin.layouts.base')

@section('title', 'Create Sales')

@section('content')
    <div class="page-wrapper">
        @include('admin.layouts.partials.sales.create.header')
        @include('admin.layouts.partials.sales.create.page-body')
    </div>
    @include('admin.layouts.modals')
@endsection
