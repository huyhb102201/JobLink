<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Logout;

class MarkUserOffline
{
    public function handle(Logout $event): void
    {
        $user = $event->user;
        // Xóa hoặc comment dòng này vì không cần broadcast
        // broadcast(new UserOffline($user))->toOthers();
    }
}