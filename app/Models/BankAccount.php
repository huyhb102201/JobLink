<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BankAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'account_id',
        'bank_name',
        'account_number',
        'account_holder',
        'branch_name',
        'is_default',
    ];

    public function account()
    {
        return $this->belongsTo(Account::class, 'account_id');
    }
}
