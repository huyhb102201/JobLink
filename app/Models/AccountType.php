<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccountType extends Model
{
    protected $table = 'account_types';
    protected $primaryKey = 'account_type_id';
    public $timestamps = false; // nếu bảng bạn không dùng timestamps

    protected $fillable = ['name','code','description','monthly_fee','status'];

    public function accounts()
    {
        return $this->hasMany(Account::class, 'account_type_id', 'account_type_id');
    }
}
