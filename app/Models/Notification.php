<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class Notification extends Model
{
    use HasFactory;

    protected $table = 'notifications';

    protected $fillable = [
        'user_id',
        'actor_id',
        'type',
        'title',
        'body',
        'meta',
        'is_read',
        'read_at',
        'severity',
        'visible',
    ];

    protected $casts = [
        'meta' => 'array',
        'is_read' => 'boolean',
        'read_at' => 'datetime',
        'visible' => 'boolean',
    ];

    // Type constants for convenience
    public const TYPE_NOTIFICATION = 'notification';
    public const TYPE_MESSAGE = 'message';
    public const TYPE_COMMENT = 'comment';
    public const TYPE_SCAM_REPORT = 'scam_report';
    public const TYPE_ALERT = 'alert';
    public const TYPE_SYSTEM = 'system';

    // Severity constants
    public const SEVERITY_LOW = 'low';
    public const SEVERITY_MEDIUM = 'medium';
    public const SEVERITY_HIGH = 'high';
    public const SEVERITY_CRITICAL = 'critical';

    // Relationships
    public function user()
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }

    public function actor()
    {
        return $this->belongsTo(\App\Models\User::class, 'actor_id');
    }

    // Mark as read
    public function markAsRead(): bool
    {
        if (!$this->is_read) {
            $this->is_read = true;
            $this->read_at = Carbon::now();
            return $this->save();
        }
        return false;
    }

    // Mark as unread
    public function markAsUnread(): bool
    {
        $this->is_read = false;
        $this->read_at = null;
        return $this->save();
    }

    // Scopes
    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function getCreatedAtHumanAttribute()
    {
        return $this->created_at ? $this->created_at->diffForHumans() : null;
    }

}
