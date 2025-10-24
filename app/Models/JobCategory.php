<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class JobCategory extends Model
{
    use HasFactory;

    protected $table = 'job_categories'; // tên bảng
    protected $primaryKey = 'category_id'; // khóa chính

    protected $fillable = [
        'name',
        'description',
        'img_url',
    ];

    // Quan hệ với Job (1 category có nhiều job)
    public function jobs()
    {
        return $this->hasMany(Job::class, 'category_id', 'category_id');
    }
}
