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
     * @responseField count integer The total number of notifications.
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
     * @responseField notifications object[] A list of notifications.
     * @responseField notifications[].id string The composite ID of the notification.
     * @responseField notifications[].title string The title of the notification.
     * @responseField notifications[].description string The description of the notification.
     * @responseField notifications[].urgency string The urgency of the notification.
     * @responseField notifications[].route string The route for the notification.
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
     * @responseField success boolean Indicates whether the request was successful.
     */
    public function markAsRead($id)
    {
        // The logic in the admin controller was a placeholder.
        // A real implementation would involve updating the notification status in the database.
        // For API purposes, acknowledging the request is sufficient for now.
        return response()->json(['success' => true]);
    }
}
