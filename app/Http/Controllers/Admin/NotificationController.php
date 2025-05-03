<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Purchase;
use App\Models\Sales;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\View;

class NotificationController extends Controller
{
    public function index()
    {
        $notifications = $this->getDueNotifications();
        $hasNotifications = $notifications->isNotEmpty();

        return view('admin.notifications', compact('notifications', 'hasNotifications'));
    }

    public function count()
    {
        $poCount = Purchase::where('due_date', '<=', Carbon::now()->addDays(7))
            ->where('status', '!=', 'Paid')
            ->count();

        $salesCount = Sales::where('due_date', '<=', Carbon::now()->addDays(7))
            ->where('status', '!=', 'Paid')
            ->count();

        return response()->json(['count' => $poCount + $salesCount]);
    }

    /**
     * Get detailed notifications for AJAX requests
     */
    public function getNotifications()
    {
        $notifications = $this->getDueNotifications();

        // Convert to simple array structure for the dropdown
        $simpleNotifications = $notifications->map(function($item) {
            return [
                'id' => $item['id'],
                'title' => $item['title'],
                'description' => $item['description'],
                'urgency' => $item['urgency'],
                'route' => $item['route']
            ];
        });

        return response()->json(['notifications' => $simpleNotifications->values()->all()]);
    }

    /**
     * Show a specific notification
     */
    public function view($id)
    {
        // Determine if the ID belongs to a purchase order or sales
        $idParts = explode('-', $id);
        $type = $idParts[0];
        $actualId = $idParts[1] ?? null;

        if (!$actualId) {
            return redirect()->route('admin.notifications')->with('error', 'Invalid notification ID');
        }

        if ($type === 'po') {
            return redirect()->route('admin.po.view', ['id' => $actualId]);
        } elseif ($type === 'sale') {
            return redirect()->route('admin.sales.view', ['id' => $actualId]);
        }

        return redirect()->route('admin.notifications')->with('error', 'Unknown notification type');
    }

    /**
     * Mark a notification as read
     */
    public function markAsRead($id)
    {
        // You can implement this method to mark notifications as read
        // For example, you could update a 'read_at' column in a notifications table
        // Or add the notification ID to a user's "read notifications" list

        return response()->json(['success' => true]);
    }

    /**
     * Get status information based on status and due date
     *
     * @param string $status Current status
     * @param Carbon $dueDate Due date
     * @param Carbon|null $paymentDate Payment date
     * @return array Status information with badge and text
     */
    private function getStatusInfo($status, $dueDate, $paymentDate = null)
    {
        $today = Carbon::today();
        $diffDays = $today->diffInDays($dueDate, false); // negative means past due

        if ($status === 'Paid') {
            if ($paymentDate && $today->isSameDay($paymentDate)) {
                return [
                    'badge' => 'text-green',
                    'text' => 'Paid Today',
                    'icon' => 'ti ti-check',
                ];
            } else {
                return [
                    'badge' => 'text-green',
                    'text' => 'Paid',
                    'icon' => 'ti ti-check',
                ];
            }
        } elseif ($diffDays == 0) {
            return [
                'badge' => 'text-orange',
                'text' => 'Due Today',
                'icon' => 'ti ti-alert-triangle',
            ];
        } elseif ($diffDays > 0 && $diffDays <= 3) {
            return [
                'badge' => 'text-orange',
                'text' => "Due in {$diffDays} Days",
                'icon' => 'ti ti-calendar-event',
            ];
        } elseif ($diffDays > 3 && $diffDays <= 7) {
            return [
                'badge' => 'text-yellow',
                'text' => 'Due in 1 Week',
                'icon' => 'ti ti-calendar',
            ];
        } elseif ($diffDays < 0) {
            return [
                'badge' => 'text-red',
                'text' => 'Overdue',
                'icon' => 'ti ti-alert-circle',
            ];
        } else {
            return [
                'badge' => 'text-blue',
                'text' => 'Pending',
                'icon' => 'ti ti-clock',
            ];
        }
    }

    /**
     * Get notifications for use in views
     */
    public function getDueNotifications()
    {
        $today = Carbon::today();

        $poNotifications = Purchase::where('due_date', '<=', Carbon::now()->addDays(7))
            ->orderBy('due_date', 'asc')
            ->get()
            ->map(function ($po) use ($today) {
                $daysRemaining = $today->diffInDays($po->due_date, false);

                // Get status info using the shared function
                $statusInfo = $this->getStatusInfo($po->status, $po->due_date, $po->payment_date);

                // Determine if notification should be shown
                $showNotification = $po->status !== 'Paid' || ($po->status === 'Paid' && $po->payment_date && $today->isSameDay($po->payment_date));

                return [
                    'id' => 'po-' . $po->id,
                    'title' => "Due Purchase: PO #{$po->id}",
                    'description' => "Due on {$po->due_date->format('M d, Y')}",
                    'due_date' => $po->due_date,
                    'payment_date' => $po->payment_date,
                    'status' => $po->status,
                    'urgency' => $daysRemaining <= 2 ? 'high' : ($daysRemaining <= 5 ? 'medium' : 'low'),
                    'days_remaining' => $daysRemaining,
                    'route' => route('admin.po.edit', ['id' => $po->id]),
                    'type' => 'purchase',
                    'label' => 'PO Invoice #' . $po->id,
                    'status_badge' => $statusInfo['badge'],
                    'status_text' => $statusInfo['text'],
                    'status_icon' => $statusInfo['icon'],
                    'show_notification' => $showNotification,
                ];
            });

        $salesNotifications = Sales::where('due_date', '<=', Carbon::now()->addDays(7))
            ->orderBy('due_date', 'asc')
            ->get()
            ->map(function ($sale) use ($today) {
                $daysRemaining = $today->diffInDays($sale->due_date, false);

                // Get status info using the shared function
                $statusInfo = $this->getStatusInfo($sale->status, $sale->due_date, $sale->payment_date);

                // Determine if notification should be shown
                $showNotification = $sale->status !== 'Paid' || ($sale->status === 'Paid' && $sale->payment_date && $today->isSameDay($sale->payment_date));

                return [
                    'id' => 'sale-' . $sale->id,
                    'title' => "Due Invoice: #{$sale->invoice}",
                    'description' => "Due on {$sale->due_date->format('M d, Y')}",
                    'due_date' => $sale->due_date,
                    'payment_date' => $sale->payment_date,
                    'status' => $sale->status,
                    'urgency' => $daysRemaining <= 2 ? 'high' : ($daysRemaining <= 5 ? 'medium' : 'low'),
                    'days_remaining' => $daysRemaining,
                    'route' => route('admin.sales.edit', ['id' => $sale->id]),
                    'type' => 'sales',
                    'label' => 'Invoice #' . $sale->invoice,
                    'status_badge' => $statusInfo['badge'],
                    'status_text' => $statusInfo['text'],
                    'status_icon' => $statusInfo['icon'],
                    'show_notification' => $showNotification,
                ];
            });

        // Combine and filter out notifications that shouldn't be shown
        $allNotifications = $poNotifications
            ->concat($salesNotifications)
            ->filter(function ($item) {
                return $item['show_notification'];
            })
            ->sortBy([['days_remaining', 'asc']]);

        return $allNotifications;
    }

    /**
     * Share notifications with all views
     * This method should only be called once per request
     */
    public function shareNotificationsWithAllViews()
    {
        // Get notifications without any transformations to avoid duplications
        $notifications = $this->getDueNotifications();
        $notificationCount = $notifications->count();

        // Share directly with view without additional transformations
        View::share('notificationCount', $notificationCount);
        View::share('notifications', $notifications);

        return true;
    }
}
