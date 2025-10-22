<?php

namespace App\Models;

use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;    
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class User extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable;

    protected $table = 'users';

    protected $fillable = [
        'username',
        'password',
        'role_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    // En Laravel 10/11/12 podés castear password para que se hashee solo
    protected $casts = [
        'password' => 'hashed',
    ];

    // Relación: usuario pertenece a un rol
    public function role()
    {
        return $this->belongsTo(UserRole::class, 'role_id');
    }

    // Relación: usuario tiene muchos mensajes
    public function messages()
    {
        return $this->hasMany(Message::class, 'user_id');
    }

    public function getJWTIdentifier() { return $this->getKey(); }
    public function getJWTCustomClaims() { return []; }
}
