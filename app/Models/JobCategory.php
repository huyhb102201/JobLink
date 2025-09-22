<?php
// app/Models/JobCategory.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JobCategory extends Model
{
    protected $table = 'job_categories';
    protected $primaryKey = 'category_id';   // đúng như DB của bạn
    public $timestamps = false;

    protected $fillable = ['name','description'];
}
