<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MembershipPlan extends Model
{
    protected $table = 'membership_plans';
    protected $primaryKey = 'plan_id';

      protected $fillable = [
        'account_type_id', 
        'name',
        'description',
        'duration_days',
        'sort_order',
        'discount_percent',
        'tagline', 
        'price', 
        'is_popular', 
        'is_active',
        'features',
    ];

    protected $casts = [
        'features'   => 'array',
        'is_popular' => 'boolean',
        'is_active' => 'boolean',
        'discount_percent'=> 'float',
    ];

    // Liên kết tới loại tài khoản
    public function accountType()
    {
        return $this->belongsTo(AccountType::class, 'account_type_id', 'account_type_id');
    }
}
