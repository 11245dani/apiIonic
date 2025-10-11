<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ruta extends Model
{
    use HasFactory;

    protected $fillable = [
        'api_id',
        'user_id',
        'perfil_id',
        'nombre_ruta',
        'calles',
        'shape',
        'sincronizado'
    ];

    protected $casts = [
        'calles' => 'array',
        'shape' => 'array',
        'sincronizado' => 'boolean',
    ];

    // Relación con el usuario
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
