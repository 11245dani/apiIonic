<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role_id',
        'perfil_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

public function vehiculos()
{
    return $this->hasMany(\App\Models\Vehiculo::class, 'user_id');
}

public function rutas()
{
    return $this->hasMany(Ruta::class, 'user_id', 'id');
}


    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function hasRole(string $nombre): bool
    {
        return $this->role && $this->role->nombre === $nombre;
    }


}
