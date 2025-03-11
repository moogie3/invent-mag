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
                                <div class="card-body">
                                    <h4 class="subheader">Business settings</h4>
                                    <div class="list-group list-group-transparent">
                                        <a href="{{ route('admin.setting.notifications') }}"
                                            class="list-group-item list-group-item-action d-flex align-items-center active">My
                                            Notifications</a>
                                        <a href="{{ route('admin.setting.profile.edit') }}"
                                            class="list-group-item list-group-item-action d-flex align-items-center">My
                                            Account</a>
                                        <a href="{{ route('admin.setting.currency.edit') }}"
                                            class="list-group-item list-group-item-action d-flex align-items-center">Currency
                                            Settings</a>
                                        <a href="{{ route('admin.setting.unit') }}"
                                            class="list-group-item list-group-item-action d-flex align-items-center">Units
                                            Settings</a>
                                        <a href="{{ route('admin.setting.category') }}"
                                            class="list-group-item list-group-item-action d-flex align-items-center">Category
                                            Settings</a>
                                    </div>
                                </div>
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
                                                $diffDays = $today->diffInDays($dueDate, false);
                                            @endphp
                                            @if ($diffDays <= 7 && $po->status !== 'Paid')
                                                <a href="{{ route('admin.po.edit', ['id' => $po->id]) }}"
                                                    class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                                    <span>PO #{{ $po->id }} - Due on
                                                        {{ $po->due_date->format('M d, Y') }}</span>
                                                    <span class="badge bg-danger">Due Soon</span>
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
