<?php

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Crypt;

class MessageSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $message;

    public function __construct(Message $message)
    {
        $this->message = $message;
    }

    public function broadcastOn()
    {
        $userIds = [$this->message->sender_id, $this->message->conversation_id];
        sort($userIds);
        return new PrivateChannel('chat.' . implode('.', $userIds));
    }

    public function broadcastWith()
    {
        // Giải mã content trước khi broadcast
        try {
            $this->message->content = $this->message->content ? Crypt::decryptString($this->message->content) : null;
        } catch (\Exception $e) {
            Log::error('Failed to decrypt message content in broadcast', [
                'message_id' => $this->message->id,
                'error' => $e->getMessage(),
            ]);
            $this->message->content = '[Không thể giải mã tin nhắn]';
        }

        // Trả về message với content đã giải mã
        return [
            'message' => [
                'id' => $this->message->id,
                'content' => $this->message->content,
                'img' => $this->message->img ? asset($this->message->img) : null,
                'sender_id' => $this->message->sender_id,
                'sender' => [
                    'name' => $this->message->sender->name ?? 'Unknown',
                    'avatar_url' => $this->message->sender->avatar_url ?? asset('assets/img/blog/blog-1.jpg'),
                ],
                'job_id' => $this->message->job_id,
                'conversation_id' => $this->message->conversation_id,
                'created_at' => $this->message->created_at->toISOString(),
            ]
        ];
    }
}