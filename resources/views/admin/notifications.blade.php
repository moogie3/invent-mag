@extends('admin.layouts.base')

@section('title', 'My Notifications')

@section('content')
    <div class="page-wrapper">
        <div class="page-header">
            <div class="container-xl">
                <div class="row align-items-center">
                    <div class="col">
                        <div class="page-pretitle">Overview</div>
                        <h2 class="page-title">My Notifications</h2>
                    </div>
                </div>
            </div>
        </div>

        <div class="page-body">
            <div class="container-xl">
                <div class="card">
                    <div class="row g-0">
                        <div class="col-12 col-md-3 border-end">
                            @include('admin.layouts.menu')
                        </div>
                        <div class="col-12 col-md-9">
                            <div class="card-body">
                                <ul class="nav nav-tabs mb-4" role="tablist">
                                    <li class="nav-item" role="presentation">
                                        <a class="nav-link active" data-bs-toggle="tab" href="#tab-financial"
                                            role="tab">Purchase Order & Sales</a>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <a class="nav-link" data-bs-toggle="tab" href="#tab-lowstock" role="tab">Low
                                            Stock</a>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <a class="nav-link" data-bs-toggle="tab" href="#tab-expiring"
                                            role="tab">Expiring</a>
                                    </li>
                                </ul>

                                <div class="tab-content">
                                    {{-- Financial Tab --}}
                                    <div class="tab-pane active show" id="tab-financial" role="tabpanel">
                                        @if ($financialNotifications->count() > 0)
                                            <div class="list-group">
                                                @foreach ($financialNotifications as $item)
                                                    <a href="{{ $item['route'] }}"
                                                        class="list-group-item list-group-item-action">
                                                        <div class="d-flex justify-content-between align-items-center">
                                                            <div class="d-flex align-items-center">
                                                                <div class="status-indicator {{ $item['status_badge'] }}"
                                                                    style="width: 4px; height: 24px; border-radius: 2px; margin-right: 12px;">
                                                                </div>
                                                                <div>
                                                                    <span class="fw-bold">{{ ucfirst($item['type']) }} -
                                                                        {{ $item['label'] }}</span>
                                                                    <small class="text-muted d-block">Due on
                                                                        {{ \Carbon\Carbon::parse($item['due_date'])->format('d M Y') }}</small>
                                                                </div>
                                                            </div>
                                                            <span
                                                                class="badge {{ str_replace('text-', 'bg-', $item['status_badge']) }}-lt">
                                                                <i
                                                                    class="{{ $item['status_icon'] }} me-1"></i>{{ $item['status_text'] }}
                                                            </span>
                                                        </div>
                                                    </a>
                                                @endforeach
                                            </div>
                                        @else
                                            <div class="text-muted text-center">No financial notifications</div>
                                        @endif
                                    </div>

                                    {{-- Low Stock Tab --}}
                                    <div class="tab-pane" id="tab-lowstock" role="tabpanel">
                                        @if ($lowStockNotifications->count() > 0)
                                            <div class="list-group">
                                                @foreach ($lowStockNotifications as $item)
                                                    <a href="{{ $item['route'] }}"
                                                        class="list-group-item list-group-item-action">
                                                        <div class="d-flex justify-content-between align-items-center">
                                                            <div class="d-flex align-items-center">
                                                                <div class="status-indicator {{ $item['status_badge'] }}"
                                                                    style="width: 4px; height: 24px; border-radius: 2px; margin-right: 12px;">
                                                                </div>
                                                                <div>
                                                                    <span class="fw-bold">{{ $item['title'] }}</span>
                                                                    <small class="text-muted d-block">
                                                                        {{ $item['description'] }}
                                                                        <span class="ms-2 text-muted">
                                                                            (Threshold: {{ $item['threshold'] }})
                                                                        </span>
                                                                    </small>
                                                                </div>
                                                            </div>
                                                            <span
                                                                class="badge {{ str_replace('text-', 'bg-', $item['status_badge']) }}-lt">
                                                                <i
                                                                    class="{{ $item['status_icon'] }} me-1"></i>{{ $item['status_text'] }}
                                                            </span>
                                                        </div>
                                                    </a>
                                                @endforeach
                                            </div>
                                        @else
                                            <div class="text-muted text-center">No low stock products</div>
                                        @endif
                                    </div>

                                    {{-- Expiring Products Tab --}}
                                    <div class="tab-pane" id="tab-expiring" role="tabpanel">
                                        @if ($expiringNotifications->count() > 0)
                                            <div class="list-group">
                                                @foreach ($expiringNotifications as $item)
                                                    <a href="{{ $item['route'] }}"
                                                        class="list-group-item list-group-item-action">
                                                        <div class="d-flex justify-content-between align-items-center">
                                                            <div class="d-flex align-items-center">
                                                                <div class="status-indicator {{ $item['status_badge'] }}"
                                                                    style="width: 4px; height: 24px; border-radius: 2px; margin-right: 12px;">
                                                                </div>
                                                                <div>
                                                                    <span class="fw-bold">{{ $item['title'] }}</span>
                                                                    <small
                                                                        class="text-muted d-block">{{ $item['description'] }}</small>
                                                                </div>
                                                            </div>
                                                            <span
                                                                class="badge {{ str_replace('text-', 'bg-', $item['status_badge']) }}-lt">
                                                                <i
                                                                    class="{{ $item['status_icon'] }} me-1"></i>{{ $item['status_text'] }}
                                                            </span>
                                                        </div>
                                                    </a>
                                                @endforeach
                                            </div>
                                        @else
                                            <div class="text-muted text-center">No expiring products</div>
                                        @endif
                                    </div>
                                </div>

                                @if (!$hasNotifications)
                                    <div class="list-group-item text-muted text-center mt-4">No notifications</div>
                                @endif

                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
