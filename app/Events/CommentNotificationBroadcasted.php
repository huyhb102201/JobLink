<?php

namespace App\Events;

use App\Models\Notification;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CommentNotificationBroadcasted implements ShouldBroadcast
{
    use Dispatchable, SerializesModels;

    public $notification;
    public $receiverId;

    public function __construct(Notification $notification, $receiverId)
    {
        $this->notification = $notification;
        $this->receiverId = $receiverId;
    }

    public function broadcastOn(): Channel
    {
        return new Channel('user-notification.' . $this->receiverId);
    }

    public function broadcastAs(): string
    {
        return 'new-comment-notification';
    }
}
