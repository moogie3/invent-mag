@extends('admin.layouts.base')

@section('title', __('messages.edit_journal_entry'))

@section('content')
    <div class="page-wrapper">
        @include('admin.layouts.partials.journal-entries.edit.header')
        @include('admin.layouts.partials.journal-entries.edit.page-body')
    </div>
@endsection
