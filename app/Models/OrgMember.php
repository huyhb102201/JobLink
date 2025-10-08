<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrgMember extends Model
{
    protected $table = 'org_members';
    protected $primaryKey = 'org_member_id';
    public $timestamps = true;

    protected $fillable = [
        'org_id',
        'account_id',
        'role',
        'status',
    ];

    public function org()
    {
        return $this->belongsTo(Org::class, 'org_id', 'org_id');
    }

    public function account()
    {
        return $this->belongsTo(Account::class, 'account_id', 'account_id');
    }
}