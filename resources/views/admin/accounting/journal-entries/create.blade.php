@extends('admin.layouts.base')

@section('title', __('messages.create_journal_entry'))

@section('content')
    <div class="page-wrapper">
        @include('admin.layouts.partials.journal-entries.create.header')
        @include('admin.layouts.partials.journal-entries.create.page-body')
    </div>
@endsection
