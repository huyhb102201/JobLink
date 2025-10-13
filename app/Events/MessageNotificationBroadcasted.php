<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use App\Models\Notification;

class MessageNotificationBroadcasted implements ShouldBroadcastNow
{
    use InteractsWithSockets, SerializesModels;

    public $notification;
    public $userId;

    public function __construct(Notification $notification)
    {
        $this->notification = $notification;
        $this->userId = $notification->user_id;
    }

    public function broadcastOn()
    {
        return new Channel('user-notification.' . $this->userId);
    }

    public function broadcastAs()
    {
        return 'new-message-notification';
    }
}
