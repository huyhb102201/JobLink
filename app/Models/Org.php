<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
class Org extends Model
{
    protected $table = 'orgs';
    protected $primaryKey = 'org_id';
    public $timestamps = true;

    // nhớ cho 'status' vào fillable
    protected $fillable = [
        'owner_account_id', 'name', 'tax_code', 'address', 'phone', 'email', 'website', 'seats_limit', 'description', 'status',
    ];

    protected $casts = [
        'seats_limit' => 'int',
        'created_at'  => 'datetime',
        'updated_at'  => 'datetime',
    ];

    // giá trị mặc định nếu cột status trống
    protected $attributes = [
        'status' => 'UNVERIFIED',
    ];

    // append để Blade gọi $org->verification_status, $org->is_verified
    protected $appends = ['verification_status', 'is_verified'];

    public function getVerificationStatusAttribute(): string
    {
        $s = strtoupper($this->status ?? '');
        // các trạng thái bạn dùng: VERIFIED / PENDING / REJECTED / UNVERIFIED
        return in_array($s, ['VERIFIED','PENDING','REJECTED']) ? $s : 'UNVERIFIED';
    }

    public function getIsVerifiedAttribute(): bool
    {
        return $this->verification_status === 'VERIFIED';
    }

    /* (tuỳ chọn) helper/scopes/relations */
    public function scopeVerified($q)   { return $q->where('status', 'VERIFIED'); }

    public function invitations()      { return $this->hasMany(OrgInvitation::class, 'org_id', 'org_id'); }

    public function boxChats()
    {
        return $this->hasMany(BoxChat::class, 'org_id', 'org_id');
    }

    public function members()
    {
        return $this->hasMany(OrgMember::class, 'org_id', 'org_id');
    }

    // THÊM MỐI QUAN HỆ OWNER
    public function owner()
    {
        return $this->belongsTo(Account::class, 'owner_account_id', 'account_id');
    }
    
}
