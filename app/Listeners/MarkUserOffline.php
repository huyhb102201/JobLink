<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Logout;
use App\Events\UserOffline;

class MarkUserOffline
{
    public function handle(Logout $event): void
    {
        $user = $event->user;

        // $user->update(['is_online' => false]);

        broadcast(new UserOffline($user))->toOthers();
    }
}
