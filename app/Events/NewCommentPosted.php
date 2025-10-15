<?php

namespace App\Events;

use App\Models\Comment;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;

class NewCommentPosted implements ShouldBroadcast
{
    use SerializesModels;

    public $comment;

    public function __construct(Comment $comment)
    {
        $this->comment = $comment->load('account');
    }

    // Kênh công khai: mọi người đang ở trang bài viết đều nhận được
    public function broadcastOn()
    {
        return new Channel('job-comments.' . $this->comment->job_id);
    }

    public function broadcastAs()
    {
        return 'new-comment';
    }
}
