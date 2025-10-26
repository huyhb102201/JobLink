<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class JobCategory extends Model
{
    use HasFactory;

    protected $table = 'job_categories'; // tên bảng
    protected $primaryKey = 'category_id'; // khóa chính
    public $timestamps = false; // Bảng không có created_at và updated_at

    protected $fillable = [
        'name',
        'description',
        'img_url',
        'isDeleted',
    ];

    protected $casts = [
        'isDeleted' => 'boolean',
    ];

    // Quan hệ với Job (1 category có nhiều job)
    public function jobs()
    {
        return $this->hasMany(Job::class, 'category_id', 'category_id');
    }

    // Scope để chỉ lấy các category chưa bị xóa
    public function scopeNotDeleted($query)
    {
        return $query->where('isDeleted', 0);
    }

    // Đếm số jobs của category
    public function getJobsCountAttribute()
    {
        return $this->jobs()->count();
    }
}
