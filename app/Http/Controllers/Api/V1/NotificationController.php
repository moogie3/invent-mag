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
     * @group Notifications
     * @title Get Notification Count
     * @response {
     *  "count": 5
     * }
     */
    public function count()
    {
        $counts = $this->notificationService->getNotificationCounts();
        return response()->json(['count' => $counts['total']]);
    }

    /**
     * @group Notifications
     * @title Get All Notifications
     * @response {
     *  "notifications": [
     *      {
     *          "id": "po::1",
     *          "title": "Purchase Order Due",
     *          "description": "Invoice #123 is due tomorrow.",
     *          "urgency": "high",
     *          "route": "/admin/po/view/1"
     *      }
     *  ]
     * }
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
     * @group Notifications
     * @title Mark Notification as Read
     * @urlParam id string required The composite ID of the notification to mark as read. Example: "po::1"
     *
     * @response {
     *  "success": true
     * }
     */
    public function markAsRead($id)
    {
        // The logic in the admin controller was a placeholder.
        // A real implementation would involve updating the notification status in the database.
        // For API purposes, acknowledging the request is sufficient for now.
        return response()->json(['success' => true]);
    }
}
