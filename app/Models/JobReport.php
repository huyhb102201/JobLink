<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobReport extends Model
{
    use HasFactory;

    protected $table = 'job_reports';
    protected $primaryKey = 'id';

    protected $fillable = [
        'job_id',
        'user_id',
        'reason',
        'message',
        'img',
        'status', // thêm cột trạng thái
    ];

    // Quan hệ: báo cáo thuộc về công việc
    public function job()
    {
        return $this->belongsTo(Job::class, 'job_id', 'job_id');
    }

    // Quan hệ: báo cáo thuộc về người dùng
    public function user()
    {
        return $this->belongsTo(Account::class, 'user_id');
    }

    // Helper: trả về mảng ảnh
    public function getImagesArrayAttribute()
    {
        if (empty($this->img))
            return [];
        $images = explode(',', $this->img);
        return array_slice(array_map('trim', $images), 0, 5); // tối đa 5 ảnh
    }
}
