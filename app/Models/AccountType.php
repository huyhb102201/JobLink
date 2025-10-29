<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccountType extends Model
{
    protected $table = 'account_types';
    protected $primaryKey = 'account_type_id';

      protected $fillable = [
        'name',
        'withdraw_monthly_limit_cents',
        'description',
        'code',
        'monthly_fee',
        'connects_per_month',
        'job_post_limit',
        'max_active_contracts',
        'status',
        'auto_approve_job_posts',
    ];
    public function accounts()
    {
        return $this->hasMany(Account::class, 'account_type_id', 'account_type_id');
    }
    public function membership()
    {
        return $this->hasMany(Account::class, 'account_type_id', 'account_type_id');
    }
}
