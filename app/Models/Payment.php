<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $table = 'payments';
    protected $primaryKey = 'payment_id';

    protected $fillable = [
        'account_id',
        'plan_id',
        'order_code',
        'amount',
        'status',
        'description',
    ];

    // Quan hệ
    public function account()
    {
        return $this->belongsTo(Account::class, 'account_id', 'account_id');
    }

    public function plan()
    {
        return $this->belongsTo(MembershipPlan::class, 'plan_id', 'plan_id');
    }
}
