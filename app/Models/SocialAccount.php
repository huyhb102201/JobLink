<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SocialAccount extends Model
{
    protected $fillable = [
        'account_id',
        'provider',
        'provider_id',
        'nickname',
        'name',
        'email',
        'avatar',
        'token',
        'refresh_token',
        'token_expires_at',
    ];

    protected $casts = [
        'token_expires_at' => 'datetime',
    ];

    public function account()
    {
        return $this->belongsTo(\App\Models\Account::class, 'account_id', 'account_id');
    }
}
