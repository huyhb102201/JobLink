<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BoxChat extends Model
{
    protected $table = 'box_chat';
    protected $fillable = ['sender_id', 'receiver_id', 'job_id', 'org_id', 'name', 'type'];

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

    public function job()
    {
        return $this->belongsTo(Job::class, 'job_id', 'job_id');
    }

    public function org()
    {
        return $this->belongsTo(Org::class, 'org_id', 'org_id');
    }

    public function latestMessage()
    {
        return $this->hasOne(Message::class, 'box_id')->latestOfMany();
    }

}