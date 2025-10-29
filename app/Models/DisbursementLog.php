<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DisbursementLog extends Model
{
    // Tên bảng tương ứng
    protected $table = 'disbursement_logs';

    // Không có cột updated_at
    public $timestamps = false;

    // Khóa chính
    protected $primaryKey = 'id';

    // Cho phép gán hàng loạt
    protected $fillable = [
        'job_id',
        'payer_account_id',
        'receiver_account_id',
        'amount_cents',
        'currency',
        'type',
        'note',
        'meta',
        'created_at',
    ];

    // Ép kiểu dữ liệu
    protected $casts = [
        'amount_cents' => 'integer',
        'created_at'   => 'datetime',
        'meta'         => 'array', // meta là JSON
    ];

    // ========================
    // QUAN HỆ
    // ========================

    // Nếu bạn có model Account
    public function receiver()
    {
        return $this->belongsTo(Account::class, 'receiver_account_id');
    }

    public function payer()
    {
        return $this->belongsTo(Account::class, 'payer_account_id');
    }

    // Nếu bạn có model Job
    public function job()
    {
        return $this->belongsTo(Job::class, 'job_id');
    }

    // ========================
    // HELPER
    // ========================

    // Trả số tiền định dạng đẹp (VND)
    public function getAmountFormattedAttribute()
    {
        return number_format($this->amount_cents) . 'đ';
    }

    // Trả thời gian ngắn gọn
    public function getDateShortAttribute()
    {
        return optional($this->created_at)->format('Y-m-d H:i');
    }
}
