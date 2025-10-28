<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class JobFavorite extends Model
{
    use HasFactory;

    protected $table = 'job_favorites';

    protected $fillable = [
        'user_id',
        'job_id',
    ];

    /**
     * Người dùng đã yêu thích job này.
     */
    public function user()
    {
        // nếu bảng account có khóa chính là account_id
        return $this->belongsTo(Account::class, 'user_id', 'account_id');
    }

    /**
     * Job được yêu thích.
     */
    public function job()
    {
        return $this->belongsTo(Job::class, 'job_id', 'job_id');
    }
}
