<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Posicion extends Model
{
    use HasFactory;

    protected $table = 'posiciones'; // 👈 nombre correcto de la tabla

    protected $fillable = ['recorrido_id', 'latitud', 'longitud', 'registrado_en'];

    public $incrementing = false;
    protected $keyType = 'string';


    public function recorrido()
    {
        return $this->belongsTo(Recorrido::class);
    }
}
