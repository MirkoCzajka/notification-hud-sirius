<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class UserRole extends Model
{
    use HasFactory;

    protected $table = 'user_roles';

    protected $fillable = [
        'type',
        'description',
        'daily_msg_limit',
        'permissions',
    ];

    protected $casts = [
        'permissions'     => 'array',
        'daily_msg_limit' => 'integer',
    ];

    public function users()
    {
        return $this->hasMany(User::class, 'role_id');
    }

    // Helper: sin límite si es 0
    public function hasUnlimitedDailyMessages(): bool
    {
        return ($this->daily_msg_limit ?? 0) === 0;
    }
}
