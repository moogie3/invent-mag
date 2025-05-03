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
                                                    class="list-group-item list-group-item-action">
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <div class="d-flex align-items-center">
                                                            <div class="status-indicator {{ $item['status_badge'] }}"
                                                                style="width: 4px; height: 24px; border-radius: 2px; margin-right: 12px;">
                                                            </div>
                                                            <div>
                                                                <span class="d-block fw-bold">
                                                                    {{ ucfirst($item['type']) }} - {{ $item['label'] }}
                                                                </span>
                                                                <small class="text-muted">
                                                                    Due on
                                                                    {{ \Carbon\Carbon::parse($item['due_date'])->format('d M Y') }}
                                                                </small>
                                                            </div>
                                                        </div>
                                                        <span
                                                            class="badge {{ str_replace('text-', 'bg-', $item['status_badge']) }}-lt">
                                                            <i class="{{ $item['status_icon'] }} me-1"></i>
                                                            {{ $item['status_text'] }}
                                                        </span>
                                                    </div>
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
