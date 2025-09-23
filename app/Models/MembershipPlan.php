<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MembershipPlan extends Model
{
    protected $table = 'membership_plans';
    protected $primaryKey = 'plan_id';

    protected $fillable = [
        'account_type_id', 'tagline', 'price', 'is_popular', 'features',  'discount_percent',

    ];

    protected $casts = [
        'features'   => 'array',
        'is_popular' => 'boolean',
        'discount_percent'=> 'float',

    ];

    // Liên kết tới loại tài khoản
    public function accountType()
    {
        return $this->belongsTo(AccountType::class, 'account_type_id', 'account_type_id');
    }
}
