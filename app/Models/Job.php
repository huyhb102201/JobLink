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
        'category_id',
        'budget',
        'payment_type',
        'status',
        'deadline',
        'apply_id',
    ];

    // Quan hệ với JobDetail
    public function jobDetails()
    {
        return $this->hasMany(JobDetail::class, 'job_id', 'job_id');
    }

    // Quan hệ với JobCategories
    public function jobCategory()
    {
        return $this->belongsTo(JobCategory::class, 'category_id', 'category_id');
    }

    // Quan hệ với user nhận chat
    public function account()
    {
        return $this->belongsTo(Account::class, 'account_id');
    }
    public function categoryRef()
    {
        return $this->belongsTo(\App\Models\JobCategory::class, 'category_id', 'category_id');
    }

}
