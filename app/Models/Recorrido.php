<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Recorrido extends Model
{
    use HasFactory;

    protected $fillable = [
        'api_id',
        'ruta_id',
        'vehiculo_id',
        'perfil_id',
        'user_id',
        'estado',
    ];

    public $incrementing = false; // 🔹 UUID, no autoincremental
    protected $keyType = 'string'; // 🔹 clave primaria tipo string

    protected $casts = [
        'api_id' => 'string',
        'ruta_id' => 'string',
        'vehiculo_id' => 'string',
        'perfil_id' => 'string',
        'user_id' => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (!$model->id) {
                $model->id = (string) Str::uuid();
            }
        });
    }

    // 🔹 Relaciones
    public function posiciones()
    {
        return $this->hasMany(Posicion::class);
    }

    public function ruta()
    {
        return $this->belongsTo(Ruta::class, 'ruta_id', 'id');
    }

    public function vehiculo()
    {
        return $this->belongsTo(Vehiculo::class, 'vehiculo_id', 'id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
