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
        return response()->json(['notifications' => $notifications->values()->all()]);
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
     * Get notifications for use in views
     */
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
            $diffDays = $today->diffInDays($po->due_date, false);

            // Calculate status badge and text
            $statusBadge = 'text-blue';
            $statusText = 'Pending';

            if ($po->status === 'Paid') {
                if ($po->payment_date && $today->isSameDay($po->payment_date)) {
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

            // Determine if notification should be shown
            $showNotification =
                $po->status !== 'Paid' ||
                ($po->status === 'Paid' && $po->payment_date && $today->isSameDay($po->payment_date));

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
                'status_badge' => $statusBadge,
                'status_text' => $statusText,
                'show_notification' => $showNotification
            ];
        });

    $salesNotifications = Sales::where('due_date', '<=', Carbon::now()->addDays(7))
        ->orderBy('due_date', 'asc')
        ->get()
        ->map(function ($sale) use ($today) {
            $daysRemaining = $today->diffInDays($sale->due_date, false);
            $diffDays = $today->diffInDays($sale->due_date, false);

            // Calculate status badge and text
            $statusBadge = 'text-blue';
            $statusText = 'Pending';

            if ($sale->status === 'Paid') {
                if ($sale->payment_date && $today->isSameDay($sale->payment_date)) {
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

            // Determine if notification should be shown
            $showNotification =
                $sale->status !== 'Paid' ||
                ($sale->status === 'Paid' && $sale->payment_date && $today->isSameDay($sale->payment_date));

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
                'status_badge' => $statusBadge,
                'status_text' => $statusText,
                'show_notification' => $showNotification
            ];
        });

    // Combine and filter out notifications that shouldn't be shown
    $allNotifications = $poNotifications->concat($salesNotifications)
        ->filter(function($item) {
            return $item['show_notification'];
        })
        ->sortBy([['days_remaining', 'asc']]);

    return $allNotifications;
}

    /**
     * Share notifications with all views
     */
    public function shareNotificationsWithAllViews()
    {
        $notifications = $this->getDueNotifications()->map(function ($item) {
            if (str_starts_with($item['id'], 'po-')) {
                $id = (int) str_replace('po-', '', $item['id']);
                $model = Purchase::find($id);
            } elseif (str_starts_with($item['id'], 'sale-')) {
                $id = (int) str_replace('sale-', '', $item['id']);
                $model = Sales::find($id);
            } else {
                return (object) $item;
            }

            return (object) array_merge($item, [
                'due_date' => $model->due_date,
                'payment_date' => $model->payment_date,
                'status' => $model->status,
                'type' => str_starts_with($item['id'], 'po-') ? 'purchase' : 'sale',
                'label' => $item['label'] ?? ($model instanceof Purchase ? 'PO #' . $model->id : 'Invoice #' . $model->invoice),
            ]);
        });

        $notificationCount = $notifications->count();

        View::share('notificationCount', $notificationCount);
        View::share('notifications', $notifications);

        return true;
    }
}
