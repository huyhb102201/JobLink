<?php

namespace App\Services;

use App\Models\Notification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;
use Exception;

class NotificationService
{
    /**
     * Tạo và lưu một thông báo mới.
     */
    public function create(
        int $userId,
        string $type,
        ?string $title = null,
        ?string $body = null,
        ?array $meta = [],
        ?int $actorId = null,
        string $severity = 'low'
    ): ?Notification {
        try {
            $notification = Notification::create([
                'user_id' => $userId,
                'actor_id' => $actorId,
                'type' => $type,
                'title' => $title,
                'body' => $body,
                'meta' => $meta,
                'severity' => $severity,
                'is_read' => false,
                'read_at' => null,
                'visible' => true,
            ]);

            Log::info("New notification created", [
                'id' => $notification->id,
                'type' => $type,
                'user' => $userId,
            ]);

            return $notification;
        } catch (Exception $e) {
            Log::error('Error creating notification: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Lấy danh sách thông báo loại tin nhắn (message)
     */
    public function getMessageNotifications(int $userId, int $limit = 20)
    {
        return Notification::where('user_id', $userId)
            ->where('type', Notification::TYPE_MESSAGE)
            ->where('visible', true)
            ->orderByDesc('created_at')
            ->take($limit)
            ->get();
    }

    /**
     * Lấy danh sách các thông báo khác ngoài tin nhắn
     */
    public function getOtherNotifications(int $userId, int $limit = 20)
    {
        return Notification::select('id', 'type', 'title', 'body', 'is_read', 'meta', 'created_at')
            ->where('user_id', $userId)
            ->where('type', '!=', Notification::TYPE_MESSAGE)
            ->where('visible', true)
            ->orderByDesc('created_at')
            ->take($limit)
            ->get()
            ->map(function ($n) {
                $n->created_at = $n->created_at->diffForHumans();
                return $n;
            });
    }


    /**
     * Đánh dấu 1 thông báo đã đọc
     */
    public function markAsRead(Notification $notification): bool
    {
        return $notification->markAsRead();
    }

    /**
     * Đánh dấu tất cả thông báo của 1 user là đã đọc
     */
    public function markAllAsRead(int $userId): int
    {
        return Notification::where('user_id', $userId)
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => Carbon::now(),
            ]);
    }
}
