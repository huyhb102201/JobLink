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
        if ($this->message->conversation_id === 0) {
            if ($this->message->job_id) {
                // Chat nhóm công việc
                return new PresenceChannel('chat-group.' . $this->message->job_id);
            } elseif ($this->message->org_id) {
                // Chat nhóm tổ chức
                return new PresenceChannel('chat-org.' . $this->message->org_id);
            }
        } else {
            // Chat 1-1
            $userIds = [$this->message->sender_id, $this->message->conversation_id];
            sort($userIds);
            return new PrivateChannel('chat.' . implode('.', $userIds));
        }
    }

    public function broadcastWith()
    {
        try {
            $this->message->content = $this->message->content ? Crypt::decryptString($this->message->content) : null;
        } catch (\Exception $e) {
            Log::error('Failed to decrypt message content in broadcast', [
                'message_id' => $this->message->id,
                'error' => $e->getMessage(),
            ]);
            $this->message->content = '[Không thể giải mã tin nhắn]';
        }

        return [
            'message' => [
                'id' => $this->message->id,
                'content' => $this->message->content,
                'img' => $this->message->img ? asset($this->message->img) : null,
                'sender_id' => $this->message->sender_id,
                'sender' => [
                    'name' => $this->message->sender->name ?? 'Unknown',
                    'avatar_url' => $this->message->sender->avatar_url ?? asset('assets/img/defaultavatar.jpg'),
                ],
                'job_id' => $this->message->job_id,
                'org_id' => $this->message->org_id,
                'conversation_id' => $this->message->conversation_id,
                'created_at' => $this->message->created_at->toISOString(),
            ]
        ];
    }
}