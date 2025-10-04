<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BoxChat extends Model
{
    protected $table = 'box_chat';
    protected $fillable = ['sender_id', 'receiver_id', 'name', 'type'];

    public function messages()
    {
        return $this->hasMany(Message::class, 'box_id');
    }

    public function sender()
    {
        return $this->belongsTo(Account::class, 'sender_id');
    }

    public function receiver()
    {
        return $this->belongsTo(Account::class, 'receiver_id');
    }
}