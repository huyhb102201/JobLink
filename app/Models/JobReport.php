<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Account;
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
    ];

    /**
     * Quan hệ: Báo cáo thuộc về một công việc
     */
    public function job()
    {
        return $this->belongsTo(Job::class, 'job_id', 'job_id');
    }

    /**
     * Quan hệ: Báo cáo được gửi bởi một người dùng
     */
    public function reporter()
    {
        return $this->belongsTo(Account::class, 'user_id', 'account_id')->withDefault();
    }
}
