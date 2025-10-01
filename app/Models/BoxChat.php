<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BoxChat extends Model
{
    use HasFactory;

    protected $table = 'box_chat'; // thêm dòng này

    protected $fillable = [
        'name',
        'type',
        'conversation_id',
        'sender_id',
        'job_id',
    ];

    /**
     * Lấy tất cả tin nhắn của hộp chat
     */
    public function messages()
    {
        return $this->hasMany(Message::class, 'box_id');
    }
}
