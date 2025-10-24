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
use App\Events\HeaderSummaryUpdated;

class NotificationService
{
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

            $this->emitHeaderSummary($userId);

            return $notification;
        } catch (Exception $e) {
            Log::error('Error creating notification: ' . $e->getMessage());
            return null;
        }
    }

    public function getMessageNotifications(int $userId, int $limit = 20)
    {
        return Notification::where('user_id', $userId)
            ->where('type', Notification::TYPE_MESSAGE)
            ->where('visible', true)
            ->orderByDesc('created_at')
            ->take($limit)
            ->get();
    }

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

    public function markAsRead(Notification $notification): bool
    {
        return $notification->markAsRead();
    }

    public function markAllAsRead(int $userId): int
    {
        return Notification::where('user_id', $userId)
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => Carbon::now(),
            ]);
    }

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
            ->where('visible', true)
            ->where(function ($q) {
                $q->whereNull('read_at')->orWhere('is_read', false);
            })
            ->count();

        $unreadMessages = Notification::forUser($userId)
            ->where('type', Notification::TYPE_MESSAGE)
            ->where('visible', true)
            ->where(function ($q) {
                $q->whereNull('read_at')->orWhere('is_read', false);
            })
            ->count();

        return [
            'notifications' => $notifications,
            'unread_notifications' => $unreadNotifications,
            'unread_messages' => $unreadMessages,
        ];
    }

    public function getHeaderChatList(int $userId): array
    {
        // 1) Lấy 5 box gần nhất, chỉ cột cần dùng + latestMessage
        $boxes = BoxChat::select('id', 'type', 'sender_id', 'receiver_id', 'job_id', 'org_id', 'name', 'updated_at')
            ->with([
                'latestMessage' => function ($q) {
                    // Quan trọng: qualify cột với tên bảng để tránh ambiguous
                    $q->select(
                        'messages.id',
                        DB::raw('messages.box_id as box_id'),
                        'messages.content',
                        'messages.created_at'
                    );
                }
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
            ->orderByDesc('updated_at')
            ->take(5)
            ->get();

        // 2) Đếm unread mỗi box bằng 1 query join notifications + messages
        //    (meta.message_id là id message)
        $unreadByBox = DB::table('notifications as n')
            ->join('messages as m', 'm.id', '=', DB::raw("JSON_UNQUOTE(JSON_EXTRACT(n.meta, '$.message_id'))"))
            ->where('n.user_id', $userId)
            ->where('n.type', Notification::TYPE_MESSAGE)
            ->where('n.visible', 1)
            ->where(function ($q) {
                $q->whereNull('n.read_at')->orWhere('n.is_read', 0);
            })
            ->groupBy('m.box_id')
            ->pluck(DB::raw('COUNT(*)'), 'm.box_id'); // [box_id => count]

        // 3) Lấy info hiển thị: tên/ảnh (tránh select dư)
        $result = $boxes->map(function ($box) use ($userId, $unreadByBox) {
            $latest = $box->latestMessage;
            $lastMessage = '';
            $lastTime = '';

            if ($latest) {
                try {
                    if ($latest->content) {
                        $lastMessage = \Illuminate\Support\Str::limit(Crypt::decryptString($latest->content), 40);
                    }
                } catch (\Exception $e) {
                    $lastMessage = '[Không thể giải mã tin nhắn]';
                }
                $lastTime = $latest->created_at?->diffForHumans();
            }

            $name = $box->name ?: 'Người dùng';
            $avatar = asset('assets/img/defaultavatar.jpg');

            switch ($box->type) {
                case 1:
                    $partnerId = $box->sender_id == $userId ? $box->receiver_id : $box->sender_id;
                    if ($partnerId) {
                        $partner = Account::select('account_id', 'name', 'avatar_url')->find($partnerId);
                        $name = $partner->name ?? $name;
                        $avatar = ($partner && $partner->avatar_url) ? asset($partner->avatar_url) : $avatar;
                    }
                    break;
                case 2:
                    $job = Job::select('job_id', 'title')->find($box->job_id);
                    $name = $job ? ("Nhóm công việc: " . ($job->title ?? $name)) : $name;
                    $avatar = asset('assets/img/group-icon.png');
                    break;
                case 3:
                    $org = Org::select('org_id', 'name')->find($box->org_id);
                    $name = $org ? ("Tổ chức: " . $org->name) : $name;
                    $avatar = asset('assets/img/org-icon.png');
                    break;
            }

            return [
                'id' => $box->id,
                'type' => $box->type,
                'name' => $name,
                'avatar' => $avatar,
                'last_message' => $lastMessage ?: '<i>Không có tin nhắn</i>',
                'last_time' => $lastTime ?: '',
                'unread' => (int) ($unreadByBox[$box->id] ?? 0),
            ];
        });

        return [
            'boxes' => $result,
            'unread_total' => $result->sum('unread'),
        ];
    }

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

        $this->emitHeaderSummary($userId);
    }

    public function markBoxMessagesAsRead(int $userId, int $boxId)
    {
        $messageIds = DB::table('messages')
            ->where('box_id', $boxId)
            ->pluck('id');

        if ($messageIds->isEmpty())
            return;

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
        $this->emitHeaderSummary($userId);
    }



    private function emitHeaderSummary(int $userId): void
    {
        $notif = $this->getHeaderData($userId);
        $chat = $this->getHeaderChatList($userId);

        event(new HeaderSummaryUpdated($userId, $notif + ['chat' => $chat]));
    }

}

