@extends('admin.layouts.base')

@section('title', __('messages.my_notifications'))

@section('content')
    <div class="page-wrapper">
        @include('admin.layouts.partials.notification.page-body')
    </div>
    
    @include('admin.layouts.modals.general.clear-notifications-modal')

    <style>
        .notification-item.transition-all {
            transition: all 0.2s ease-in-out;
            cursor: pointer;
        }
        .notification-item.transition-all:hover {
            transform: translateY(-2px);
            box-shadow: 0 .5rem 1rem rgba(0,0,0,.08) !important;
            border-color: #ddd !important;
        }
        .mark-as-read-btn {
            opacity: 0.6;
            transition: all 0.2s ease;
        }
        .notification-item:hover .mark-as-read-btn {
            opacity: 1;
        }
        .mark-as-read-btn:hover {
            color: #d63939;
            background-color: rgba(214, 57, 57, 0.1);
            border-color: transparent;
        }
        .z-index-2 {
            z-index: 2;
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const markAsReadBtns = document.querySelectorAll('.mark-as-read-btn');
            
            markAsReadBtns.forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    const notifId = this.getAttribute('data-id');
                    const targetId = this.getAttribute('data-target');
                    const targetEl = document.getElementById(targetId);
                    
                    if (!notifId) return;

                    // Disable button to prevent double clicks
                    this.disabled = true;
                    this.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>';
                    
                    fetch(`{{ url('/admin/notifications/mark-read') }}/${notifId}`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            if (targetEl) {
                                targetEl.style.transition = 'all 0.4s ease';
                                targetEl.style.opacity = '0';
                                targetEl.style.transform = 'translateX(20px)';
                                
                                setTimeout(() => {
                                    targetEl.remove();
                                    
                                    // Also update notification count badge if exists
                                    const badge = document.querySelector('#notification-bell .badge');
                                    if (badge) {
                                        let currentCount = parseInt(badge.innerText);
                                        if (!isNaN(currentCount) && currentCount > 1) {
                                            badge.innerText = currentCount - 1;
                                        } else {
                                            badge.remove();
                                            const dot = document.getElementById('notification-dot');
                                            if (dot) dot.remove();
                                        }
                                    }
                                    
                                    // Check if there are no more notifications in the tab, show empty state
                                    ['financial', 'lowstock', 'expiring'].forEach(type => {
                                        const tab = document.getElementById(`tab-${type}`);
                                        if (tab && tab.querySelectorAll('.notification-item').length === 0 && !tab.querySelector('.empty')) {
                                            const icon = type === 'financial' ? 'ti-receipt' : (type === 'lowstock' ? 'ti-box' : 'ti-calendar-check');
                                            const msg = type === 'financial' ? 'No financial notifications' : (type === 'lowstock' ? 'No low stock products' : 'No expiring products');
                                            
                                            tab.innerHTML = `
                                                <div class="empty py-5">
                                                    <div class="empty-img"><i class="ti ${icon} text-muted opacity-50" style="font-size: 6rem;"></i></div>
                                                    <p class="empty-title mt-3 fs-3">${msg}</p>
                                                    <p class="empty-subtitle text-muted">You're all caught up here.</p>
                                                </div>
                                            `;
                                        }
                                    });
                                }, 400);
                            }
                        }
                    })
                    .catch(error => {
                        console.error('Error marking as read:', error);
                        this.disabled = false;
                        this.innerHTML = '<i class="ti ti-x"></i>';
                    });
                });
            });

            const clearAllBtn = document.getElementById('clear-all-btn');
            const confirmClearAllBtn = document.getElementById('confirm-clear-all-btn');
            
            if (clearAllBtn) {
                clearAllBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    // Just open the modal
                    const modal = new bootstrap.Modal(document.getElementById('clearAllNotificationsModal'));
                    modal.show();
                });
            }

            if (confirmClearAllBtn) {
                confirmClearAllBtn.addEventListener('click', function(e) {
                    const btn = this;
                    const originalText = btn.innerHTML;
                    btn.disabled = true;
                    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> Clearing...';

                    fetch('{{ route('admin.notifications.clear-all') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            window.location.reload();
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        btn.disabled = false;
                        btn.innerHTML = originalText;
                    });
                });
            }
        });
    </script>
@endsection
