<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;

class UserOffline implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $user;

    public function __construct($user)
    {
        $this->user = [
            'id' => $user->account_id,
            'name' => $user->name,
            'avatar' => $user->avatar_url ?? null,
        ];
    }

    public function broadcastOn(): Channel
    {
        return new Channel('users');
    }

    public function broadcastAs(): string
    {
        return 'UserOffline';
    }
}
