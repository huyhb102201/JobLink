<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\NotificationService;
use Illuminate\Support\Facades\DB;

class NotificationController extends Controller
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    // --- Header Data (badge & 5 thông báo gần nhất) - giữ cho tương thích cũ
    public function headerData()
    {
        $userId = Auth::id();
        if (!$userId) {
            return response()->json([
                'notifications' => [],
                'unread_notifications' => 0,
                'unread_messages' => 0,
            ]);
        }

        $data = $this->notificationService->getHeaderData($userId);
        return response()->json($data);
    }

    // --- Header Chat List (giữ cho tương thích cũ)
    public function headerList()
    {
        $userId = Auth::id();
        if (!$userId) {
            return response()->json([
                'boxes' => [],
                'unread_total' => 0,
            ]);
        }

        $data = $this->notificationService->getHeaderChatList($userId);
        return response()->json($data);
    }

    // --- NEW: Gộp cả 2 API thành 1, có ETag/304 ---
    public function headerSummary(Request $request)
    {
        $userId = Auth::id();
        if (!$userId) {
            return response()->json([
                'notifications' => [],
                'unread_notifications' => 0,
                'unread_messages' => 0,
                'chat' => ['boxes' => [], 'unread_total' => 0],
            ]);
        }

        // Tính etag đơn giản dựa trên mốc cập nhật gần nhất
        $notifMax = DB::table('notifications')
            ->where('user_id', $userId)
            ->where('visible', 1)
            ->max('updated_at') ?? now();
        $boxMax = DB::table('box_chat')
            ->where(function ($q) use ($userId) {
                $q->where('sender_id', $userId)
                  ->orWhere('receiver_id', $userId)
                  ->orWhereExists(function ($s) use ($userId) {
                      $s->select(DB::raw(1))
                        ->from('org_members')
                        ->whereColumn('org_members.org_id', 'box_chat.org_id')
                        ->where('org_members.account_id', $userId)
                        ->where('org_members.status', 'ACTIVE');
                  });
            })
            ->max('updated_at') ?? now();

        $etag = md5($userId.'|'.$notifMax.'|'.$boxMax);
        $clientEtags = $request->getETags();
        if (!empty($clientEtags) && $clientEtags[0] === $etag) {
            return response()->noContent(304)->setEtag($etag);
        }

        $notif = $this->notificationService->getHeaderData($userId);
        $chat  = $this->notificationService->getHeaderChatList($userId);

        return response()->json($notif + ['chat' => $chat])->setEtag($etag);
    }

    // --- Đánh dấu toàn bộ thông báo (trừ message) là đã đọc ---
    public function markNotificationsRead()
    {
        $userId = Auth::id();

        if (!$userId) {
            return response()->json(['success' => false, 'message' => 'Chưa đăng nhập'], 401);
        }

        $this->notificationService->markAllNonMessageAsRead($userId);
        return response()->json(['success' => true]);
    }

    // --- Đánh dấu tin nhắn trong 1 box đã đọc ---
    public function markBoxMessagesRead(Request $request)
    {
        $userId = Auth::id();
        $boxId = (int) $request->input('box_id');

        if (!$userId || !$boxId) {
            return response()->json(['success' => false, 'message' => 'Thiếu dữ liệu'], 400);
        }

        $this->notificationService->markBoxMessagesAsRead($userId, $boxId);
        return response()->json(['success' => true]);
    }
}
