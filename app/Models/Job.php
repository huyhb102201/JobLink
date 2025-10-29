<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class Job extends Model
{
    use HasFactory;


    protected $primaryKey = 'job_id';

    protected $fillable = [
        'account_id',
        'title',
        'description',
        'category_id',
        'quantity',
        'budget',
        'total_budget',
        'payment_type',
        'status',
        'escrow_status',
        'deadline',
        'apply_id',
    ];
    protected $casts = [
        'deadline' => 'datetime',
        'budget' => 'decimal:2',
        'status' => 'string',
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
            \App\Models\Account::class, // model liên quan
            'job_apply',                // bảng pivot
            'job_id',                   // FK của Job trong pivot
            'user_id',                  // FK của Account trong pivot
            'job_id',                   // khóa chính của Job
            'account_id'                // khóa chính của Account
        )
            ->withPivot(['id', 'status', 'introduction', 'created_at', 'updated_at'])
            ->withTimestamps()
            ->with('profile'); // eager profile cho mỗi account
    }

    // Quan hệ với Comment
    public function comments()
    {
        return $this->hasMany(Comment::class, 'job_id');
    }
    public function client(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'account_id', 'account_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(JobCategory::class, 'category_id', 'category_id');
    }

    public function tasks()
    {
        return $this->hasMany(Task::class, 'job_id', 'job_id');
    }

    public function favoritedBy()
    {
        return $this->belongsToMany(Account::class, 'job_favorites', 'job_id', 'user_id')
            ->withTimestamps();
    }
    // app/Models/Job.php
    public function favorites()
    {
        return $this->hasMany(\App\Models\JobFavorite::class, 'job_id', 'job_id');
    }


}