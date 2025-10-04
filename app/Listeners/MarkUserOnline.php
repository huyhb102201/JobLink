<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Login;

class MarkUserOnline
{
    public function handle(Login $event): void
    {
        $user = $event->user;
        // Xóa hoặc comment dòng này vì không cần broadcast
        // broadcast(new UserOnline($user))->toOthers();
    }
}