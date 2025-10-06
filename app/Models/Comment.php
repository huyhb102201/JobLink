<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Comment extends Model
{
    use HasFactory;

    protected $table = 'comments';

    protected $fillable = [
    'account_id',
    'job_id',
    'content',
    'parent_id',
];

public function replies()
{
    return $this->hasMany(Comment::class, 'parent_id')->with('account', 'replies');
}

public function account()
{
    return $this->belongsTo(Account::class, 'account_id');
}

public function job()
{
    return $this->belongsTo(Job::class, 'job_id');
}

}
