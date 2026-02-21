<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;

class NotificationController extends Controller
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    public function index()
    {
        $notifications = $this->notificationService->getDueNotifications();

        $financialNotifications = $notifications->filter(fn($item) => in_array($item['type'], ['purchase', 'sales']));
        $inventoryNotifications = $notifications->filter(fn($item) => $item['type'] === 'product');
        $lowStockNotifications = $inventoryNotifications->filter(fn($item) => $item['status_text'] === 'Low Stock');
        $expiringNotifications = $inventoryNotifications->filter(fn($item) => str_contains($item['status_text'], 'Expiring'));

        $hasNotifications = $notifications->isNotEmpty();

        return view('admin.notifications', compact('notifications', 'hasNotifications', 'financialNotifications', 'lowStockNotifications', 'expiringNotifications'));
    }

    public function count()
    {
        $counts = $this->notificationService->getNotificationCounts();
        return response()->json(['count' => $counts['total']]);
    }

    public function getNotifications()
    {
        $notifications = $this->notificationService->getDueNotifications();

        $simpleNotifications = $notifications->map(fn($item) => [
            'id' => $item['id'],
            'title' => $item['title'],
            'description' => $item['description'],
            'urgency' => $item['urgency'],
            'route' => $item['route'],
        ]);

        return response()->json(['notifications' => $simpleNotifications->values()->all()]);
    }

    public function view($id)
    {
        [$type, $actualId] = explode('::', $id) + [null, null];

        if (!$actualId) {
            return redirect()->route('admin.notifications')->with('error', 'Invalid notification ID');
        }

        $route = match ($type) {
            'po' => 'admin.po.view',
            'sale' => 'admin.sales.view',
            'product' => 'admin.product.edit',
            default => null,
        };

        if (!$route) {
            return redirect()->route('admin.notifications')->with('error', 'Unknown notification type');
        }

        return redirect()->route($route, ['id' => $actualId]);
    }

    public function markAsRead($id)
    {
        $user = auth()->user();
        if ($user) {
            $settings = $user->system_settings ?? [];
            $dismissed = $settings['dismissed_notifications'] ?? [];
            
            if (!in_array($id, $dismissed)) {
                $dismissed[] = $id;
                $settings['dismissed_notifications'] = $dismissed;
                $user->system_settings = $settings;
                $user->save();
            }
        }
        
        return response()->json(['success' => true]);
    }

    public function clearAll()
    {
        $user = auth()->user();
        if ($user) {
            $notifications = $this->notificationService->getDueNotifications();
            $allIds = $notifications->pluck('id')->toArray();

            $settings = $user->system_settings ?? [];
            $dismissed = $settings['dismissed_notifications'] ?? [];
            
            $settings['dismissed_notifications'] = array_values(array_unique(array_merge($dismissed, $allIds)));
            $user->system_settings = $settings;
            $user->save();
        }
        
        return response()->json(['success' => true]);
    }

    public function shareNotificationsWithAllViews()
    {
        $notifications = $this->notificationService->getDueNotifications();
        View::share('notificationCount', $notifications->count());
        View::share('notifications', $notifications);
        return true;
    }
}
