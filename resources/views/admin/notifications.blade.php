@extends('admin.layouts.base')

@section('title', 'Notifications')

@section('content')
    <div class="nav-item dropdown me-3">
        <a href="#" class="nav-link px-2 position-relative" data-bs-toggle="dropdown">
            <i class="ti ti-bell fs-2"></i>
            @if ($purchaseOrders->isNotEmpty())
                <span
                    class="position-absolute top-0 start-100 translate-middle p-1 bg-danger border border-light rounded-circle"></span>
            @endif
        </a>
        <div class="dropdown-menu dropdown-menu-end dropdown-menu-arrow">
            <h6 class="dropdown-header">Notifications</h6>

            @forelse ($purchaseOrders as $po)
                <a href="{{ route('admin.po') }}" class="dropdown-item d-flex align-items-center">
                    <span class="badge bg-danger me-2"></span>
                    Due Note: PO #{{ $po->id }} - {{ \Carbon\Carbon::parse($po->due_date)->format('M d, Y') }}
                </a>
            @empty
                <div class="dropdown-item text-muted text-center">No new notifications</div>
            @endforelse
        </div>
    </div>
@endsection
