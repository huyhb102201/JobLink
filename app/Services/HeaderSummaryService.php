<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class HeaderSummaryService
{
    public function getSummary(int $userId): array
    {
        $key = "hdr:summary:{$userId}";

        return Cache::remember($key, now()->addMinutes(5), function () use ($userId) {
            $notif = app(\App\Services\NotificationService::class)->getHeaderData($userId);
            $chat  = app(\App\Services\NotificationService::class)->getHeaderChatList($userId);

            return [
                'notifications'        => $notif['notifications'] ?? [],
                'unread_notifications' => $notif['unread_notifications'] ?? 0,
                'unread_messages'      => $notif['unread_messages'] ?? 0,
                'chat'                 => $chat ?? ['unread_total' => 0, 'boxes' => []],
            ];
        });
    }

    public function refreshAndGet(int $userId): array
    {
        $this->forget($userId);
        return $this->getSummary($userId);
    }

    public function forget(int $userId): void
    {
        Cache::forget("hdr:summary:{$userId}");
    }
}
