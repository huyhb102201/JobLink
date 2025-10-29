<?php

namespace App\Events;

use App\Models\Notification;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class GenericNotificationBroadcasted implements ShouldBroadcast
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
        return 'new-system-notification';
    }

    public function broadcastWith(): array
    {
        // Dữ liệu gửi ra cho JS (e.notification)
        return [
            'notification' => [
                'id'        => $this->notification->id,
                'title'     => $this->notification->title,
                'body'      => $this->notification->body,
                'meta'      => $this->notification->meta,
                'created_at'=> $this->notification->created_at->toDateTimeString(),
            ]
        ];
    }
}
