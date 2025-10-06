<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable; // để dùng guard web (session)
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens; // nếu sau này cần API token
use Illuminate\Contracts\Auth\MustVerifyEmail;

class Account extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, Notifiable;

    protected $table = 'accounts';
    protected $primaryKey = 'account_id';
    public $timestamps = true; // có created_at/updated_at

    protected $fillable = [
        'account_type_id',
        'firebase_uid',
        'name',
        'avatar_url',
        'provider',
        'provider_id',
        'email',
        'email_verify_token',
        'password',
        'status',
        'last_login_at',
        'email_verified_at',
        'oauth_access_token',
        'oauth_refresh_token',
        'oauth_expires_at',
        'last_login_ip',
        'login_provider_last',
    ];

    protected $hidden = ['password', 'oauth_access_token', 'oauth_refresh_token'];

    protected $dates = ['last_login_at', 'email_verified_at', 'oauth_expires_at', 'created_at', 'updated_at'];
    protected $casts = [
        'last_login_at' => 'datetime',
        'email_verified_at' => 'datetime',
        'oauth_expires_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Laravel mặc định dùng cột 'email' làm username; nếu muốn đổi có thể override:
    // public function getAuthIdentifierName(){ return 'account_id'; }

    // Trả về tên cột dùng làm identifier
    public function getAuthIdentifierName()
    {
        return 'account_id';
    }

    // Trả về giá trị identifier
    public function getAuthIdentifier()
    {
        return $this->account_id;
    }
    public function profile()
    {
        return $this->hasOne(Profile::class, 'account_id', 'account_id');
    }
    public function type()
{
    return $this->belongsTo(AccountType::class, 'account_type_id', 'account_type_id');
}
    public function sendEmailVerificationNotification()
    {
        // Gửi bằng SendGrid SDK thay vì Mailer
        app(\App\Services\VerifyEmailService::class)->send($this);
    }
    public function accountType()
    {
        // Giả định khóa ngoại trong bảng 'accounts' là 'account_type_id'
        // và khóa chính trong bảng 'account_types' là 'account_type_id'
        return $this->belongsTo(AccountType::class, 'account_type_id', 'account_type_id');
    }
    public function jobs()
    {
        return $this->hasMany(Job::class, 'account_id', 'account_id');
    }


     // Quan hệ với Comment
    public function comments()
    {
        return $this->hasMany(Comment::class, 'account_id');
    }

}
