<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobPayment extends Model
{
    use HasFactory;

    protected $table = 'job_payments';
    protected $primaryKey = 'id';

    protected $fillable = [
        'job_id',
        'orderCode',
        'amount',
        'description',
        'status',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    // Quan hệ: Mỗi payment thuộc về 1 job
    public function job()
    {
        return $this->belongsTo(Job::class, 'job_id', 'job_id');
    }
}
