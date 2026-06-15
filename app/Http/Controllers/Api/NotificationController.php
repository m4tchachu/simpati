<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\NotificationResource;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NotificationController extends Controller
{
    public function __construct(
        private NotificationService $notificationService,
    ) {}

    /**
     * Get user's notifications with pagination and filters
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
            'type' => 'nullable|string|max:255',
            'read' => 'nullable|boolean',
        ]);

        $notifications = $this->notificationService->getUserNotifications(
            $request->user(),
            $filters
        );

        return response()->json([
            'message' => 'Notifications retrieved successfully',
            'data' => NotificationResource::collection($notifications),
            'pagination' => [
                'total' => $notifications->total(),
                'per_page' => $notifications->perPage(),
                'current_page' => $notifications->currentPage(),
                'last_page' => $notifications->lastPage(),
                'from' => $notifications->firstItem(),
                'to' => $notifications->lastItem(),
            ],
        ], 200);
    }

    /**
     * Get unread notifications count
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function unreadCount(Request $request): JsonResponse
    {
        $count = $this->notificationService->getUnreadCount($request->user());

        return response()->json([
            'message' => 'Unread count retrieved',
            'data' => [
                'unread_count' => $count,
            ],
        ], 200);
    }

    /**
     * Get all unread notifications
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function unread(Request $request): JsonResponse
    {
        $notifications = $this->notificationService->getUnreadNotifications($request->user());

        return response()->json([
            'message' => 'Unread notifications retrieved',
            'data' => NotificationResource::collection($notifications),
            'count' => $notifications->count(),
        ], 200);
    }

    /**
     * Mark notification as read
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function markAsRead(Request $request, int $id): JsonResponse
    {
        try {
            $notification = $this->notificationService->markAsRead($id);

            // Verify ownership
            if ($notification->user_id !== $request->user()->id) {
                return response()->json([
                    'message' => 'Unauthorized',
                ], 403);
            }

            return response()->json([
                'message' => 'Notification marked as read',
                'data' => new NotificationResource($notification),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to mark notification as read',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Mark notification as unread
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function markAsUnread(Request $request, int $id): JsonResponse
    {
        try {
            $notification = $this->notificationService->markAsUnread($id);

            // Verify ownership
            if ($notification->user_id !== $request->user()->id) {
                return response()->json([
                    'message' => 'Unauthorized',
                ], 403);
            }

            return response()->json([
                'message' => 'Notification marked as unread',
                'data' => new NotificationResource($notification),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to mark notification as unread',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Mark all notifications as read
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function markAllAsRead(Request $request): JsonResponse
    {
        try {
            $count = $this->notificationService->markAllAsRead($request->user());

            return response()->json([
                'message' => 'All notifications marked as read',
                'data' => [
                    'marked_count' => $count,
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to mark all notifications as read',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Delete notification
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        try {
            // Verify ownership before deletion
            $notification = DB::table('notifications')
                ->where('id', $id)
                ->where('user_id', $request->user()->id)
                ->first();

            if (! $notification) {
                return response()->json([
                    'message' => 'Notification not found',
                ], 404);
            }

            $this->notificationService->deleteNotification($id);

            return response()->json([
                'message' => 'Notification deleted successfully',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to delete notification',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Delete all notifications
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function deleteAll(Request $request): JsonResponse
    {
        try {
            $count = $this->notificationService->deleteAllNotifications($request->user());

            return response()->json([
                'message' => 'All notifications deleted successfully',
                'data' => [
                    'deleted_count' => $count,
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to delete all notifications',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Get notification statistics
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function stats(Request $request): JsonResponse
    {
        $stats = $this->notificationService->getNotificationStats($request->user());

        return response()->json([
            'message' => 'Notification statistics retrieved',
            'data' => $stats,
        ], 200);
    }
}
