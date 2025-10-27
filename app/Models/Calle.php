<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Calle extends Model {
    use HasFactory;
    protected $fillable = [
        'api_id','nombre','barrio','meta'
    ];

    public $incrementing = false;
    protected $keyType = 'string';

    protected $casts = [
        'meta' => 'array'
    ];
}
