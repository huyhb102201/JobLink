<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Review extends Model
{
    use HasFactory;

    protected $table = 'reviews';
    protected $primaryKey = 'review_id';
    public $timestamps = true; // Bảng có created_at

    protected $fillable = [
        'reviewer_id',
        'reviewee_id',
        'rating',
        'comment',
        'isDeleted',
    ];

    protected $casts = [
        'isDeleted' => 'boolean',
        'rating' => 'integer',
    ];
    
    // Chỉ sử dụng created_at, không có updated_at
    const UPDATED_AT = null;

    // Relationship với Profile (người đánh giá)
    public function reviewerProfile()
    {
        return $this->belongsTo(Profile::class, 'reviewer_id', 'profile_id');
    }

    // Relationship với Profile (người được đánh giá)
    public function revieweeProfile()
    {
        return $this->belongsTo(Profile::class, 'reviewee_id', 'profile_id');
    }

    // Relationship với Account qua Profile (người đánh giá)
    public function reviewer()
    {
        return $this->belongsTo(Profile::class, 'reviewer_id', 'profile_id');
    }

    // Relationship với Account qua Profile (người được đánh giá)
    public function reviewee()
    {
        return $this->belongsTo(Profile::class, 'reviewee_id', 'profile_id');
    }

    // Scope để chỉ lấy các review chưa bị xóa
    public function scopeNotDeleted($query)
    {
        return $query->where('isDeleted', 0);
    }
}
