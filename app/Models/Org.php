<?php
// app/Models/Org.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Org extends Model
{
    protected $table = 'orgs';
    protected $primaryKey = 'org_id';
    public $timestamps = true;
    protected $fillable = ['owner_account_id','name','seats_limit','description'];
}
