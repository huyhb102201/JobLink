<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Skill extends Model
{
    use HasFactory;

    protected $table = 'skills';
    protected $primaryKey = 'skill_id';
    public $timestamps = false; // vì bảng skills không có created_at, updated_at

    protected $fillable = [
        'name',
    ];
}
