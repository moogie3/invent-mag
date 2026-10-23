@extends('admin.layouts.base')

@section('title', 'POS')

@section('content')
    <div class="page-wrapper">
        @include('admin.layouts.partials.pos.index.header', ['containerClass' => 'container-fluid'])
        @include('admin.layouts.partials.pos.index.page-body', ['containerClass' => 'container-fluid'])
    </div>
    @include('admin.layouts.modals.pos.posmodals')
@endsection
