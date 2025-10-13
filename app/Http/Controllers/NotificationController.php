<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use App\Models\Notification;
use App\Models\BoxChat;
use App\Models\Account;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;


class NotificationController extends Controller
{
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

        // Latest 5 notifications (dropdown)
        $notifications = Notification::forUser($userId)
            ->where('type', '!=', Notification::TYPE_MESSAGE)
            ->where('visible', true)
            ->orderByDesc('created_at')
            ->take(5)
            ->get();

        // Count badges
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

        return response()->json([
            'notifications' => $notifications,
            'unread_notifications' => $unreadNotifications,
            'unread_messages' => $unreadMessages,
        ]);
    }


    public function headerList()
    {
        $userId = Auth::id();

        if (!$userId) {
            return response()->json([
                'boxes' => [],
                'unread_total' => 0,
            ]);
        }

        // --- Lấy 5 box gần nhất ---
        $boxes = BoxChat::where(function ($q) use ($userId) {
            $q->where('sender_id', $userId)
                ->orWhere('receiver_id', $userId);
        })
            ->with(['sender', 'receiver', 'latestMessage'])
            ->orderByDesc('updated_at')
            ->take(5)
            ->get();

        // --- Lấy toàn bộ notification chưa đọc của user ---
        $notifications = DB::table('notifications')
            ->where('user_id', $userId)
            ->whereNull('read_at') // hoặc 'is_read' = 0 tùy cấu trúc DB
            ->get();

        // --- Giải mã meta để lấy message_id ---
        $messageIds = $notifications->map(function ($n) {
            $meta = json_decode($n->meta, true);
            return $meta['message_id'] ?? null;
        })->filter()->values();

        // --- Lấy box_id tương ứng từ bảng messages ---
        $messages = DB::table('messages')
            ->whereIn('id', $messageIds)
            ->select('id', 'box_id')
            ->get();

        $messageBoxMap = $messages->groupBy('box_id')->map->count();

        // --- Duyệt từng box để build dữ liệu ---
        $result = $boxes->map(function ($box) use ($userId, $messageBoxMap) {
            $latest = $box->latestMessage;
            $lastMessage = '';

            if (!empty($latest?->content)) {
                try {
                    $lastMessage = Crypt::decryptString($latest->content);
                } catch (\Exception $e) {
                    $lastMessage = $latest->content;
                }
            }

            // --- Xác định thông tin hiển thị theo loại box ---
            $name = 'Người dùng';
            $avatar = asset('assets/img/defaultavatar.jpg');

            switch ($box->type) {
                case 1: // 1:1 chat
                    $partner = $box->sender_id === $userId ? $box->receiver : $box->sender;
                    $name = $partner->name ?? 'Người dùng';
                    $avatar = !empty($partner->avatar_url)
                        ? asset($partner->avatar_url)
                        : $avatar;
                    break;

                case 2: // nhóm chat
                    $name = $box->name ?? 'Nhóm trò chuyện';
                    $avatar = !empty($box->avatar_url)
                        ? asset($box->avatar_url)
                        : asset('assets/img/group-default.png');
                    break;

                case 3: // doanh nghiệp
                    // Nếu có quan hệ tới bảng accounts
                    $company = Account::find($box->company_id ?? null);
                    $name = $company->name ?? $box->name ?? 'Doanh nghiệp';
                    $avatar = !empty($company->logo_url ?? $box->avatar_url)
                        ? asset($company->logo_url ?? $box->avatar_url)
                        : asset('assets/img/company-default.png');
                    break;
            }

            return [
                'id' => $box->id,
                'type' => $box->type,
                'name' => $name,
                'avatar' => $avatar,
                'last_message' => $lastMessage,
                'last_time' => $latest?->created_at?->diffForHumans() ?? '',
                'unread' => $messageBoxMap[$box->id] ?? 0,
            ];
        });

        $unreadTotal = $result->sum('unread');

        return response()->json([
            'boxes' => $result,
            'unread_total' => $unreadTotal,
        ]);
    }
}
