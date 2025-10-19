<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    protected $table = 'reviews';
    protected $primaryKey = 'review_id';
    public $timestamps = false;

    protected $fillable = [
        'reviewer_id',
        'reviewee_id',
        'rating',
        'comment',
        'created_at'
    ];

    public function reviewerProfile()
    {
        return $this->belongsTo(Profile::class, 'reviewer_id', 'profile_id');
    }
    public function revieweeProfile()
    {
        return $this->belongsTo(Profile::class, 'reviewee_id', 'profile_id');
    }

}
