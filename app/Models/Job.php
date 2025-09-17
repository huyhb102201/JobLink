<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Job extends Model
{
    use HasFactory;

    protected $primaryKey = 'job_id';

    protected $fillable = [
        'account_id',
        'title',
        'description',
        'category',
        'budget',
        'payment_type',
        'status',
        'deadline',
    ];

    // Quan hệ với user nhận chat
    public function account()
    {
        return $this->belongsTo(Account::class, 'account_id');
    }
}
