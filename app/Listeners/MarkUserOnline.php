<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Login;
use App\Events\UserOnline;

class MarkUserOnline
{
    public function handle(Login $event): void
    {
        $user = $event->user;

        // Có thể lưu cờ online vào DB nếu cần
        // $user->update(['is_online' => true]);

        broadcast(new UserOnline($user))->toOthers();
    }
}
