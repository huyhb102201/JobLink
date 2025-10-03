<?php
// app/Models/OrgInvitation.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrgInvitation extends Model
{
    protected $table = 'org_invitations';
    protected $primaryKey = 'invitation_id'; // vì PK của bạn là invitation_id
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = true;

    protected $fillable = [
        'org_id',
        'email',
        'role',        // ADMIN | MANAGER | MEMBER | BILLING
        'token',
        'expires_at',
        'status',      // PENDING | ACCEPTED | CANCELLED
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Quan hệ tiện dụng
    public function org()
    {
        return $this->belongsTo(Org::class, 'org_id', 'org_id');
    }

    // Scopes gọn gàng (tùy chọn)
    public function scopePending($q)
    {
        return $q->where('status', 'PENDING');
    }
    public function scopeNotExpired($q)
    {
        return $q->where(function ($qq) {
            $qq->whereNull('expires_at')->orWhere('expires_at', '>', now());
        });
    }
}
