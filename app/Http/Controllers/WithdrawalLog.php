<?php

// app/Models/WithdrawalLog.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WithdrawalLog extends Model
{
    protected $table = 'withdrawal_logs';
    protected $fillable = [
        'account_id','bank_account_number','amount_cents','fee_cents',
        'currency','status','note','meta',
    ];
    protected $casts = [
        'amount_cents' => 'integer',
        'fee_cents'    => 'integer',
        'meta'         => 'array',
        'created_at'   => 'datetime',
        'updated_at'   => 'datetime',
    ];
}
