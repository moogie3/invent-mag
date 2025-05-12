<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Purchase;
use App\Models\Sales;
use App\Models\Product;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\View;

class NotificationController extends Controller
{
    public function index()
    {
        $notifications = $this->getDueNotifications();

        $financialNotifications = $notifications->filter(function ($item) {
            return in_array($item['type'], ['purchase', 'sales']);
        });

        $inventoryNotifications = $notifications->filter(function ($item) {
            return $item['type'] === 'product';
        });

        $lowStockNotifications = $inventoryNotifications->filter(function ($item) {
            return $item['status_text'] === 'Low Stock';
        });

        $expiringNotifications = $inventoryNotifications->filter(function ($item) {
            return $item['status'] === 'Expiring Soon';
        });

        $hasNotifications = $financialNotifications->isNotEmpty() || $lowStockNotifications->isNotEmpty() || $expiringNotifications->isNotEmpty();

        return view('admin.notifications', compact('notifications', 'hasNotifications', 'financialNotifications', 'lowStockNotifications', 'expiringNotifications'));
    }

    public function count()
    {
        $poCount = Purchase::where('due_date', '<=', Carbon::now()->addDays(7))
            ->where('status', '!=', 'Paid')
            ->count();

        $salesCount = Sales::where('due_date', '<=', Carbon::now()->addDays(7))
            ->where('status', '!=', 'Paid')
            ->count();

        $lowStockCount = Product::lowStockCount();
        $expiringSoonCount = Product::expiringSoonCount();

        return response()->json(['count' => $poCount + $salesCount + $lowStockCount + $expiringSoonCount]);
    }

    public function getNotifications()
    {
        $notifications = $this->getDueNotifications();

        $simpleNotifications = $notifications->map(function ($item) {
            return [
                'id' => $item['id'],
                'title' => $item['title'],
                'description' => $item['description'],
                'urgency' => $item['urgency'],
                'route' => $item['route'],
            ];
        });

        return response()->json(['notifications' => $simpleNotifications->values()->all()]);
    }

    public function view($id)
    {
        $idParts = explode('::', $id);
        $type = $idParts[0];
        $actualId = $idParts[1] ?? null;

        if (!$actualId) {
            return redirect()->route('admin.notifications')->with('error', 'Invalid notification ID');
        }

        return match ($type) {
            'po' => redirect()->route('admin.po.view', ['id' => $actualId]),
            'sale' => redirect()->route('admin.sales.view', ['id' => $actualId]),
            'product' => redirect()->route('admin.product.edit', ['id' => $actualId]),
            default => redirect()->route('admin.notifications')->with('error', 'Unknown notification type'),
        };
    }

    public function markAsRead($id)
    {
        return response()->json(['success' => true]);
    }

    private function getStatusInfo($status, $dueDate, $paymentDate = null)
    {
        $today = Carbon::today();
        $diffDays = $today->diffInDays($dueDate, false);

        if ($status === 'Paid') {
            if ($paymentDate && $today->isSameDay($paymentDate)) {
                return ['badge' => 'text-green', 'text' => 'Paid Today', 'icon' => 'ti ti-check'];
            }
            return ['badge' => 'text-green', 'text' => 'Paid', 'icon' => 'ti ti-check'];
        } elseif ($diffDays == 0) {
            return ['badge' => 'text-orange', 'text' => 'Due Today', 'icon' => 'ti ti-alert-triangle'];
        } elseif ($diffDays > 0 && $diffDays <= 3) {
            return ['badge' => 'text-orange', 'text' => "Due in {$diffDays} Days", 'icon' => 'ti ti-calendar-event'];
        } elseif ($diffDays > 3 && $diffDays <= 7) {
            return ['badge' => 'text-yellow', 'text' => 'Due in 1 Week', 'icon' => 'ti ti-calendar'];
        } elseif ($diffDays < 0) {
            return ['badge' => 'text-red', 'text' => 'Overdue', 'icon' => 'ti ti-alert-circle'];
        }

        return ['badge' => 'text-blue', 'text' => 'Pending', 'icon' => 'ti ti-clock'];
    }

    public function getDueNotifications()
    {
        $today = Carbon::today();

        return collect()
            ->concat($this->getPurchaseNotifications($today))
            ->concat($this->getSalesNotifications($today))
            ->concat($this->getLowStockNotifications())
            ->concat($this->getExpiringProductNotifications())
            ->filter(fn($item) => $item['show_notification'])
            ->sortBy([['days_remaining', 'asc']]);
    }

    protected function getPurchaseNotifications(Carbon $today)
    {
        return Purchase::where('due_date', '<=', Carbon::now()->addDays(7))
            ->orderBy('due_date', 'asc')
            ->get()
            ->map(function ($po) use ($today) {
                $daysRemaining = $today->diffInDays($po->due_date, false);
                $statusInfo = $this->getStatusInfo($po->status, $po->due_date, $po->payment_date);
                $showNotification = $po->status !== 'Paid' || ($po->payment_date && $today->isSameDay($po->payment_date));

                return [
                    'id' => 'po::' . $po->id,
                    'title' => "Due Purchase: PO #{$po->id}",
                    'description' => "Due on {$po->due_date->format('M d, Y')}",
                    'due_date' => $po->due_date,
                    'payment_date' => $po->payment_date,
                    'status' => $po->status,
                    'urgency' => $this->getUrgencyLevel($daysRemaining),
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
    }

    protected function getSalesNotifications(Carbon $today)
    {
        return Sales::where('due_date', '<=', Carbon::now()->addDays(7))
            ->orderBy('due_date', 'asc')
            ->get()
            ->map(function ($sale) use ($today) {
                $daysRemaining = $today->diffInDays($sale->due_date, false);
                $statusInfo = $this->getStatusInfo($sale->status, $sale->due_date, $sale->payment_date);
                $showNotification = $sale->status !== 'Paid' || ($sale->payment_date && $today->isSameDay($sale->payment_date));

                return [
                    'id' => 'sale::' . $sale->id,
                    'title' => "Due Invoice: #{$sale->invoice}",
                    'description' => "Due on {$sale->due_date->format('M d, Y')}",
                    'due_date' => $sale->due_date,
                    'payment_date' => $sale->payment_date,
                    'status' => $sale->status,
                    'urgency' => $this->getUrgencyLevel($daysRemaining),
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
    }

    protected function getLowStockNotifications()
    {
        return Product::getLowStockProducts()->map(function ($product) {
            return [
                'id' => 'product::' . $product->id,
                'title' => "Low Stock Alert: {$product->name}",
                'description' => "Only {$product->stock_quantity} remaining",
                'due_date' => Carbon::now(),
                'status' => 'Low Stock',
                'urgency' => 'high',
                'days_remaining' => 0,
                'route' => route('admin.product.edit', ['id' => $product->id]),
                'threshold' => $product->getLowStockThreshold(),
                'type' => 'product',
                'label' => 'Product #' . $product->code,
                'status_badge' => 'text-red',
                'status_text' => 'Low Stock',
                'status_icon' => 'ti ti-alert-triangle',
                'show_notification' => true,
            ];
        });
    }

    protected function getExpiringProductNotifications()
    {
        return Product::getExpiringSoonProducts()->map(function ($product) {
            $daysRemaining = Carbon::now()->diffInDays($product->expiry_date, false);
            [, $statusText] = \App\Helpers\ProductHelper::getExpiryClassAndText($product->expiry_date);

            return [
                'id' => 'product::' . $product->id,
                'title' => "Expiring Product: {$product->name}",
                'description' => "Expires on {$product->expiry_date->format('M d, Y')}",
                'due_date' => $product->expiry_date,
                'status' => 'Expiring Soon',
                'urgency' => $this->getUrgencyLevel($daysRemaining),
                'days_remaining' => $daysRemaining,
                'route' => route('admin.product.edit', ['id' => $product->id]),
                'type' => 'product',
                'label' => 'Product #' . $product->code,
                'status_badge' => $daysRemaining <= 3 ? 'text-red' : 'text-orange',
                'status_text' => $statusText ?? 'Expiring Soon',
                'status_icon' => 'ti ti-calendar-time',
                'show_notification' => true,
            ];
        });
    }

    protected function getUrgencyLevel(int $daysRemaining): string
    {
        return $daysRemaining <= 2 ? 'high' : ($daysRemaining <= 5 ? 'medium' : 'low');
    }

    public function shareNotificationsWithAllViews()
    {
        $notifications = $this->getDueNotifications();
        View::share('notificationCount', $notifications->count());
        View::share('notifications', $notifications);
        return true;
    }
}
