<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Message extends Model
{
    use HasFactory;

    protected $fillable = [
        'conversation_id',
        'sender_id',
        'job_id',
        'content',
        'type',
        'status',
    ];

    public function sender()
    {
        return $this->belongsTo(Account::class, 'sender_id');
    }
}
