<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobView extends Model
{
    use HasFactory;

    protected $table = 'jobs_view';
    //public $timestamps = false; 

    protected $fillable = [
        'job_id',
        'account_id',
        'ip_address',
        'action',
        'view',
    ];

    public function job()
    {
        return $this->belongsTo(Job::class);
    }

    public function account()
    {
        return $this->belongsTo(Account::class);
    }
}
