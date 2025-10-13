<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\NotificationService;

class NotificationController extends Controller
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    // --- Header Data (badge & 5 thông báo gần nhất)
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

    // --- Header Chat List ---
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
        $boxId = $request->input('box_id');

        if (!$userId || !$boxId) {
            return response()->json(['success' => false, 'message' => 'Thiếu dữ liệu'], 400);
        }

        $this->notificationService->markBoxMessagesAsRead($userId, $boxId);
        return response()->json(['success' => true]);
    }
}
