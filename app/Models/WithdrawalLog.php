<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WithdrawalLog extends Model
{
    protected $table = 'withdrawal_logs';
    protected $primaryKey = 'id';

    protected $fillable = [
        'account_id',
        'bank_account_number',
        'bank_name',
        'bank_short',
        'bank_code',
        'amount_cents',
        'fee_cents',
        'currency',
        'status',   // processing | approved | rejected | paid
        'note',
        'meta',     // JSON: {by, ip, ua, ...}
    ];

    protected $casts = [
        'amount_cents' => 'integer',
        'fee_cents'    => 'integer',
        'meta'         => 'array',
    ];

    public function account()
    {
        return $this->belongsTo(\App\Models\Account::class, 'account_id', 'account_id')
            ->with('profile');
    }

    // Helpers
    public function amountVnd(): string
    {
        return number_format($this->amount_cents / 100, 0, ',', '.').' ₫';
    }
    public function feeVnd(): string
    {
        return number_format(($this->fee_cents ?? 0) / 100, 0, ',', '.').' ₫';
    }
}
