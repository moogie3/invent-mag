@extends('admin.layouts.base')

@section('title', __('messages.journal_entry_details'))

@section('content')
    <div class="page-wrapper">
        @include('admin.layouts.partials.journal-entries.show.header')
        @include('admin.layouts.partials.journal-entries.show.page-body')
    </div>
@endsection
