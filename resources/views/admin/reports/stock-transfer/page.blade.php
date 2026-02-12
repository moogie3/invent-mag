@extends('admin.layouts.base')

@section('title', __('messages.stock_transfer'))

@section('content')
    <div class="page-wrapper">
        @include('admin.reports.stock-transfer.header')
        @include('admin.reports.stock-transfer.page-body')
    </div>
@endsection
