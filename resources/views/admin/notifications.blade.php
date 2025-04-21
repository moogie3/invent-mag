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
                                        @php $hasNotifications = false; @endphp
                                        @foreach ($notifications as $item)
                                            @php
                                                $dueDate = $item['due_date'];
                                                $paymentDate = $item['payment_date'] ?? null;
                                                $today = \Carbon\Carbon::today();
                                                $diffDays = $today->diffInDays($dueDate, false);
                                                $statusBadge = 'text-blue';
                                                $statusText = 'Pending';

                                                if ($item['status'] === 'Paid') {
                                                    if ($paymentDate && $today->isSameDay($paymentDate)) {
                                                        $statusBadge = 'text-green';
                                                        $statusText = 'Paid Today';
                                                    } else {
                                                        $statusBadge = 'text-green';
                                                        $statusText = 'Paid';
                                                    }
                                                } elseif ($diffDays == 0) {
                                                    $statusBadge = 'text-red';
                                                    $statusText = 'Due Today';
                                                } elseif ($diffDays > 0 && $diffDays <= 3) {
                                                    $statusBadge = 'text-red';
                                                    $statusText = 'Due in 3 Days';
                                                } elseif ($diffDays > 3 && $diffDays <= 7) {
                                                    $statusBadge = 'text-yellow';
                                                    $statusText = 'Due in 1 Week';
                                                } elseif ($diffDays < 0) {
                                                    $statusBadge = 'text-black';
                                                    $statusText = 'Overdue';
                                                }

                                                $showNotification =
                                                    $item['status'] !== 'Paid' ||
                                                    ($item['status'] === 'Paid' &&
                                                        $paymentDate &&
                                                        $today->isSameDay($paymentDate));
                                            @endphp

                                            @if ($showNotification)
                                                <a href="{{ $item['route'] }}"
                                                    class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                                    <span>
                                                        {{ ucfirst($item['type']) }} - {{ $item['label'] }} - Due on
                                                        {{ \Carbon\Carbon::parse($item['due_date'])->format('M d, Y') }}
                                                    </span>
                                                    <span
                                                        class="badge badge-outline {{ $statusBadge }}">{{ $statusText }}</span>
                                                </a>
                                                @php $hasNotifications = true; @endphp
                                            @endif
                                        @endforeach
                                        @if (!$hasNotifications)
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
