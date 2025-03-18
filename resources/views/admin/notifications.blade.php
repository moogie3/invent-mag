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
                                    <h2 class="mb-4">Due Invoice</h2>
                                    <div class="list-group">
                                        @php
                                            $hasNotifications = false;
                                            $today = now();
                                        @endphp
                                        @foreach ($purchaseOrders as $po)
                                            @php
                                                $dueDate = $po->due_date;
                                                $paymentDate = $po->payment_date;
                                                $diffDays = $today->diffInDays($dueDate, false);
                                                $statusBadge = 'bg-info'; // Default
                                                $statusText = 'Pending'; // Default

                                                if ($po->status === 'Paid') {
                                                    if ($paymentDate && $today->isSameDay($paymentDate)) {
                                                        $statusBadge = 'bg-success';
                                                        $statusText = 'Paid Today';
                                                    } else {
                                                        $statusBadge = 'bg-success';
                                                        $statusText = 'Paid';
                                                    }
                                                } elseif ($diffDays == 0) {
                                                    $statusBadge = 'bg-danger';
                                                    $statusText = 'Due Today';
                                                } elseif ($diffDays > 0 && $diffDays <= 3) {
                                                    $statusBadge = 'bg-danger';
                                                    $statusText = 'Due in 3 Days';
                                                } elseif ($diffDays > 3 && $diffDays <= 7) {
                                                    $statusBadge = 'bg-warning';
                                                    $statusText = 'Due in 1 Week';
                                                } elseif ($diffDays < 0) {
                                                    $statusBadge = 'bg-dark'; // Use a darker color for visibility
                                                    $statusText = 'Overdue'; // Ensure this text is always set
                                                }
                                            @endphp

                                            @if ($po->status !== 'Paid' || ($po->status === 'Paid' && $today->isSameDay($paymentDate)))
                                                <a href="{{ route('admin.po.edit', ['id' => $po->id]) }}"
                                                    class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                                    <span>PO #{{ $po->id }} - Due on
                                                        {{ $po->due_date->format('M d, Y') }}</span>
                                                    <span
                                                        class="badge {{ $statusBadge }}">{{ $statusText ?: 'Overdue' }}</span>
                                                    <!-- Ensure text is always set -->
                                                </a>
                                                @php $hasNotifications = true; @endphp
                                            @endif
                                        @endforeach


                                        @if (!$hasNotifications)
                                            <div class="list-group-item text-muted text-center">No new notifications</div>
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
