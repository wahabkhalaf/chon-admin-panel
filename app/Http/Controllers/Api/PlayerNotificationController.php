<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Player;
use App\Models\PlayerNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PlayerNotificationController extends Controller
{
    /**
     * Get player's notification history.
     */
    public function index(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'whatsapp_number' => 'required|string',
            'page' => 'integer|min:1',
            'per_page' => 'integer|min:1|max:50',
            'filter' => 'string|in:all,unread,read,recent',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $player = Player::where('whatsapp_number', $request->whatsapp_number)->first();

        if (!$player) {
            return response()->json([
                'success' => false,
                'message' => 'Player not found'
            ], 404);
        }

        $perPage = $request->get('per_page', 15);
        $filter = $request->get('filter', 'all');

        $query = $player->notifications()->with('notification');

        switch ($filter) {
            case 'unread':
                $query = $query->unread();
                break;
            case 'read':
                $query = $query->read();
                break;
            case 'recent':
                $query = $query->recent();
                break;
        }

        $notifications = $query->orderBy('received_at', 'desc')
            ->paginate($perPage);

        // Transform the data for better API response
        $transformedNotifications = $notifications->getCollection()->map(function ($playerNotification) {
            return [
                'id' => $playerNotification->id,
                'notification_id' => $playerNotification->notification_id,
                'title' => $playerNotification->notification->title,
                'message' => $playerNotification->notification->message,
                'type' => $playerNotification->notification->type,
                'priority' => $playerNotification->notification->priority,
                'data' => $playerNotification->notification->data,
                'received_at' => $playerNotification->received_at->toISOString(),
                'read_at' => $playerNotification->read_at?->toISOString(),
                'is_read' => $playerNotification->isRead(),
                'delivery_data' => $playerNotification->delivery_data,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'notifications' => $transformedNotifications,
                'pagination' => [
                    'current_page' => $notifications->currentPage(),
                    'last_page' => $notifications->lastPage(),
                    'per_page' => $notifications->perPage(),
                    'total' => $notifications->total(),
                    'from' => $notifications->firstItem(),
                    'to' => $notifications->lastItem(),
                ],
                'summary' => [
                    'total_notifications' => $player->notifications()->count(),
                    'unread_count' => $player->unreadNotificationsCount(),
                    'read_count' => $player->readNotifications()->count(),
                ]
            ]
        ]);
    }

    /**
     * Get unread notifications count.
     */
    public function unreadCount(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'whatsapp_number' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $player = Player::where('whatsapp_number', $request->whatsapp_number)->first();

        if (!$player) {
            return response()->json([
                'success' => false,
                'message' => 'Player not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'unread_count' => $player->unreadNotificationsCount(),
                'total_notifications' => $player->notifications()->count(),
            ]
        ]);
    }

    /**
     * Mark notification as read.
     */
    public function markAsRead(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'whatsapp_number' => 'required|string',
            'notification_id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $player = Player::where('whatsapp_number', $request->whatsapp_number)->first();

        if (!$player) {
            return response()->json([
                'success' => false,
                'message' => 'Player not found'
            ], 404);
        }

        $success = $player->markNotificationAsRead($request->notification_id);

        if (!$success) {
            return response()->json([
                'success' => false,
                'message' => 'Notification not found or already read'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Notification marked as read',
            'data' => [
                'unread_count' => $player->unreadNotificationsCount(),
            ]
        ]);
    }

    /**
     * Mark all notifications as read.
     */
    public function markAllAsRead(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'whatsapp_number' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $player = Player::where('whatsapp_number', $request->whatsapp_number)->first();

        if (!$player) {
            return response()->json([
                'success' => false,
                'message' => 'Player not found'
            ], 404);
        }

        $player->markAllNotificationsAsRead();

        return response()->json([
            'success' => true,
            'message' => 'All notifications marked as read',
            'data' => [
                'unread_count' => 0,
            ]
        ]);
    }

    /**
     * Get notification details.
     */
    public function show(Request $request, int $notificationId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'whatsapp_number' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $player = Player::where('whatsapp_number', $request->whatsapp_number)->first();

        if (!$player) {
            return response()->json([
                'success' => false,
                'message' => 'Player not found'
            ], 404);
        }

        $playerNotification = $player->notifications()
            ->where('notification_id', $notificationId)
            ->with('notification')
            ->first();

        if (!$playerNotification) {
            return response()->json([
                'success' => false,
                'message' => 'Notification not found'
            ], 404);
        }

        // Mark as read when viewed
        if ($playerNotification->isUnread()) {
            $playerNotification->markAsRead();
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $playerNotification->id,
                'notification_id' => $playerNotification->notification_id,
                'title' => $playerNotification->notification->title,
                'message' => $playerNotification->notification->message,
                'type' => $playerNotification->notification->type,
                'priority' => $playerNotification->notification->priority,
                'data' => $playerNotification->notification->data,
                'received_at' => $playerNotification->received_at->toISOString(),
                'read_at' => $playerNotification->read_at?->toISOString(),
                'is_read' => $playerNotification->isRead(),
                'delivery_data' => $playerNotification->delivery_data,
            ]
        ]);
    }
}
