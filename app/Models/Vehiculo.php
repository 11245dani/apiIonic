<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vehiculo extends Model {
    use HasFactory;
    protected $fillable = [
        'api_id','user_id','perfil_id','placa','marca','modelo','capacidad','tipo_combustible','activo','sincronizado','api_created_at','api_updated_at','meta'
    ];
    protected $casts = [
        'meta' => 'array',
        'activo' => 'boolean',
        'sincronizado' => 'boolean'
    ];
    public function user() {
        return $this->belongsTo(User::class);
    }
}
