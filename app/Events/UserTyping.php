<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserTyping implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $jobId;
    public $senderId;
    public $receiverId;

    public function __construct($jobId, $senderId, $receiverId)
    {
        $this->jobId = $jobId;
        $this->senderId = $senderId;
        $this->receiverId = $receiverId;
    }

    public function broadcastOn()
    {
        $userIds = [$this->senderId, $this->receiverId];
        sort($userIds);
        return new PrivateChannel('job.'.$this->jobId.'.'.implode('.', $userIds));
    }

    public function broadcastWith()
    {
        return [
            'sender_id' => $this->senderId
        ];
    }
}
