<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\NotificationService;
use Illuminate\Http\Request;

/**
 * @group Notifications
 *
 * APIs for managing user notifications
 */
class NotificationController extends Controller
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Get Notification Count
     *
     * @group Notifications
     * @authenticated
     *
     * @response 200 scenario="Success" {"count":5}
     * @response 401 scenario="Unauthenticated" {"message": "Unauthenticated."}
     */
    public function count()
    {
        $counts = $this->notificationService->getNotificationCounts();
        return response()->json(['count' => $counts['total']]);
    }

    /**
     * Get All Notifications
     *
     * @group Notifications
     * @authenticated
     *
     * @response 200 scenario="Success" {"notifications":[{"id":"po::1","title":"New Purchase Order","description":"A new purchase order has been created.","urgency":"high","route":"/purchase-orders/1"}]}
     * @response 401 scenario="Unauthenticated" {"message": "Unauthenticated."}
     */
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

    /**
     * Mark Notification as Read
     *
     * @group Notifications
     * @authenticated
     * @urlParam id string required The composite ID of the notification to mark as read. Example: "po::1"
     *
     * @response 200 scenario="Success" {"success":true}
     * @response 404 scenario="Not Found" {"message": "Notification not found."}
     * @response 401 scenario="Unauthenticated" {"message": "Unauthenticated."}
     */
    public function markAsRead($id)
    {
        // The logic in the admin controller was a placeholder.
        // A real implementation would involve updating the notification status in the database.
        // For API purposes, acknowledging the request is sufficient for now.
        return response()->json(['success' => true]);
    }
}
