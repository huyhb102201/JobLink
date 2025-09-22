<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobDetail extends Model
{
    use HasFactory;

    // Tên bảng
    protected $table = 'job_detail';

    // Khóa chính
    protected $primaryKey = 'detail_id';

    // Trường có thể gán hàng loạt
    protected $fillable = [
        'job_id',
        'content',
        'notes',
        'created_at',
        'updated_at'
    ];

    // Không dùng timestamps mặc định của Eloquent vì đã có sẵn cột created_at & updated_at
    public $timestamps = false;

    /**
     * Quan hệ với Job
     */
    public function job()
    {
        return $this->belongsTo(Job::class, 'job_id', 'job_id');
    }
}
