<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    protected $fillable = [
        'conversation_id',
        'sender_id',
        'job_id',
        'content',
        'img',
        'type',
        'status',
        'box_id',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function sender()
    {
        return $this->belongsTo(Account::class, 'sender_id');
    }

    public function job()
    {
        return $this->belongsTo(Job::class, 'job_id');
    }

    public function box()
    {
        return $this->belongsTo(BoxChat::class, 'box_id');
    }
}