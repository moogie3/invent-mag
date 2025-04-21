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
        $today = Carbon::now();

        $notifications = $this->getDueNotifications()->map(function ($item) {
            // Determine model type
            if (str_starts_with($item['id'], 'po-')) {
                $id = (int) str_replace('po-', '', $item['id']);
                $model = Purchase::find($id);
            } elseif (str_starts_with($item['id'], 'sale-')) {
                $id = (int) str_replace('sale-', '', $item['id']);
                $model = Sales::find($id);
            } else {
                return $item; // fallback to raw array
            }

            // Return full object merged with original array (for title, route, urgency, etc.)
            return (object) array_merge($item, [
                'due_date' => $model->due_date,
                'payment_date' => $model->payment_date,
                'status' => $model->status,
                'type' => str_starts_with($item['id'], 'po-') ? 'purchase' : 'sale',
                'label' => $item['label'] ?? ($model instanceof Purchase ? 'PO #' . $model->id : 'Invoice #' . $model->invoice),
            ]);
        });

        return view('admin.notifications', compact('notifications', 'today'));
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
    public function getDueNotifications()
    {
        $poNotifications = Purchase::where('due_date', '<=', Carbon::now()->addDays(7))
            ->where('status', '!=', 'Paid')
            ->orderBy('due_date', 'asc')
            ->get()
            ->map(function ($po) {
                $daysRemaining = Carbon::now()->diffInDays($po->due_date, false);

                $urgency = 'low';
                if ($daysRemaining <= 2) {
                    $urgency = 'high';
                } elseif ($daysRemaining <= 5) {
                    $urgency = 'medium';
                }

                return [
                    'id' => 'po-' . $po->id, // Adding prefix to identify notification type
                    'title' => "Due Purchase: PO #{$po->id}",
                    'description' => "Due on {$po->due_date->format('M d, Y')}",
                    'due_date' => $po->due_date,
                    'status' => $po->status,
                    'urgency' => $urgency,
                    'days_remaining' => $daysRemaining,
                    'route' => route('admin.po.edit', ['id' => $po->id]),
                    'type' => 'purchase',
                    'label' => 'PO Invoice #' . $po->id,
                ];
            });

        $salesNotifications = Sales::where('due_date', '<=', Carbon::now()->addDays(7))
            ->where('status', '!=', 'Paid')
            ->orderBy('due_date', 'asc')
            ->get()
            ->map(function ($sale) {
                $daysRemaining = Carbon::now()->diffInDays($sale->due_date, false);

                $urgency = 'low';
                if ($daysRemaining <= 2) {
                    $urgency = 'high';
                } elseif ($daysRemaining <= 5) {
                    $urgency = 'medium';
                }

                return [
                    'id' => 'sale-' . $sale->id, // Adding prefix to identify notification type
                    'title' => "Due Invoice: #{$sale->invoice}",
                    'description' => "Due on {$sale->due_date->format('M d, Y')}",
                    'due_date' => $sale->due_date,
                    'status' => $sale->status,
                    'urgency' => $urgency,
                    'days_remaining' => $daysRemaining,
                    'route' => route('admin.sales.edit', ['id' => $sale->id]),
                    'type' => 'sales',
                    'label' => 'Invoice #' . $sale->invoice,
                ];
            });

        // Combine and sort notifications by urgency and due date
        return $poNotifications->concat($salesNotifications)->sortBy([['days_remaining', 'asc']]);
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