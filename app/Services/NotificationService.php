<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\BoxChat;
use App\Models\Account;
use App\Models\Job;
use App\Models\Org;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
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
    /**
     * Dữ liệu header: 5 thông báo + badges
     */
    public function getHeaderData(int $userId): array
    {
        $notifications = Notification::forUser($userId)
            ->where('type', '!=', Notification::TYPE_MESSAGE)
            ->where('visible', true)
            ->orderByDesc('created_at')
            ->take(5)
            ->get();

        $unreadNotifications = Notification::forUser($userId)
            ->where('type', '!=', Notification::TYPE_MESSAGE)
            ->unread()
            ->where('visible', true)
            ->count();

        $unreadMessages = Notification::forUser($userId)
            ->where('type', Notification::TYPE_MESSAGE)
            ->unread()
            ->where('visible', true)
            ->count();

        return [
            'notifications' => $notifications,
            'unread_notifications' => $unreadNotifications,
            'unread_messages' => $unreadMessages,
        ];
    }

    /**
     * Danh sách box chat (header)
     */
    public function getHeaderChatList(int $userId): array
    {
        // Lấy 5 box chat gần nhất của user
        $boxes = BoxChat::with([
            'messages' => function ($q) {
                $q->latest()->take(1); // chỉ lấy tin mới nhất
            },
            'messages.sender'
        ])
            ->where(function ($q) use ($userId) {
                $q->where(function ($q2) use ($userId) {
                    $q2->where('type', 1)
                        ->where(function ($q3) use ($userId) {
                            $q3->where('sender_id', $userId)
                                ->orWhere('receiver_id', $userId);
                        });
                })->orWhere(function ($q2) use ($userId) {
                    $q2->where('type', 2)
                        ->whereExists(function ($q3) use ($userId) {
                            $q3->select(DB::raw(1))
                                ->from('jobs')
                                ->whereColumn('jobs.job_id', 'box_chat.job_id')
                                ->where('jobs.status', 'in_progress')
                                ->where(function ($q4) use ($userId) {
                                    $q4->where('jobs.account_id', $userId)
                                        ->orWhereRaw('find_in_set(?, jobs.apply_id)', [$userId]);
                                });
                        });
                })->orWhere(function ($q2) use ($userId) {
                    $q2->where('type', 3)
                        ->whereExists(function ($q3) use ($userId) {
                            $q3->select(DB::raw(1))
                                ->from('org_members')
                                ->whereColumn('org_members.org_id', 'box_chat.org_id')
                                ->where('org_members.account_id', $userId)
                                ->where('org_members.status', 'ACTIVE');
                        });
                });
            })
            ->orderBy('updated_at', 'desc')
            ->take(5)
            ->get();

        // Nếu có bảng notifications để đếm tin chưa đọc
        $notifications = DB::table('notifications')
            ->where('user_id', $userId)
            ->whereNull('read_at')
            ->get();

        $messageIds = $notifications->map(function ($n) {
            $meta = json_decode($n->meta, true);
            return $meta['message_id'] ?? null;
        })->filter()->values();

        $messages = DB::table('messages')
            ->whereIn('id', $messageIds)
            ->select('id', 'box_id')
            ->get();

        $messageBoxMap = $messages->groupBy('box_id')->map->count();

        // Format kết quả
        $result = $boxes->map(function ($box) use ($userId, $messageBoxMap) {
            $latest = $box->messages->first();
            $lastMessage = '';
            $lastTime = '';

            if ($latest) {
                try {
                    if ($latest->content) {
                        $lastMessage = Crypt::decryptString($latest->content);
                    }
                } catch (\Exception $e) {
                    $lastMessage = '[Không thể giải mã tin nhắn]';
                }
                $lastTime = $latest->created_at->diffForHumans();
            }

            // Xác định thông tin hiển thị
            $name = 'Người dùng';
            $avatar = asset('assets/img/defaultavatar.jpg');

            switch ($box->type) {
                case 1:
                    $partnerId = $box->sender_id == $userId ? $box->receiver_id : $box->sender_id;
                    $partner = Account::find($partnerId);
                    $name = $partner->name ?? 'Người dùng';
                    $avatar = $partner && $partner->avatar_url
                        ? asset($partner->avatar_url)
                        : asset('assets/img/defaultavatar.jpg');
                    break;

                case 2:
                    $job = Job::find($box->job_id);
                    $name = $job ? "Nhóm công việc: " . ($job->title ?? $box->name) : $box->name;
                    $avatar = asset('assets/img/group-icon.png');
                    break;

                case 3:
                    $org = Org::find($box->org_id);
                    $name = $org ? "Tổ chức: " . $org->name : $box->name;
                    $avatar = asset('assets/img/org-icon.png');
                    break;
            }

            return [
                'id' => $box->id,
                'type' => $box->type,
                'name' => $name,
                'avatar' => $avatar,
                'last_message' => \Illuminate\Support\Str::limit($lastMessage, 40),
                'last_time' => $lastTime,
                'unread' => $messageBoxMap[$box->id] ?? 0,
            ];
        });

        return [
            'boxes' => $result,
            'unread_total' => $result->sum('unread'),
        ];
    }


    /**
     * Đánh dấu tất cả thông báo KHÔNG phải message đã đọc
     */
    public function markAllNonMessageAsRead(int $userId)
    {
        Notification::forUser($userId)
            ->where('type', '!=', Notification::TYPE_MESSAGE)
            ->where(function ($q) {
                $q->whereNull('read_at')->orWhere('is_read', false);
            })
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
    }

    /**
     * Đánh dấu tin nhắn trong box là đã đọc
     */
    public function markBoxMessagesAsRead(int $userId, int $boxId)
    {
        $messageIds = DB::table('messages')
            ->where('box_id', $boxId)
            ->pluck('id');

        Notification::forUser($userId)
            ->where('type', Notification::TYPE_MESSAGE)
            ->where(function ($q) {
                $q->whereNull('read_at')->orWhere('is_read', false);
            })
            ->whereIn(DB::raw("JSON_UNQUOTE(JSON_EXTRACT(meta, '$.message_id'))"), $messageIds)
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
    }
}
