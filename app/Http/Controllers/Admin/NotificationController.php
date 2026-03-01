<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\NotificationService;
use App\Services\SystemNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;

class NotificationController extends Controller
{
    protected $notificationService;
    protected $systemNotificationService;

    public function __construct(NotificationService $notificationService, SystemNotificationService $systemNotificationService)
    {
        $this->notificationService = $notificationService;
        $this->systemNotificationService = $systemNotificationService;
    }

    public function index()
    {
        $notifications = $this->notificationService->getDueNotifications();
        $systemNotifications = $this->systemNotificationService->getSystemNotifications();

        $financialNotifications = $notifications->filter(fn($item) => in_array($item['type'], ['purchase', 'sales']));
        $inventoryNotifications = $notifications->filter(fn($item) => $item['type'] === 'product');
        $lowStockNotifications = $inventoryNotifications->filter(fn($item) => $item['status_text'] === 'Low Stock');
        $expiringNotifications = $inventoryNotifications->filter(fn($item) => str_contains($item['status_text'], 'Expiring'));

        $hasNotifications = $notifications->isNotEmpty() || $systemNotifications->isNotEmpty();

        return view('admin.notifications', compact('notifications', 'systemNotifications', 'hasNotifications', 'financialNotifications', 'lowStockNotifications', 'expiringNotifications'));
    }

    public function count()
    {
        $counts = $this->notificationService->getNotificationCounts();
        $systemCount = $this->systemNotificationService->getSystemNotificationCount();
        return response()->json(['count' => $counts['total'] + $systemCount]);
    }

    public function getNotifications()
    {
        $notifications = $this->notificationService->getDueNotifications();
        $systemNotifications = $this->systemNotificationService->getSystemNotifications();

        $simpleNotifications = $notifications->map(fn($item) => [
            'id' => $item['id'],
            'title' => $item['title'],
            'description' => $item['description'],
            'urgency' => $item['urgency'],
            'route' => $item['route'],
        ]);

        $simpleSystemNotifications = $systemNotifications->map(fn($item) => [
            'id' => $item['id'],
            'title' => $item['title'],
            'description' => $item['description'],
            'urgency' => $item['urgency'],
            'route' => $item['action_route'] ?? '#',
        ]);

        $allNotifications = $simpleNotifications->concat($simpleSystemNotifications)->values()->all();

        return response()->json(['notifications' => $allNotifications]);
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
            'system' => 'admin.settings', // Fallback for system notifications if accessed via /view
            default => null,
        };

        if (!$route) {
            return redirect()->route('admin.notifications')->with('error', 'Unknown notification type');
        }

        // Handle specific system notification routes based on ID part
        if ($type === 'system') {
            if (str_contains($id, 'trial') || str_contains($id, 'plan') || str_contains($id, 'limit')) {
                return redirect()->route('admin.setting.plan.upgrade');
            } elseif (str_contains($id, 'accounting')) {
                return redirect()->route('admin.setting.accounting');
            } elseif (str_contains($id, 'profile')) {
                return redirect()->route('admin.setting.profile.edit');
            }
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
            $systemNotifications = $this->systemNotificationService->getSystemNotifications();
            
            $allIds = $notifications->pluck('id')->concat($systemNotifications->pluck('id'))->toArray();

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
        $systemNotifications = $this->systemNotificationService->getSystemNotifications();
        View::share('notificationCount', $notifications->count());
        View::share('systemNotificationCount', $systemNotifications->count());
        View::share('totalNotificationCount', $notifications->count() + $systemNotifications->count());
        View::share('notifications', $notifications);
        View::share('systemNotifications', $systemNotifications);
        return true;
    }
}
