@extends('admin.layouts.base')

@section('title', __('messages.manual_journal_entries'))

@section('content')
    <div class="page-wrapper">
        @include('admin.layouts.partials.journal-entries.index.header')
        @include('admin.layouts.partials.journal-entries.index.page-body')
    </div>
@endsection
