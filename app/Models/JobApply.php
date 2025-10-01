<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobApply extends Model
{
    use HasFactory;

    protected $table = 'job_apply';

    protected $fillable = ['job_id', 'user_id', 'status'];

    public function job()
    {
        return $this->belongsTo(Job::class, 'job_id');
    }


    public function user()
    {
        // Liên kết với Account model
        return $this->belongsTo(\App\Models\Account::class, 'user_id', 'account_id');
    }
}
