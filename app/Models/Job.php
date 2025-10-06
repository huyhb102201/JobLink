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
        return $this->belongsTo(Account::class, 'account_id', 'account_id');
    }
    public function categoryRef()
    {
        return $this->belongsTo(\App\Models\JobCategory::class, 'category_id', 'category_id');
    }

    public function applicants()
    {
        return $this->belongsToMany(
            \App\Models\Account::class, // model Account
            'job_apply',                // bảng trung gian
            'job_id',                   // foreign key của Job trong job_apply
            'user_id'                   // foreign key của Account trong job_apply
        )->withPivot('status')
            ->withTimestamps();
    }

        // Quan hệ với Comment
    public function comments()
    {
        return $this->hasMany(Comment::class, 'job_id');
    }


}
