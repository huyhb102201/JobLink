<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Profile extends Model
{
    protected $table = 'profiles';
    protected $primaryKey = 'profile_id';
    public $incrementing = true;
    protected $keyType = 'int';

    protected $fillable = ['account_id','username','email','fullname','location','description','skill',];

    public function account() {
        return $this->belongsTo(Account::class, 'account_id', 'account_id');
    }

    // tiện: trả mảng skills từ TEXT CSV
    public function getSkillListAttribute(): array {
        return collect(preg_split('/\s*,\s*/', (string)$this->skill, -1, PREG_SPLIT_NO_EMPTY))
                ->take(50)->values()->all();
    }
}
