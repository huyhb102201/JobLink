<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Task extends Model
{
    use HasFactory;

    /**
     * Tên bảng trong database (nếu khác với tên mặc định "tasks")
     */
    protected $table = 'tasks';

    /**
     * Các cột cho phép gán hàng loạt (mass assignment)
     */
    protected $fillable = [
        'task_id',
        'job_id',
        'title',
        'description',
        'status',
        'start_date',
        'due_date',
        'assigned_to',
        'file_root',
        'file_path',
        'file_url',
    ];

    /**
     * Quan hệ: Một Task thuộc về một Job
     */
    public function job(): BelongsTo
    {
        return $this->belongsTo(Job::class, 'job_id', 'job_id');
    }

    /**
     * Quan hệ: Một Task được gán cho một User (freelancer)
     */
    public function assignee()
    {
        return $this->belongsTo(Account::class, 'assigned_to', 'account_id')
                    ->with('profile'); 
    }
    /**
     * Quan hệ: Một Task có nhiều file đính kèm
     */

    public function files()
    {
        return $this->hasMany(TaskFile::class);
    }

}
