<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Message extends Model
{
    use HasFactory;

    protected $fillable = [
        'box_id',
        'conversation_id',
        'sender_id',
        'job_id',
        'content',
        'type',
        'status',
    ];

    /**
     * Người gửi tin nhắn
     */
    public function sender()
    {
        return $this->belongsTo(Account::class, 'sender_id');
    }

    /**
     * Hộp chat chứa tin nhắn
     */
    public function box()
    {
        return $this->belongsTo(BoxChat::class, 'box_id');
    }

}
