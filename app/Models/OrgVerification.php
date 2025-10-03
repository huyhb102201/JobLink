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
        'mime_type',
        'file_size',
        'review_note',
        'reviewed_by_account_id',
        'reviewed_at',
    ];

    public function org()
    {
        return $this->belongsTo(Org::class, 'org_id', 'org_id');
    }
}
