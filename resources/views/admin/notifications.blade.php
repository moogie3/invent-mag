@extends('admin.layouts.base')

@section('title', 'My Notifications')

@section('content')
    <div class="page-wrapper">
        <div class="page-header">
            <div class="container-xl">
                <div class="row align-items-center">
                    <div class="col">
                        <div class="page-pretitle">
                            Overview
                        </div>
                        <h2 class="page-title">
                            My Notifications
                        </h2>
                    </div>
                </div>
            </div>
        </div>

        <div class="page-wrapper">
            <div class="page-body">
                <div class="container-xl">
                    <div class="card">
                        <div class="row g-0">
                            <div class="col-12 col-md-3 border-end">
                                @include('admin.layouts.menu')
                            </div>
                            <div class="col-12 col-md-9 d-flex flex-column">
                                <div class="card-body">
                                    <h2 class="mb-4">Upcoming Notifications</h2>
                                    <div class="list-group mb-5">
                                        @if ($hasNotifications)
                                            @foreach ($notifications as $item)
                                                <a href="{{ $item['route'] }}"
                                                    class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                                    <span>
                                                        {{ ucfirst($item['type']) }} - {{ $item['label'] }} - Due on
                                                        {{ \Carbon\Carbon::parse($item['due_date'])->format('M d, Y') }}
                                                    </span>
                                                    <span
                                                        class="badge badge-outline {{ $item['status_badge'] }}">{{ $item['status_text'] }}</span>
                                                </a>
                                            @endforeach
                                        @else
                                            <div class="list-group-item text-muted text-center">No notifications</div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
