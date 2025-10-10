<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrgVerification extends Model
{
    protected $table = 'org_verifications';
    protected $fillable = [
        'org_id',
        'submitted_by_account_id',
        'status',                 // PENDING | APPROVED | REJECTED
        'file_path',
        'file_url',
        'mime_type',
        'file_size',
        'review_note',
        'reviewed_by_account_id',
        'reviewed_at',
    ];

        protected $casts = [
        'reviewed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ]; 

    public function org()
    {
        return $this->belongsTo(Org::class, 'org_id', 'org_id');
    }
     public function submittedByAccount()
    {
        return $this->belongsTo(Account::class, 'submitted_by_account_id', 'account_id');
    }

    public function reviewedByAccount()
    {
        return $this->belongsTo(Account::class, 'reviewed_by_account_id', 'account_id');
    }
}
